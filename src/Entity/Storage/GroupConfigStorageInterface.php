<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines an interface for group config entity storage classes.
 */
interface GroupConfigStorageInterface extends ContentEntityStorageInterface {

  /**
   * Creates a GroupConfig entity for placing a config entity in a group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The config entity to add to the group.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the config entity to.
   * @param string $plugin_id
   *   The ID of the config enabler plugin to add the entity with.
   * @param array $values
   *   (optional) Extra values to add to the GroupConfig entity.
   *
   * @return \Drupal\group\Entity\GroupConfigInterface
   *   A new GroupConfig entity.
   */
  public function createForEntityInGroup(EntityInterface $entity, GroupInterface $group, $plugin_id, $values = []);

  /**
   * Retrieves all GroupConfig entities for a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity to load the group config entities for.
   * @param string $plugin_id
   *   (optional) A config enabler plugin ID to filter on.
   * @param array $filters
   *   (optional) An associative array of extra filters where the keys are
   *   property or field names and the values are the value to filter on.
   *
   * @return \Drupal\group\Entity\GroupConfigInterface[]
   *   A list of GroupConfig entities matching the criteria.
   */
  public function loadByGroup(GroupInterface $group, $plugin_id = NULL, $filters = []);

  /**
   * Retrieves all GroupConfig entities that represent a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   *
   * @return \Drupal\group\Entity\GroupConfigInterface[]
   *   A list of GroupConfig entities which refer to the given entity.
   */
  public function loadByEntity(EntityInterface $entity);

  /**
   * Retrieves all GroupConfig entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the config enabler plugin.
   *
   * @return \Drupal\group\Entity\GroupConfigInterface[]
   *   A list of GroupConfig entities indexed by their IDs.
   */
  public function loadByConfigPluginId($plugin_id);

}
