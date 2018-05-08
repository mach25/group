<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the Group config type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_config_type",
 *   label = @Translation("Group config type"),
 *   label_singular = @Translation("group config type"),
 *   label_plural = @Translation("group config types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group config type",
 *     plural = "@count group config types"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupConfigTypeStorage",
 *     "access" = "Drupal\group\Entity\Access\GroupConfigTypeAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupConfigTypeForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupConfigTypeForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupConfigTypeDeleteForm"
 *     },
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "config_type",
 *   bundle_of = "group_config",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "group_type",
 *     "config_plugin",
 *     "plugin_config",
 *   }
 * )
 */
class GroupConfigType extends ConfigEntityBundleBase implements GroupConfigTypeInterface {

  /**
   * The machine name of the group config type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group config type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of the group config type.
   *
   * @var string
   */
  protected $description;

  /**
   * The group type ID for the group config type.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The group config enabler plugin ID for the group config type.
   *
   * @var string
   */
  protected $config_plugin;

  /**
   * The group config enabler plugin configuration for group config type.
   *
   * @var array
   */
  protected $plugin_config = [];

  /**
   * The config enabler plugin instance.
   *
   * @var \Drupal\group\Plugin\GroupConfigEnablerInterface
   */
  protected $pluginInstance;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return GroupType::load($this->getGroupTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->group_type;
  }

  /**
   * Returns the config enabler plugin manager.
   *
   * @return \Drupal\group\Plugin\GroupConfigEnablerManagerInterface
   *   The group config plugin manager.
   */
  protected function getConfigEnablerManager() {
    return \Drupal::service('plugin.manager.group_config_enabler');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPlugin() {
    if (!isset($this->pluginInstance)) {
      $configuration = $this->plugin_config;
      $configuration['group_type_id'] = $this->getGroupTypeId();
      $this->pluginInstance = $this->getConfigEnablerManager()->createInstance($this->getConfigPluginId(), $configuration);
    }
    return $this->pluginInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPluginId() {
    return $this->config_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function updateConfigPlugin(array $configuration) {
    $this->plugin_config = $configuration;
    $this->save();

    // Make sure people get a fresh local plugin instance.
    $this->pluginInstance = NULL;

    // Make sure people get a freshly configured plugin collection.
    $this->getConfigEnablerManager()->clearCachedGroupTypeCollections($this->getGroupType());
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByConfigPluginId($plugin_id) {
    /** @var \Drupal\group\Entity\Storage\GroupConfigTypeStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_config_type');
    return $storage->loadByConfigPluginId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntityTypeId($entity_type_id) {
    /** @var \Drupal\group\Entity\Storage\GroupConfigTypeStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('group_config_type');
    return $storage->loadByEntityTypeId($entity_type_id);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      // When a new GroupConfigType is saved, we clear the views data cache to
      // make sure that all of the views data which relies on group config
      // types is up to date.
      if (\Drupal::moduleHandler()->moduleExists('views')) {
        \Drupal::service('views.views_data')->clear();
      }

      // Run the post install tasks on the plugin.
      $this->getConfigPlugin()->postInstall();

      // We need to reset the plugin ID map cache as it will be out of date now.
      $this->getConfigEnablerManager()->clearCachedPluginMaps();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    // When a GroupConfigType is deleted, we clear the views data cache to make
    // sure that all of the views data which relies on group config types is up
    // to date.
    if (\Drupal::moduleHandler()->moduleExists('views')) {
      \Drupal::service('views.views_data')->clear();
    }

    /** @var \Drupal\group\Plugin\GroupConfigEnablerManagerInterface $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.group_config_enabler');

    // We need to reset the plugin ID map cache as it will be out of date now.
    $plugin_manager->clearCachedPluginMaps();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // By adding the group type as a dependency, we ensure the group config
    // type is deleted along with the group type.
    $this->addDependency('config', $this->getGroupType()->getConfigDependencyName());

    // Add the dependencies of the responsible config enabler plugin.
    $this->addDependencies($this->getConfigPlugin()->calculateDependencies());
  }

}
