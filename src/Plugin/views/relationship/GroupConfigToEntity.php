<?php

namespace Drupal\group\Plugin\views\relationship;

/**
 * A relationship handler for group config entity references.
 *
 * Definition items:
 * - target_entity_type: The ID of the entity type this relationship maps to.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_config_to_entity")
 */
class GroupConfigToEntity extends GroupConfigToEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityType() {
    return $this->definition['target_entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getJoinFieldType() {
    return 'left_field';
  }

}
