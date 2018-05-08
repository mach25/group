<?php

namespace Drupal\group\Access;

use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupConfigInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on whether a piece of group config belongs
 * to the group that was also specified in the route.
 */
class GroupOwnsConfigAccessCheck implements AccessInterface {

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $must_own_config = $route->getRequirement('_group_owns_config') === 'TRUE';

    // Don't interfere if no group or group config was specified.
    $parameters = $route_match->getParameters();
    if (!$parameters->has('group') || !$parameters->has('group_config')) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group isn't a real group.
    $group = $parameters->get('group');
    if (!$group instanceof GroupInterface) {
      return AccessResult::neutral();
    }

    // Don't interfere if the group config isn't a real group config entity.
    $group_config = $parameters->get('group_config');
    if (!$group_config instanceof GroupConfigInterface) {
      return AccessResult::neutral();
    }

    // If we have a group and group config, see if the owner matches.
    $group_owns_config = $group_config->getGroup()->id() == $group->id();

    // Only allow access if the group config is owned by the group and
    // _group_owns_config is set to TRUE or the other way around.
    return AccessResult::allowedIf($group_owns_config xor !$must_own_config);
  }

}
