<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface defining a group config type entity.
 */
interface GroupConfigTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Gets the group type the config type was created for.
   *
   * @return \Drupal\group\Entity\GroupTypeInterface
   *   The group type for which the config type was created.
   */
  public function getGroupType();

  /**
   * Gets the group type ID the config type was created for.
   *
   * @return string
   *   The group type ID for which the config type was created.
   */
  public function getGroupTypeId();

  /**
   * Gets the config enabler plugin the config type uses.
   *
   * @return \Drupal\group\Plugin\GroupConfigEnablerInterface
   *   The config enabler plugin the config type uses.
   */
  public function getConfigPlugin();

  /**
   * Gets the config enabler plugin ID the config type uses.
   *
   * @return string
   *   The config enabler plugin ID the config type uses.
   */
  public function getConfigPluginId();

  /**
   * Updates the configuration of the config enabler plugin.
   *
   * Any keys that were left out will be reset to the default.
   *
   * @param array $configuration
   *   An array of config enabler plugin configuration.
   */
  public function updateConfigPlugin(array $configuration);

  /**
   * Loads group config type entities by their responsible plugin ID.
   *
   * @param string|string[] $plugin_id
   *   The ID of the config enabler plugin or an array of plugin IDs. If more
   *   than one plugin ID is provided, this will load all of the group config
   *   types that match any of the provided plugin IDs.
   *
   * @return \Drupal\group\Entity\GroupConfigTypeInterface[]
   *   An array of group config type entities indexed by their IDs.
   */
  public static function loadByConfigPluginId($plugin_id);

  /**
   * Loads group config type entities which could serve a given entity type.
   *
   * @param string $entity_type_id
   *   An entity type ID which may be served by one or more group config types.
   *
   * @return \Drupal\group\Entity\GroupConfigTypeInterface[]
   *   An array of group config type entities which serve the given entity.
   */
  public static function loadByEntityTypeId($entity_type_id);

}
