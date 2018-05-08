<?php

namespace Drupal\group\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a GroupConfigEnabler annotation object.
 *
 * Plugin Namespace: Plugin\GroupConfigEnabler.
 *
 * For a working example, see
 * \Drupal\group\Plugin\GroupConfigEnabler\GroupMembership
 *
 * @see \Drupal\group\Plugin\GroupConfigEnablerInterface
 * @see \Drupal\group\Plugin\GroupConfigEnablerManager
 * @see plugin_api
 *
 * @Annotation
 */
class GroupConfigEnabler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the GroupConfigEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the GroupConfigEnabler plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The ID of the entity type you want to enable as group config.
   *
   * @var string
   */
  public $entity_type_id;

  /**
   * (optional) The bundle of the entity type you want to enable as group config.
   *
   * Do not specify if your plugin manages all bundles.
   *
   * @var string|false
   */
  public $entity_bundle = FALSE;

  /**
   * (optional) Whether the plugin defines entity access.
   *
   * This controls whether you can create entities within the group (TRUE) or
   * only add existing ones (FALSE). It also generates the necessary group
   * permissions when enabled.
   *
   * Eventually, this will even generate entity access records for you, but that
   * will only happen after the patch in https://www.drupal.org/node/777578 has
   * been committed to Drupal core.
   *
   * @var bool
   */
  public $entity_access = FALSE;

  /**
   * (optional) The key to use in automatically generated paths.
   *
   * This is exposed through tokens so modules like Pathauto may use it. Only
   * use this if your plugin has something meaningful to show on the actual
   * group config entity; i.e.: the relationship. Otherwise leave blank so it
   * defaults to 'config'.
   *
   * @var string
   */
  public $pretty_path_key = 'config';

  /**
   * (optional) The label for the entity reference field.
   *
   * @var string
   */
  public $reference_label;

  /**
   * (optional) The description for the entity reference field.
   *
   * @var string
   */
  public $reference_description;

  /**
   * (optional) Whether this plugin is always on.
   *
   * @var bool
   */
  public $enforced = FALSE;

}
