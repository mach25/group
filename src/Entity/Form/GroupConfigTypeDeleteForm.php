<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for group config type deletion.
 *
 * Instead of just deleting the group config type here, we use this form as a
 * mean of uninstalling a group config enabler plugin which will actually
 * trigger the deletion of the group config type.
 */
class GroupConfigTypeDeleteForm extends EntityDeleteForm {

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new GroupConfigTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query object.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\group\Entity\GroupConfigTypeInterface $group_config_type */
    $group_config_type = $this->getEntity();
    return $this->t('Are you sure you want to uninstall the %plugin plugin?', ['%plugin' => $group_config_type->getConfigPlugin()->getLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\group\Entity\GroupConfigTypeInterface $group_config_type */
    $group_config_type = $this->getEntity();
    return Url::fromRoute('entity.group_type.config_plugins', ['group_type' => $group_config_type->getGroupTypeId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\group\Entity\GroupConfigTypeInterface $group_config_type */
    $group_config_type = $this->getEntity();
    $plugin = $group_config_type->getConfigPlugin();
    $replace = [
      '%entity_type' => $this->entityTypeManager->getDefinition($plugin->getEntityTypeId())->getLabel(),
      '%group_type' => $group_config_type->getGroupType()->label(),
    ];
    return $this->t('You will no longer be able to add %entity_type entities to %group_type groups.', $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_count = $this->queryFactory->get('group_config')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if (!empty($entity_count)) {
      $form['#title'] = $this->getQuestion();
      $form['description'] = [
        '#markup' => '<p>' . $this->t('You can not uninstall this config plugin until you have removed all of the config that uses it.') . '</p>',
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\group\Entity\GroupConfigTypeInterface $group_config_type */
    $group_config_type = $this->getEntity();
    $group_type = $group_config_type->getGroupType();
    $plugin = $group_config_type->getConfigPlugin();

    $group_config_type->delete();
    \Drupal::logger('group_config_type')->notice('Uninstalled %plugin from %group_type.', [
      '%plugin' => $plugin->getLabel(),
      '%group_type' => $group_type->label(),
    ]);

    $form_state->setRedirect('entity.group_type.config_plugins', ['group_type' => $group_type->id()]);
    drupal_set_message($this->t('The config plugin was uninstalled from the group type.'));
  }

}
