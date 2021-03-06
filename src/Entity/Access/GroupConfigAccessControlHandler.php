<?php

namespace Drupal\group\Entity\Access;

use Drupal\group\Entity\GroupConfigType;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Group entity.
 *
 * @see \Drupal\group\Entity\Group.
 */
class GroupConfigAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\group\Entity\GroupConfigInterface $entity */
    return $entity->getConfigPlugin()->checkAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $group_config_type = GroupConfigType::load($entity_bundle);
    return $group_config_type->getConfigPlugin()->createAccess($context['group'], $account);
  }

}
