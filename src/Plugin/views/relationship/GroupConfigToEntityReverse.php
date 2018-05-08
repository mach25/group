<?php

namespace Drupal\group\Plugin\views\relationship;

/**
 * A relationship handler which reverses group config entity references.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_config_to_entity_reverse")
 */
class GroupConfigToEntityReverse extends GroupConfigToEntityBase {

  /**
   * {@inheritdoc}
   */
  protected function getTargetEntityType() {
    return $this->definition['entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getJoinFieldType() {
    return 'field';
  }

}
