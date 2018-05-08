<?php

namespace Drupal\group\Entity;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Group config entity.
 *
 * @ingroup group
 */
interface GroupConfigInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Returns the group config type entity the group config uses.
   *
   * @return \Drupal\group\Entity\GroupConfigTypeInterface
   */
  public function getGroupConfigType();

  /**
   * Returns the group the group config belongs to.
   *
   * @return \Drupal\group\Entity\GroupInterface
   */
  public function getGroup();

  /**
   * Returns the entity that was added as group config.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * Returns the config enabler plugin that handles the group config.
   *
   * @return \Drupal\group\Plugin\GroupConfigEnablerInterface
   */
  public function getConfigPlugin();

  /**
   * Loads group config entities by their responsible plugin ID.
   *
   * @param string $plugin_id
   *   The ID of the config enabler plugin.
   *
   * @return \Drupal\group\Entity\GroupConfigInterface[]
   *   An array of group config entities indexed by their IDs.
   */
  public static function loadByConfigPluginId($plugin_id);

  /**
   * Loads group config entities which reference a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity which may be within one or more groups.
   *
   * @return \Drupal\group\Entity\GroupConfigInterface[]
   *   An array of group config entities which reference the given entity.
   */
  public static function loadByEntity(ContentEntityInterface $entity);

}
