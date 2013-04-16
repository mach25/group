<?php
/**
 * @file
 * Defines the Entity API UI class for group roles.
 */

/**
 * UI class for group roles.
 */
class GroupRoleUIController extends EntityDefaultUIController {

  /**
   * Class constructor.
   */
  public function __construct($entity_type, $entity_info) {
    parent::__construct($entity_type, $entity_info);
  }

  /**
   * Provides definitions for implementing hook_menu().
   */
  public function hook_menu() {
    $items = parent::hook_menu();

    $items[$this->path]['type'] = MENU_LOCAL_TASK;
    $items[$this->path]['weight'] = 0;

    $items["$this->path/manage/%entity_object/permissions"] = array(
      'title' => 'Permissions',
      'description' => 'Edit role permissions',
      'page callback' => 'drupal_get_form',
      'page arguments' => array('group_permission_form', 4),
      'load arguments' => array($this->entityType),
      //@todo Check this.
      'access callback' => 'user_access',
      'access arguments' => array('administer group types'),
      'file' => 'admin/group_role.inc',
      'file path' => drupal_get_path('module', 'group'),
      'type' => MENU_LOCAL_TASK,
      'weight' => 5,
    );

    return $items;
  }

  //@todo Show a table of roles per group type.

  /**
   * Returns the operation count for calculating colspans.
   */
  protected function operationCount() {
    return parent::operationCount() + 1;
  }

  /**
   * Generates the row for the passed entity and may be overridden in order to
   * customize the rows.
   *
   * @param $additional_cols
   * Additional columns to be added after the entity label column.
   */
  protected function overviewTableRow($conditions, $id, $entity, $additional_cols = array()) {
    $row = parent::overviewTableRow($conditions, $id, $entity, $additional_cols);
    $new = array(
      l(t('permissions'), $this->path . '/manage/' . $id . '/permissions')
    );

    array_splice($row, 3, 0, $new);

    return $row;
  }
}