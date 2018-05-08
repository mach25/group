<?php

namespace Drupal\group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access for group config target entity creation.
 */
class GroupConfigCreateAnyEntityAccessCheck implements AccessInterface {

  /**
   * Checks access for group config target entity creation routes.
   *
   * All routes using this access check should have a group parameter and have
   * the _group_config_create_any_entity_access requirement set to 'TRUE' or
   * 'FALSE'.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group in which the config should be created.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account, GroupInterface $group) {
    $needs_access = $route->getRequirement('_group_config_create_any_entity_access') === 'TRUE';

    // Retrieve all of the group config plugins for the group.
    $plugins = $group->getGroupType()->getInstalledConfigPlugins();

    // Find out which ones allow the user to create a target entity.
    foreach ($plugins as $plugin) {
      /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
      if ($plugin->createEntityAccess($group, $account)->isAllowed()) {
        // Allow access if the route flag was set to 'TRUE'.
        return AccessResult::allowedIf($needs_access);
      }
    }

    // If we got this far, it means the user could not create any config in the
    // group. So only allow access if the route flag was set to 'FALSE'.
    return AccessResult::allowedIf(!$needs_access);
  }

}
