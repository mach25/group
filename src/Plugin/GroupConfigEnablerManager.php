<?php

namespace Drupal\group\Plugin;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Manages GroupConfigEnabler plugin implementations.
 *
 * @see hook_group_config_info_alter()
 * @see \Drupal\group\Annotation\GroupConfigEnabler
 * @see \Drupal\group\Plugin\GroupConfigEnablerInterface
 * @see \Drupal\group\Plugin\GroupConfigEnablerBase
 * @see plugin_api
 */
class GroupConfigEnablerManager extends DefaultPluginManager implements GroupConfigEnablerManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group type storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $groupTypeStorage;

  /**
   * A group config type storage handler.
   *
   * @var \Drupal\group\Entity\Storage\GroupConfigTypeStorageInterface
   */
  protected $groupConfigTypeStorage;

  /**
   * A collection of vanilla instances of all config enabler plugins.
   *
   * @var \Drupal\group\Plugin\GroupConfigEnablerCollection
   */
  protected $allPlugins;

  /**
   * An list each group type's installed plugins as plugin collections.
   *
   * @var \Drupal\group\Plugin\GroupConfigEnablerCollection[]
   */
  protected $groupTypeInstalled = [];

  /**
   * An static cache of group config type IDs per plugin ID.
   *
   * @var array[]
   */
  protected $pluginGroupConfigTypeMap;

  /**
   * The cache key for the group config type IDs per plugin ID map.
   *
   * @var string
   */
  protected $pluginGroupConfigTypeMapCacheKey;

  /**
   * An static cache of plugin IDs per group type ID.
   *
   * @var array[]
   */
  protected $groupTypePluginMap;

  /**
   * The cache key for the plugin IDs per group type ID map.
   *
   * @var string
   */
  protected $groupTypePluginMapCacheKey;

  /**
   * Constructs a GroupConfigEnablerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/GroupConfigEnabler', $namespaces, $module_handler, 'Drupal\group\Plugin\GroupConfigEnablerInterface', 'Drupal\group\Annotation\GroupConfigEnabler');
    $this->alterInfo('group_config_info');
    $this->setCacheBackend($cache_backend, 'group_config_enablers');
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginGroupConfigTypeMapCacheKey = $this->cacheKey . '_GCT_map';
    $this->groupTypePluginMapCacheKey = $this->cacheKey . '_GT_map';
  }

  /**
   * Returns the group type storage handler.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected function getGroupTypeStorage() {
    if (!isset($this->groupTypeStorage)) {
      $this->groupTypeStorage = $this->entityTypeManager->getStorage('group_type');
    }
    return $this->groupTypeStorage;
  }

  /**
   * Returns the group config type storage handler.
   *
   * @return \Drupal\group\Entity\Storage\GroupConfigTypeStorageInterface
   */
  protected function getGroupConfigTypeStorage() {
    if (!isset($this->groupConfigTypeStorage)) {
      $this->groupConfigTypeStorage = $this->entityTypeManager->getStorage('group_config_type');
    }
    return $this->groupConfigTypeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    if (!isset($this->allPlugins)) {
      $collection = new GroupConfigEnablerCollection($this, []);

      // Add every known plugin to the collection with a vanilla configuration.
      foreach ($this->getDefinitions() as $plugin_id => $plugin_info) {
        $collection->setInstanceConfiguration($plugin_id, ['id' => $plugin_id]);
      }

      // Sort and set the plugin collection.
      $this->allPlugins = $collection->sort();
    }

    return $this->allPlugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalled(GroupTypeInterface $group_type = NULL) {
    return !isset($group_type)
      ? $this->getVanillaInstalled()
      : $this->getGroupTypeInstalled($group_type);
  }

  /**
   * Retrieves a vanilla instance of every installed plugin.
   *
   * @return \Drupal\group\Plugin\GroupConfigEnablerCollection
   *   A plugin collection with a vanilla instance of every installed plugin.
   */
  protected function getVanillaInstalled() {
    // Retrieve a vanilla instance of all known config enabler plugins.
    $plugins = clone $this->getAll();

    // Retrieve all installed config enabler plugin IDs.
    $installed = $this->getInstalledIds();

    // Remove uninstalled plugins from the collection.
    /** @var \Drupal\group\Plugin\GroupConfigEnablerCollection $plugins */
    foreach ($plugins as $plugin_id => $plugin) {
      if (!in_array($plugin_id, $installed)) {
        $plugins->removeInstanceId($plugin_id);
      }
    }

    return $plugins;
  }

  /**
   * Retrieves fully instantiated plugins for a group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to instantiate the installed plugins for.
   *
   * @return \Drupal\group\Plugin\GroupConfigEnablerCollection
   *   A plugin collection with fully instantiated plugins for the group type.
   */
  protected function getGroupTypeInstalled(GroupTypeInterface $group_type) {
    if (!isset($this->groupTypeInstalled[$group_type->id()])) {
      $configurations = [];
      $group_config_types = $this->getGroupConfigTypeStorage()->loadByGroupType($group_type);

      // Get the plugin config from every group config type for the group type.
      foreach ($group_config_types as $group_config_type) {
        $plugin_id = $group_config_type->getConfigPluginId();

        // Grab the plugin config from every group config type and amend it
        // with the group type ID so the plugin knows what group type to use. We
        // also specify the 'id' key because DefaultLazyPluginCollection throws
        // an exception if it is not present.
        $configuration = $group_config_type->get('plugin_config');
        $configuration['group_type_id'] = $group_type->id();
        $configuration['id'] = $plugin_id;

        $configurations[$plugin_id] = $configuration;
      }

      $plugins = new GroupConfigEnablerCollection($this, $configurations);
      $plugins->sort();

      $this->groupTypeInstalled[$group_type->id()] = $plugins;
    }

    return $this->groupTypeInstalled[$group_type->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getInstalledIds(GroupTypeInterface $group_type = NULL) {
    // If no group type was provided, we can find all installed plugin IDs by
    // grabbing the keys from the group config type IDs per plugin ID map.
    if (!isset($group_type)) {
      return array_keys($this->getPluginGroupConfigTypeMap());
    }

    // Otherwise, we can find the entry in the plugin IDs per group type ID map.
    $map = $this->getGroupTypePluginMap();
    return isset($map[$group_type->id()]) ? $map[$group_type->id()] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedInstalledIds() {
    $this->clearCachedPluginMaps();
  }

  /**
   * {@inheritdoc}
   */
  public function installEnforced(GroupTypeInterface $group_type = NULL) {
    $enforced = [];

    // Gather the ID of all plugins that are marked as enforced.
    foreach ($this->getDefinitions() as $plugin_id => $plugin_info) {
      if ($plugin_info['enforced']) {
        $enforced[] = $plugin_id;
      }
    }

    // If no group type was specified, we check all of them.
    /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
    $group_types = empty($group_type) ? $this->getGroupTypeStorage()->loadMultiple() : [$group_type];

    // Search through all of the enforced plugins and install new ones.
    foreach ($group_types as $group_type) {
      $installed = $this->getInstalledIds($group_type);

      foreach ($enforced as $plugin_id) {
        if (!in_array($plugin_id, $installed)) {
          $this->getGroupConfigTypeStorage()->createFromPlugin($group_type, $plugin_id)->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupConfigTypeIds($plugin_id) {
    $map = $this->getPluginGroupConfigTypeMap();
    return isset($map[$plugin_id]) ? $map[$plugin_id] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginGroupConfigTypeMap() {
    $map = $this->getCachedPluginGroupConfigTypeMap();

    if (!isset($map)) {
      $map = [];

      /** @var \Drupal\group\Entity\GroupConfigTypeInterface[] $group_config_types */
      $group_config_types = $this->getGroupConfigTypeStorage()->loadMultiple();
      foreach ($group_config_types as $group_config_type) {
        $map[$group_config_type->getConfigPluginId()][] = $group_config_type->id();
      }

      $this->setCachedPluginGroupConfigTypeMap($map);
    }

    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedGroupConfigTypeIdMap() {
    $this->clearCachedPluginMaps();
  }

  /**
   * Returns the cached group config type ID map.
   *
   * @return array|null
   *   On success this will return the group config ID map (array). On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array.
   */
  protected function getCachedPluginGroupConfigTypeMap() {
    if (!isset($this->pluginGroupConfigTypeMap) && $cache = $this->cacheGet($this->pluginGroupConfigTypeMapCacheKey)) {
      $this->pluginGroupConfigTypeMap = $cache->data;
    }
    return $this->pluginGroupConfigTypeMap;
  }

  /**
   * Sets a cache of the group config type ID map.
   *
   * @param array $map
   *   The group config type ID map to store in cache.
   */
  protected function setCachedPluginGroupConfigTypeMap($map) {
    $this->cacheSet($this->pluginGroupConfigTypeMapCacheKey, $map, Cache::PERMANENT);
    $this->pluginGroupConfigTypeMap = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypePluginMap() {
    $map = $this->getCachedGroupTypePluginMap();

    if (!isset($map)) {
      $map = [];

      /** @var \Drupal\group\Entity\GroupConfigTypeInterface[] $group_config_types */
      $group_config_types = $this->getGroupConfigTypeStorage()->loadMultiple();
      foreach ($group_config_types as $group_config_type) {
        $map[$group_config_type->getGroupTypeId()][] = $group_config_type->getConfigPluginId();
      }

      $this->setCachedGroupTypePluginMap($map);
    }

    return $map;
  }

  /**
   * Returns the cached group type plugin map.
   *
   * @return array|null
   *   On success this will return the group type plugin map (array). On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array.
   */
  protected function getCachedGroupTypePluginMap() {
    if (!isset($this->groupTypePluginMap) && $cache = $this->cacheGet($this->groupTypePluginMapCacheKey)) {
      $this->groupTypePluginMap = $cache->data;
    }
    return $this->groupTypePluginMap;
  }

  /**
   * Sets a cache of the group type plugin map.
   *
   * @param array $map
   *   The group type plugin map to store in cache.
   */
  protected function setCachedGroupTypePluginMap($map) {
    $this->cacheSet($this->groupTypePluginMapCacheKey, $map, Cache::PERMANENT);
    $this->groupTypePluginMap = $map;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedGroupTypeCollections(GroupTypeInterface $group_type = NULL) {
    if (!isset($group_type)) {
      $this->groupTypeInstalled = [];
    }
    else {
      $this->groupTypeInstalled[$group_type->id()] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedPluginMaps() {
    if ($this->cacheBackend) {
      $this->cacheBackend->delete($this->pluginGroupConfigTypeMapCacheKey);
      $this->cacheBackend->delete($this->groupTypePluginMapCacheKey);
    }
    $this->pluginGroupConfigTypeMap = NULL;
    $this->groupTypePluginMap = NULL;

    // Also clear the array of per group type plugin collections as it shares
    // its cache clearing requirements with the group type plugin map.
    $this->groupTypeInstalled = [];
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();

    // The collection of all plugins should only change if the plugin
    // definitions change, so we can safely reset that here.
    $this->allPlugins = NULL;
  }

}
