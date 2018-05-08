<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for group config entities.
 *
 * @ingroup group
 */
class GroupConfigListBuilder extends EntityListBuilder {

  /**
   * The group to show the config for.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $entityTypeManager;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * Constructs a new GroupConfigListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RedirectDestinationInterface $redirect_destination, RouteMatchInterface $route_match, EntityTypeInterface $entity_type) {
    parent::__construct($entity_type, $entity_type_manager->getStorage($entity_type->id()));
    $this->entityTypeManager = $entity_type_manager;
    $this->redirectDestination = $redirect_destination;
    // There should always be a group on the route for group config lists.
    $this->group = $route_match->getParameters()->get('group');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('redirect.destination'),
      $container->get('current_route_match'),
      $entity_type
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $query->sort($this->entityType->getKey('id'));

    // Only show group config for the group on the route.
    $query->condition('gid', $this->group->id());

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'id' => $this->t('ID'),
      'label' => $this->t('Config label'),
      'entity_type' => $this->t('Entity type'),
      'plugin' => $this->t('Plugin used'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupConfigInterface $entity */
    $row['id'] = $entity->id();

    // EntityListBuilder sets the table rows using the #rows property, so we
    // need to add links as render arrays using the 'data' key.
    $row['label']['data'] = $entity->toLink()->toRenderable();
    $entity_type_id = $entity->getConfigPlugin()->getEntityTypeId();
    $row['entity_type'] = $this->entityTypeManager->getDefinition($entity_type_id)->getLabel();
    $row['plugin'] = $entity->getConfigPlugin()->getLabel();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no config entities related to this group yet.');
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\group\Entity\GroupConfigInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    // Improve the edit and delete operation labels.
    if (isset($operations['edit'])) {
      $operations['edit']['title'] = $this->t('Edit relation');
    }
    if (isset($operations['delete'])) {
      $operations['delete']['title'] = $this->t('Delete relation');
    }

    // Slap on redirect destinations for the administrative operations.
    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    // Add an operation to view the actual entity.
    if ($entity->getEntity()->access('view') && $entity->getEntity()->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View related entity'),
        'weight' => 101,
        'url' => $entity->getEntity()->toUrl('canonical'),
      ];
    }

    return $operations;
  }

}
