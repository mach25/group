<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupConfigType;
use Drupal\group\Entity\GroupInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for GroupConfig routes.
 */
class GroupConfigController extends ControllerBase {

  /**
   * The private store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupConfigController.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, RendererInterface $renderer) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * Provides the group config creation overview page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   * @param bool $create_mode
   *   (optional) Whether the target entity still needs to be created. Defaults
   *   to FALSE, meaning the target entity is assumed to exist already.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The group config creation overview page or a redirect to the form for
   *   adding group config if there is only one group config type.
   */
  public function addPage(GroupInterface $group, $create_mode = FALSE) {
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];
    $form_route = $this->addPageFormRoute($group, $create_mode);
    $bundle_names = $this->addPageBundles($group, $create_mode);

    // Set the add bundle message if available.
    $add_bundle_message = $this->addPageBundleMessage($group, $create_mode);
    if ($add_bundle_message !== FALSE) {
      $build['#add_bundle_message'] = $add_bundle_message;
    }

    // Filter out the bundles the user doesn't have access to.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_config');
    foreach ($bundle_names as $plugin_id => $bundle_name) {
      $access = $access_control_handler->createAccess($bundle_name, NULL, ['group' => $group], TRUE);
      if (!$access->isAllowed()) {
        unset($bundle_names[$plugin_id]);
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Redirect if there's only one bundle available.
    if (count($bundle_names) == 1) {
      reset($bundle_names);
      $route_params = ['group' => $group->id(), 'plugin_id' => key($bundle_names)];
      $url = Url::fromRoute($form_route, $route_params, ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    // Set the info for all of the remaining bundles.
    foreach ($bundle_names as $plugin_id => $bundle_name) {
      $plugin = $group->getGroupType()->getConfigPlugin($plugin_id);
      $label = $plugin->getLabel();

      $build['#bundles'][$bundle_name] = [
        'label' => $label,
        'description' => $plugin->getConfigTypeDescription(),
        'add_link' => Link::createFromRoute($label, $form_route, ['group' => $group->id(), 'plugin_id' => $plugin_id]),
      ];
    }

    // Add the list cache tags for the GroupConfigType entity type.
    $bundle_entity_type = $this->entityTypeManager->getDefinition('group_config_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    return $build;
  }

  /**
   * Retrieves a list of available bundles for the add page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   *
   * @return array
   *   An array of group config type IDs, keyed by the plugin that was used to
   *   generate their respective group config types.
   *
   * @see ::addPage()
   */
  protected function addPageBundles(GroupInterface $group, $create_mode) {
    $bundles = [];

    /** @var \Drupal\group\Entity\Storage\GroupConfigTypeStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('group_config_type');
    foreach ($storage->loadByGroupType($group->getGroupType()) as $bundle => $group_config_type) {
      // Skip the bundle if we are listing bundles that allow you to create an
      // entity in the group and the bundle's plugin does not support that.
      if ($create_mode && !$group_config_type->getConfigPlugin()->definesEntityAccess()) {
        continue;
      }

      $bundles[$group_config_type->getConfigPluginId()] = $bundle;
    }

    return $bundles;
  }

  /**
   * Returns the 'add_bundle_message' string for the add page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   *
   * @return string|false
   *   The translated string or FALSE if no string should be set.
   *
   * @see ::addPage()
   */
  protected function addPageBundleMessage(GroupInterface $group, $create_mode) {
    // We do not set the 'add_bundle_message' variable because we deny access to
    // the page if no bundle is available. This method exists so that modules
    // that extend this controller may specify a message should they decide to
    // allow access to their page even if it has no bundles.
    return FALSE;
  }

  /**
   * Returns the route name of the form the add page should link to.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   *
   * @return string
   *   The route name.
   *
   * @see ::addPage()
   */
  protected function addPageFormRoute(GroupInterface $group, $create_mode) {
    return $create_mode
      ? 'entity.group_config.create_form'
      : 'entity.group_config.add_form';
  }

  /**
   * Provides the group config submission form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   * @param string $plugin_id
   *   The group config enabler to add config with.
   *
   * @return array
   *   A group submission form.
   */
  public function addForm(GroupInterface $group, $plugin_id) {
    /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getConfigPlugin($plugin_id);

    $values = [
      'type' => $plugin->getConfigTypeConfigId(),
      'gid' => $group->id(),
    ];
    $group_config = $this->entityTypeManager()->getStorage('group_config')->create($values);

    return $this->entityFormBuilder->getForm($group_config, 'add');
  }

  /**
   * The _title_callback for the entity.group_config.add_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   * @param string $plugin_id
   *   The group config enabler to add config with.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(GroupInterface $group, $plugin_id) {
    /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getConfigPlugin($plugin_id);
    $group_config_type = GroupConfigType::load($plugin->getConfigTypeConfigId());
    return $this->t('Create @name', ['@name' => $group_config_type->label()]);
  }

  /**
   * The _title_callback for the entity.group_config.edit_form route.
   *
   * Overrides the Drupal\Core\Entity\Controller\EntityController::editTitle().
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity edit page, if an entity was found.
   */
  public function editFormTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $route_match->getParameter('group_config')) {
      return $this->t('Edit %label', ['%label' => $entity->label()]);
    }
  }

  /**
   * The _title_callback for the entity.group_config.collection route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   *
   * @return string
   *   The page title.
   *
   * @todo Revisit when 8.2.0 is released, https://www.drupal.org/node/2767853.
   */
  public function collectionTitle(GroupInterface $group) {
    return $this->t('Related entities for @group', ['@group' => $group->label()]);
  }

  /**
   * Provides the group config creation form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the group config to.
   * @param string $plugin_id
   *   The group config enabler to add config with.
   *
   * @return array
   *   A group config creation form.
   */
  public function createForm(GroupInterface $group, $plugin_id) {
    /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getConfigPlugin($plugin_id);

    $wizard_id = 'group_entity';
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $plugin_id . ':' . $group->id();

    // See if the plugin uses a wizard for creating new entities. Also pass this
    // info to the form state.
    $config = $plugin->getConfiguration();
    $extra['group_wizard'] = $config['use_creation_wizard'];
    $extra['group_wizard_id'] = $wizard_id;

    // Pass the group, plugin ID and store ID to the form state as well.
    $extra['group'] = $group;
    $extra['group_config_enabler'] = $plugin_id;
    $extra['store_id'] = $store_id;

    // See if we are on the second step of the form.
    $step2 = $extra['group_wizard'] && $store->get("$store_id:step") === 2;

    // Config entity form, potentially as wizard step 1.
    if (!$step2) {
      // Figure out what entity type the plugin is serving.
      $entity_type_id = $plugin->getEntityTypeId();
      $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
      $storage = $this->entityTypeManager()->getStorage($entity_type_id);

      // Only create a new entity if we have nothing stored.
      if (!$entity = $store->get("$store_id:entity")) {
        $values = [];
        if (($key = $entity_type->getKey('bundle')) && ($bundle = $plugin->getEntityBundle())) {
          $values[$key] = $bundle;
        }
        $entity = $storage->create($values);
      }

      // Use the add form handler if available.
      $operation = 'default';
      if ($entity_type->getFormClass('add')) {
        $operation = 'add';
      }
    }
    // Wizard step 2: Group config form.
    else {
      // Create an empty group config entity.
      $values = [
        'type' => $plugin->getConfigTypeConfigId(),
        'gid' => $group->id(),
      ];
      $entity = $this->entityTypeManager()->getStorage('group_config')->create($values);

      // Group config entities have an add form handler.
      $operation = 'add';
    }

    // Return the entity form with the configuration gathered above.
    return $this->entityFormBuilder()->getForm($entity, $operation, $extra);
  }

  /**
   * The _title_callback for the entity.group_config.create_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create the group config in.
   * @param string $plugin_id
   *   The group config enabler to create config with.
   *
   * @return string
   *   The page title.
   */
  public function createFormTitle(GroupInterface $group, $plugin_id) {
    /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
    $plugin = $group->getGroupType()->getConfigPlugin($plugin_id);
    $group_config_type = GroupConfigType::load($plugin->getConfigTypeConfigId());
    return $this->t('Create @name', ['@name' => $group_config_type->label()]);
  }

}
