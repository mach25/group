<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks the cardinality limits for a piece of group config.
 *
 * Config enabler plugins may limit the amount of times a single config entity
 * can be added to a group as well as the amount of groups that single entity
 * can be added to. This constraint will enforce that behavior.
 *
 * @Constraint(
 *   id = "GroupConfigCardinality",
 *   label = @Translation("Group config cardinality check", context = "Validation"),
 *   type = "entity:group_config"
 * )
 */
class GroupConfigCardinality extends Constraint {

  /**
   * The message to show when an entity has reached the group cardinality.
   *
   * @var string
   */
  public $groupMessage = '@field: %config has reached the maximum amount of groups it can be added to';

  /**
   * The message to show when an entity has reached the entity cardinality.
   *
   * @var string
   */
  public $entityMessage = '@field: %config has reached the maximum amount of times it can be added to %group';

}
