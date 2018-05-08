<?php

namespace Drupal\group\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Group content entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group_config",
 *   label = @Translation("Group config"),
 *   label_singular = @Translation("group config item"),
 *   label_plural = @Translation("group config items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group config item",
 *     plural = "@count group config items"
 *   ),
 *   bundle_label = @Translation("Group config type"),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupConfigStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\group\Entity\Views\GroupConfigViewsData",
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupConfigListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupConfigRouteProvider",
 *     },
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupConfigForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupConfigForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupConfigDeleteForm",
 *       "group-join" = "Drupal\group\Form\GroupConfigJoinForm",
 *       "group-leave" = "Drupal\group\Form\GroupConfigLeaveForm",
 *     },
 *     "access" = "Drupal\group\Entity\Access\GroupConfigAccessControlHandler",
 *   },
 *   base_table = "group_config",
 *   data_table = "group_config_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/group/{group}/config/add/{plugin_id}",
 *     "add-page" = "/group/{group}/config/add",
 *     "canonical" = "/group/{group}/config/{group_config}",
 *     "collection" = "/group/{group}/config",
 *     "create-form" = "/group/{group}/config/create/{plugin_id}",
 *     "create-page" = "/group/{group}/config/create",
 *     "delete-form" = "/group/{group}/config/{group_config}/delete",
 *     "edit-form" = "/group/{group}/config/{group_config}/edit"
 *   },
 *   bundle_entity_type = "group_config_type",
 *   field_ui_base_route = "entity.group_config_type.edit_form",
 *   permission_granularity = "bundle",
 *   constraints = {
 *     "GroupConfigCardinality" = {}
 *   }
 * )
 */
class GroupConfig extends ContentEntityBase implements GroupConfigInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroupConfigType() {
    return $this->type->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->gid->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->entity_id->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPlugin() {
    return $this->getGroupConfigType()->getConfigPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByConfigPluginId($plugin_id) {
    /** @var \Drupal\group\Entity\Storage\GroupConfigStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_config');
    return $storage->loadByConfigPluginId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntity(ContentEntityInterface $entity) {
    /** @var \Drupal\group\Entity\Storage\GroupConfigStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_config');
    return $storage->loadByEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getConfigPlugin()->getConfigLabel($this);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['group'] = $this->getGroup()->id();
    // These routes depend on the plugin ID.
    if (in_array($rel, ['add-form', 'create-form'])) {
      $uri_route_parameters['plugin_id'] = $this->getConfigPlugin()->getPluginId();
    }
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Set the label so the DB also reflects it.
    $this->set('label', $this->label());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if ($update === FALSE) {
      // We want to make sure that the entity we just added to the group behaves
      // as a grouped entity. This means we may need to update access records,
      // flush some caches containing the entity or perform other operations we
      // cannot possibly know about. Lucky for us, all of that behavior usually
      // happens when saving an entity so let's re-save the added entity.
      $this->getEntity()->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // For the same reasons we re-save entities that are added to a group, we
    // need to re-save entities that were removed from one. See ::postSave().
    /** @var GroupConfigInterface[] $entities */
    foreach ($entities as $group_config) {
      // We only save the entity if it still exists to avoid trying to save an
      // entity that just got deleted and triggered the deletion of its group
      // config entities.
      if ($entity = $group_config->getEntity()) {
        // @todo Revisit when https://www.drupal.org/node/2754399 lands.
        $entity->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent group'))
      ->setDescription(t('The group containing the entity.'))
      ->setSetting('target_type', 'group')
      ->setReadOnly(TRUE);

    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Config'))
      ->setDescription(t('The entity to add to the group.'))
      ->setSetting('target_type', 'group_config_type')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setReadOnly(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group config creator'))
      ->setDescription(t('The username of the group config creator.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\group\Entity\GroupConfig::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the group config was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group config was last edited.'))
      ->setTranslatable(TRUE);

    if (\Drupal::moduleHandler()->moduleExists('path')) {
      $fields['path'] = BaseFieldDefinition::create('path')
        ->setLabel(t('URL alias'))
        ->setTranslatable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'path',
          'weight' => 30,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setComputed(TRUE);
    }

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    if ($group_config_type = GroupConfigType::load($bundle)) {
      $plugin = $group_config_type->getConfigPlugin();

      /** @var \Drupal\Core\Field\BaseFieldDefinition $original */
      $original = $base_field_definitions['entity_id'];

      // Recreated the original entity_id field so that it does not contain any
      // data in its "propertyDefinitions" or "schema" properties because those
      // were set based on the base field which had no clue what bundle to serve
      // up until now. This is a bug in core because we can't simply unset those
      // two properties, see: https://www.drupal.org/node/2346329
      $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
        ->setLabel($plugin->getEntityReferenceLabel() ?: $original->getLabel())
        ->setDescription($plugin->getEntityReferenceDescription() ?: $original->getDescription())
        ->setConstraints($original->getConstraints())
        ->setDisplayOptions('view', $original->getDisplayOptions('view'))
        ->setDisplayOptions('form', $original->getDisplayOptions('form'))
        ->setDisplayConfigurable('view', $original->isDisplayConfigurable('view'))
        ->setDisplayConfigurable('form', $original->isDisplayConfigurable('form'))
        ->setRequired($original->isRequired());

      foreach ($plugin->getEntityReferenceSettings() as $name => $setting) {
        $fields['entity_id']->setSetting($name, $setting);
      }

      return $fields;
    }

    return [];
  }

}
