<?php

namespace Drupal\gnode\Plugin\GroupConfigEnabler;

use Drupal\group\Plugin\GroupConfigEnablerBase;

/**
 * Provides a content enabler for nodes.
 *
 * @GroupConfigEnabler(
 *   id = "group_node_type",
 *   label = @Translation("Group content type"),
 *   description = @Translation("Adds content type to groups both publicly and privately."),
 *   entity_type_id = "node_type",
 * )
 */
class GroupNodeType extends GroupConfigEnablerBase {
}
