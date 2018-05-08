<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a group content entity.
 */
class GroupConfigDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Returns the plugin responsible for this piece of group config.
   *
   * @return \Drupal\group\Plugin\GroupConfigEnablerInterface
   *   The responsible group config enabler plugin.
   */
  protected function getConfigPlugin() {
    /** @var \Drupal\group\Entity\GroupConfig $group_config */
    $group_config = $this->getEntity();
    return $group_config->getConfigPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    /** @var \Drupal\group\Entity\GroupConfig $group_config */
    $group_config = $this->getEntity();
    $group = $group_config->getGroup();
    $route_params = [
      'group' => $group->id(),
      'group_config' => $group_config->id(),
    ];
    return new Url('entity.group_config.canonical', $route_params);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupConfig $group_config */
    $group_config = $this->getEntity();
    $group = $group_config->getGroup();
    $group_config->delete();

    \Drupal::logger('group_config')->notice('@type: deleted %title.', [
      '@type' => $group_config->bundle(),
      '%title' => $group_config->label(),
    ]);

    $form_state->setRedirect('entity.group.canonical', ['group' => $group->id()]);
  }

}
