<?php

namespace Drupal\group\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of group config plugins.
 */
class GroupConfigEnablerCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\group\Plugin\GroupConfigEnablerInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   *
   * Sorts plugins by provider.
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);

    if ($a->getProvider() != $b->getProvider()) {
      return strnatcasecmp($a->getProvider(), $b->getProvider());
    }

    return parent::sortHelper($aID, $bID);
  }

}
