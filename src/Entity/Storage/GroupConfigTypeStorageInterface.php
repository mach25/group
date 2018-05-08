<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Defines an interface for group config type entity storage classes.
 */
interface GroupConfigTypeStorageInterface extends ConfigEntityStorageInterface {

  /**
   * Retrieves all group config types for a group type.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to load the group config types for.
   *
   * @return \Drupal\group\Entity\GroupConfigTypeInterface[]
   *   An array of group config types indexed by their IDs.
   */
  public function loadByGroupType(GroupTypeInterface $group_type);

  /**
   * Retrieves group config types by their responsible plugin ID.
   *
   * @param string|string[] $plugin_id
   *   The ID of the config enabler plugin or an array of plugin IDs. If more
   *   than one plugin ID is provided, this will load all of the group config
   *   types that match any of the provided plugin IDs.
   *
   * @return \Drupal\group\Entity\GroupConfigTypeInterface[]
   *   An array of group config types indexed by their IDs.
   */
  public function loadByConfigPluginId($plugin_id);

  /**
   * Retrieves group config types which could serve a given entity type.
   *
   * @param string $entity_type_id
   *   An entity type ID which may be served by one or more group config types.
   *
   * @return \Drupal\group\Entity\GroupConfigTypeInterface[]
   *   An array of group config types indexed by their IDs.
   */
  public function loadByEntityTypeId($entity_type_id);

  /**
   * Creates a group config type for a group type using a specific plugin.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to create the group config type for.
   * @param string $plugin_id
   *   The ID of the config enabler plugin to use.
   * @param array $configuration
   *   (optional) An array of config enabler plugin configuration.
   *
   * @return \Drupal\group\Entity\GroupConfigTypeInterface
   *   A new, unsaved GroupConfigType entity.
   */
  public function createFromPlugin(GroupTypeInterface $group_type, $plugin_id, array $configuration = []);

}
