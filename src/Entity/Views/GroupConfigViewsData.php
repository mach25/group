<?php

namespace Drupal\group\Entity\Views;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Plugin\GroupConfigEnablerManagerInterface;
use Drupal\views\EntityViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the views data for the group config entity type.
 */
class GroupConfigViewsData extends EntityViewsData {

  /**
   * The group config enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupConfigEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The entity manager set but not declared in the parent class.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a GroupConfigViewsData object.
   *
   * @param \Drupal\group\Plugin\GroupConfigEnablerManagerInterface $plugin_manager
   *   The group config enabler plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, SqlEntityStorageInterface $storage_controller, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, TranslationInterface $translation_manager, GroupConfigEnablerManagerInterface $plugin_manager) {
    parent::__construct($entity_type, $storage_controller, $entity_manager, $module_handler, $translation_manager);
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('string_translation'),
      $container->get('plugin.manager.group_config_enabler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Add a custom numeric argument for the parent group ID that allows us to
    // use replacement titles with the parent group's label.
    $data['group_config_field_data']['gid']['argument'] = [
      'id' => 'group_id',
      'numeric' => TRUE,
    ];

    // Get the data table for GroupConfig entities.
    $data_table = $this->entityType->getDataTable();

    // Unset the 'entity_id' field relationship as we want a more powerful one.
    // @todo Eventually, we may want to replace all of 'entity_id'.
    unset($data[$data_table]['entity_id']['relationship']);

    /** @var \Drupal\Core\Entity\EntityTypeInterface[] $entity_types */
    $entity_types = $this->entityManager->getDefinitions();

    // Add views data for all defined plugins so modules can provide default
    // views even though their plugins may not have been installed yet.
    foreach ($this->pluginManager->getAll() as $plugin) {
      $entity_type_id = $plugin->getEntityTypeId();
      $entity_type = $entity_types[$entity_type_id];
      $entity_data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

      // Create a unique field name for this views field.
      $field_name = 'gc__' . $entity_type_id;

      // We only add one 'group_config' relationship per entity type.
      if (isset($data[$entity_data_table][$field_name])) {
        continue;
      }

      $t_args = [
        '@entity_type' => $entity_type->getLabel(),
      ];

      // This relationship will allow a group config entity to easily map to a
      // config entity that it ties to a group, optionally filtering by plugin.
      $data[$data_table][$field_name] = [
        'title' => $this->t('@entity_type from group config', $t_args),
        'help' => $this->t('Relates to the @entity_type entity the group config represents.', $t_args),
        'relationship' => [
          'group' => $entity_type->getLabel(),
          'base' => $entity_data_table,
          'base field' => $entity_type->getKey('id'),
          'relationship field' => 'entity_id',
          'id' => 'group_config_to_entity',
          'label' => $this->t('Group config @entity_type', $t_args),
          'target_entity_type' => $entity_type_id,
        ],
      ];
    }

    return $data;
  }

}
