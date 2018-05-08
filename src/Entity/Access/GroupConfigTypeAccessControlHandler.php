<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the group config type entity type.
 *
 * @see \Drupal\group\Entity\GroupConfigType
 */
class GroupConfigTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupConfigTypeInterface $entity */
    if ($operation == 'delete') {
      return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
