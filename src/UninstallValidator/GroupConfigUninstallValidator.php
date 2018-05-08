<?php

namespace Drupal\group\UninstallValidator;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupConfigType;
use Drupal\group\Plugin\GroupConfigEnablerManagerInterface;

/**
 *
 */
class GroupConfigUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The group config plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupConfigEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupConfigUninstallValidator object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   * @param \Drupal\group\Plugin\GroupConfigEnablerManagerInterface $plugin_manager
   *   The group config plugin manager.
   */
  public function __construct(TranslationInterface $string_translation, QueryFactory $query_factory, GroupConfigEnablerManagerInterface $plugin_manager) {
    $this->stringTranslation = $string_translation;
    $this->queryFactory = $query_factory;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = $plugin_names = [];

    /** @var \Drupal\group\Plugin\GroupConfigEnablerInterface $plugin */
    foreach ($this->pluginManager->getAll() as $plugin_id => $plugin) {
      if ($plugin->getProvider() == $module && $this->hasGroupConfig($plugin_id)) {
        $plugin_names[] = $plugin->getLabel();
      }
    }

    if (!empty($plugin_names)) {
      $reasons[] = $this->t('The following group config plugins still have config for them: %plugins.', ['%plugins' => implode(', ', $plugin_names)]);
    }

    return $reasons;
  }

  /**
   * Determines if there is any group config for a config enabler plugin.
   *
   * @param string $plugin_id
   *   The group config enabler plugin ID to check for group config.
   *
   * @return bool
   *   Whether there are group config entities for the given plugin ID.
   */
  protected function hasGroupConfig($plugin_id) {
    $group_config_types = array_keys(GroupConfigType::loadByConfigPluginId($plugin_id));

    if (empty($group_config_types)) {
      return FALSE;
    }

    $entity_count = $this->queryFactory->get('group_config')
      ->condition('type', $group_config_types, 'IN')
      ->count()
      ->execute();

    return (bool) $entity_count;
  }

}
