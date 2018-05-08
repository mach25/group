<?php

namespace Drupal\group\Plugin\views\relationship;

use Drupal\Core\Form\FormStateInterface;
use Drupal\group\Entity\GroupConfigType;
use Drupal\group\Plugin\GroupConfigEnablerManagerInterface;
use Drupal\views\Plugin\views\relationship\RelationshipPluginBase;
use Drupal\views\Plugin\ViewsHandlerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A relationship handler for group config.
 *
 * @ingroup views_relationship_handlers
 *
 * @ViewsRelationship("group_to_group_config")
 */
class GroupToGroupConfig extends RelationshipPluginBase {

  /**
   * The Views join plugin manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $joinManager;

  /**
   * The group config enabler plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupConfigEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The group config type IDs to filter the join on.
   *
   * @var string[]
   */
  protected $groupConfigTypeIds;

  /**
   * Constructs a GroupToGroupConfig object.
   *
   * @param \Drupal\views\Plugin\ViewsHandlerManager $join_manager
   *   The views plugin join manager.
   * @param \Drupal\group\Plugin\GroupConfigEnablerManagerInterface $plugin_manager
   *   The group config enabler plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViewsHandlerManager $join_manager, GroupConfigEnablerManagerInterface $plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->joinManager = $join_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.views.join'),
      $container->get('plugin.manager.group_config_enabler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['group_config_plugins']['default'] = [];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['group_config_plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Filter by plugin'),
      '#description' => $this->t('Refine the result by plugin. Leave empty to select all plugins, including those that could be added after this relationship was configured.'),
      '#options' => $this->getConfigPluginOptions(),
      '#weight' => -2,
      '#default_value' => $this->options['group_config_plugins'],
    ];
  }

  /**
   * Builds the options for the config plugin selection.
   *
   * @return string[]
   *   An array of config plugin labels, keyed by plugin ID.
   */
  protected function getConfigPluginOptions() {
    $options = [];
    foreach ($this->pluginManager->getAll() as $plugin_id => $plugin) {
      /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
      $options[$plugin_id] = $plugin->getLabel();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Build the join definition.
    $def = $this->definition;
    $def['table'] = $this->definition['base'];
    $def['field'] = $this->definition['base field'];
    $def['left_table'] = $this->tableAlias;
    $def['left_field'] = $this->realField;
    $def['adjusted'] = TRUE;

    // Change the join to INNER if the relationship is required.
    if (!empty($this->options['required'])) {
      $def['type'] = 'INNER';
    }

    // If there were extra join conditions added in the definition, use them.
    if (!empty($this->definition['extra'])) {
      $def['extra'] = $this->definition['extra'];
    }

    // We can't run an IN-query on an empty array. So if there are no group
    // config types yet, we do not add our extra condition to the JOIN.
    $group_config_type_ids = $this->getGroupConfigTypeIds();
    if (!empty($group_config_type_ids)) {
      $def['extra'][] = [
        'field' => 'type',
        'value' => $group_config_type_ids,
      ];
    }

    // Use the standard join plugin unless instructed otherwise.
    $join_id = !empty($def['join_id']) ? $def['join_id'] : 'standard';
    $join = $this->joinManager->createInstance($join_id, $def);

    // Add the join using a more verbose alias.
    $alias = $def['table'] . '_' . $this->table;
    $this->alias = $this->query->addRelationship($alias, $join, $this->definition['base'], $this->relationship);

    // Add access tags if the base table provides it.
    $table_data = $this->viewsData->get($def['table']);
    if (empty($this->query->options['disable_sql_rewrite']) && isset($table_data['table']['base']['access query tag'])) {
      $access_tag = $table_data['table']['base']['access query tag'];
      $this->query->addTag($access_tag);
    }
  }

  /**
   * Returns the group config types this relationship should filter on.
   *
   * This checks if any plugins were selected on the option form and, in that
   * case, loads only those group config types available to the selected
   * plugins. Otherwise, all possible group config types for the relationship's
   * entity type are loaded.
   *
   * This needs to happen live to cover the use case where a group config
   * plugin is installed on a group type after this relationship has been
   * configured on a view without any plugins selected.
   *
   * @return string[]
   *   The group config type IDs to filter on.
   */
  protected function getGroupConfigTypeIds() {
    // Even though the retrieval needs to happen live, there's nothing stopping
    // us from statically caching it during runtime.
    if (!isset($this->groupConfigTypeIds)) {
      $plugin_ids = array_filter($this->options['group_config_plugins']);

      $group_config_type_ids = [];
      foreach ($plugin_ids as $plugin_id) {
        $group_config_type_ids = array_merge($group_config_type_ids, $this->pluginManager->getGroupConfigTypeIds($plugin_id));
      }

      $this->groupConfigTypeIds = $plugin_ids
        ? $group_config_type_ids
        : array_keys(GroupConfigType::loadMultiple());
    }

    return $this->groupConfigTypeIds;
  }

}
