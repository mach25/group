<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines the storage handler class for group config entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group config entities based on group and plugin information.
 */
class GroupConfigStorage extends SqlContentEntityStorage implements GroupConfigStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function createForEntityInGroup(EntityInterface $entity, GroupInterface $group, $plugin_id, $values = []) {
    // An unsaved entity cannot have any group config.
    if ($entity->id() === NULL) {
      throw new EntityStorageException("Cannot add an unsaved entity to a group.");
    }

    // An unsaved group cannot have any config.
    if ($group->id() === NULL) {
      throw new EntityStorageException("Cannot add an entity to an unsaved group.");
    }

    // Check whether the entity can actually be added to the group.
    $plugin = $group->getGroupType()->getConfigPlugin($plugin_id);
    if ($entity->getEntityTypeId() != $plugin->getEntityTypeId()) {
      throw new EntityStorageException("Invalid plugin provided for adding the entity to the group.");
    }

    // Verify the bundle as well if the plugin is specific about them.
    $supported_bundle = $plugin->getEntityBundle();
    if ($supported_bundle !== FALSE) {
      if ($entity->bundle() != $supported_bundle) {
        throw new EntityStorageException("The provided plugin provided does not support the entity's bundle.");
      }
    }

    // Set the necessary keys for a valid GroupConfig entity.
    $keys = [
      'type' => $plugin->getConfigTypeConfigId(),
      'gid' => $group->id(),
      'entity_id' => $entity->id(),
    ];

    // Return an unsaved GroupConfig entity.
    return $this->create($keys + $values);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group, $plugin_id = NULL, $filters = []) {
    // An unsaved group cannot have any config.
    if ($group->id() === NULL) {
      throw new EntityStorageException("Cannot load GroupConfig entities for an unsaved group.");
    }

    $properties = ['gid' => $group->id()] + $filters;

    // If a plugin ID was provided, set the group config type ID for it.
    if (isset($plugin_id)) {
      /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
      $plugin = $group->getGroupType()->getConfigPlugin($plugin_id);
      $properties['type'] = $plugin->getConfigTypeConfigId();
    }

    return $this->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntity(EntityInterface $entity) {
    // An unsaved entity cannot have any group config.
    if ($entity->id() === NULL) {
      throw new EntityStorageException("Cannot load GroupConfig entities for an unsaved entity.");
    }

    // If no responsible group config types were found, we return nothing.
    /** @var \Drupal\group\Entity\Storage\GroupConfigTypeStorageInterface $storage */
    $storage = $this->entityManager->getStorage('group_config_type');
    $group_config_types = $storage->loadByEntityTypeId($entity->getEntityTypeId());
    if (empty($group_config_types)) {
      return [];
    }

    return $this->loadByProperties([
      'type' => array_keys($group_config_types),
      'entity_id' => $entity->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadByConfigPluginId($plugin_id) {
    // If no responsible group config types were found, we return nothing.
    /** @var \Drupal\group\Entity\Storage\GroupConfigTypeStorageInterface $storage */
    $storage = $this->entityManager->getStorage('group_config_type');
    $group_config_types = $storage->loadByConfigPluginId($plugin_id);
    if (empty($group_config_types)) {
      return [];
    }

    return $this->loadByProperties(['type' => array_keys($group_config_types)]);
  }

}
