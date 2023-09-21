<?php

namespace Drupal\stanford_actions\Plugin\Action;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_actions\Events\NodeCloneEvent;
use Drupal\stanford_actions\Events\StanfordActionsEvents;
use Drupal\stanford_actions\Plugin\Action\FieldClone\FieldCloneInterface;
use Drupal\stanford_actions\Plugin\FieldCloneManagerInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Clones a node.
 *
 * @Action(
 *   id = "node_clone_action",
 *   label = @Translation("Clone selected content"),
 *   type = "node"
 * )
 */
class CloneNode extends ViewsBulkOperationsActionBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.stanford_actions_field_clone'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Plugin constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity Field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\stanford_actions\Plugin\FieldCloneManagerInterface $fieldCloneManager
   *   Field clone plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current active user.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   Event dispatcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityFieldManagerInterface $entityFieldManager, protected EntityTypeManagerInterface $entityTypeManager, protected FieldCloneManagerInterface $fieldCloneManager, ConfigFactoryInterface $config_factory, protected AccountProxyInterface $currentUser, protected EventDispatcherInterface $eventDispatcher) {
    $clone_entities = $config_factory->get('stanford_actions.settings')
      ->get('actions.node_clone_action.clone_entities');
    $configuration['clone_entities'] = $clone_entities ?? [];

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'clone_entities' => [],
      'clone_count' => 1,
      'prepend_title' => '',
      'field_clone' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $values = range(1, 10);
    $form['clone_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Clone how many times'),
      '#options' => array_combine($values, $values),
    ];
    $form['prepend_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prepend Cloned Titles'),
      '#default_value' => 'Clone',
    ];

    foreach ($this->context['list'] as $item) {
      $node_ids[] = $item[0];
    }

    // Load all nodes that are being cloned.
    $nodes = $this->entityTypeManager->getStorage('node')
      ->loadMultiple($node_ids);

    $form['field_clone'] = [
      '#type' => 'details',
      '#title' => $this->t('Adjust Cloned Field Values'),
      '#tree' => TRUE,
    ];

    // Add field clone fields to the form.
    foreach ($nodes as $node) {
      $this->buildFieldCloneForm($form, $form_state, $node);
    }

    // If no plugins add to the form, don't show the detail element.
    $form['field_clone']['#access'] = !empty(Element::children($form['field_clone']));

    return $form;
  }

  /**
   * Build the field clone form for the provided entity.
   *
   * @param array $form
   *   Complete Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $node
   *   Entity to be cloned.
   */
  protected function buildFieldCloneForm(array &$form, FormStateInterface $form_state, FieldableEntityInterface $node) {
    $field_clone_plugins = $this->getFieldClonePlugins();

    $fields = $this->entityFieldManager->getFieldDefinitions('node', $node->bundle());
    foreach ($fields as $field) {
      foreach ($field_clone_plugins as $plugin) {
        $plugin_definition = $plugin->getPluginDefinition();
        if (in_array($field->getType(), $plugin_definition['fieldTypes'])) {
          $form['field_clone'][$plugin_definition['id']][$field->getName()] = [
            '#type' => 'details',
            '#title' => $field->getLabel(),
            '#description' => $plugin_definition['description'] ?? '',
            '#open' => TRUE,
          ];

          $form['field_clone'][$plugin_definition['id']][$field->getName()] += $plugin->buildConfigurationForm($form, $form_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach ($this->getFieldClonePlugins() as $plugin) {
      $plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    foreach ($this->getFieldClonePlugins() as $plugin) {
      $plugin->submitConfigurationForm($form, $form_state);
    }
    $this->configuration['prepend_title'] = $form_state->getValue('prepend_title');
    $this->configuration['clone_count'] = $form_state->getValue('clone_count');
    $this->configuration['field_clone'] = $form_state->getValue('field_clone', []);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE)
      ->andIf($object->access('create', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (!isset($this->configuration['clone_count'])) {
      $this->configuration['clone_count'] = 1;
    }
    $duplicate_node = $entity;
    $original_title = $entity->label();
    for ($i = 0; $i < $this->configuration['clone_count']; $i++) {
      /** @var \Drupal\node\NodeInterface $duplicate_node */
      $duplicate_node = $this->duplicateEntity($duplicate_node);
      $this->adjustNodeTitle($duplicate_node, $original_title);
      $duplicate_node->setUnpublished();
      $duplicate_node->set('uid', $this->currentUser->id());
      $duplicate_node->set('created', time());
      $duplicate_node->set('changed', time());

      $event = new NodeCloneEvent($duplicate_node, $entity);
      $this->eventDispatcher->dispatch($event, StanfordActionsEvents::PRE_NODE_CLONED);
      $duplicate_node->save();

      $event = new NodeCloneEvent($duplicate_node, $entity);
      $this->eventDispatcher->dispatch($event, StanfordActionsEvents::POST_NODE_CLONED);
    }
  }

  /**
   * Modify the node title if configured to prepend a string.
   *
   * @param \Drupal\node\NodeInterface $node
   *   New node entity.
   */
  protected function adjustNodeTitle(NodeInterface $node, $original_title) {
    if ($this->configuration['prepend_title'] ?? FALSE) {
      $new_title = trim($this->configuration['prepend_title']) . ' ' . $original_title;

      // Make sure the new title will fit in the database.
      if (strlen($new_title) <= 255) {
        $node->set('title', $new_title);
      }
    }
  }

  /**
   * Recursively clone an entity and any dependent entities in reference fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to clone.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Cloned entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function duplicateEntity(ContentEntityInterface $entity): ContentEntityInterface {
    $duplicate_entity = $entity->createDuplicate();
    $duplicate_entity->enforceIsNew();

    // Loop through paragraph and eck fields to clone those entities.
    foreach ($this->getReferenceFields($entity->getEntityTypeId(), $entity->bundle()) as $field) {
      /** @var \Drupal\Core\Field\FieldItemInterface $value */
      foreach ($duplicate_entity->get($field->getName()) as $value) {
        $value->entity = $this->duplicateEntity($value->entity);
      }
    }

    foreach ($this->configuration['field_clone'] as $plugin_id => $fields) {
      foreach ($fields as $field_name => $field_changes) {
        $plugin = $this->getFieldClonePlugin($plugin_id, $field_changes);
        $plugin->alterFieldValue($entity, $duplicate_entity, $field_name);
      }
    }

    return $duplicate_entity;
  }

  /**
   * Get all the field clone plugins available.
   *
   * @return \Drupal\stanford_actions\Plugin\Action\FieldClone\FieldCloneInterface[]
   *   Keyed array of plugins.
   */
  protected function getFieldClonePlugins(): array {
    if (empty($this->fieldClonePlugins)) {
      foreach ($this->fieldCloneManager->getDefinitions() as $plugin_definition) {
        $this->fieldClonePlugins[$plugin_definition['id']] = $this->getFieldClonePlugin($plugin_definition['id']);
      }
    }
    return $this->fieldClonePlugins;
  }

  /**
   * Create the single plugin object.
   *
   * @param string $plugin_id
   *   Plugin ID.
   * @param array $config
   *   Plugin configuration.
   *
   * @return \Drupal\stanford_actions\Plugin\Action\FieldClone\FieldCloneInterface
   *   Plugin object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getFieldClonePlugin($plugin_id, array $config = []): FieldCloneInterface {
    return $this->fieldCloneManager->createInstance($plugin_id, $config);
  }

  /**
   * Get fields that need to have their referenced entities cloned.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return \Drupal\field\Entity\FieldConfig[]
   *   Array of fields that need cloned values.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getReferenceFields($entity_type_id, $bundle): array {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $clone_target_types = $this->configuration['clone_entities'];

    if ($this->entityTypeManager->hasDefinition('eck_entity_type')) {
      $eck_types = $this->entityTypeManager->getStorage('eck_entity_type')
        ->loadMultiple();
      $clone_target_types = array_merge($clone_target_types, array_keys($eck_types));
    }

    // Filter out fields that we dont care about. We only need entity reference
    // fields that are not base fields. Also we only want entity reference
    // fields that target specific entity types as defined above that require
    // cloning..
    return array_filter($fields, function($field) use ($clone_target_types) {
      $target_entity_id = $field->getFieldStorageDefinition()
        ->getSetting('target_type');
      $types = ['entity_reference', 'entity_reference_revisions'];

      return $field instanceof FieldConfigInterface && in_array($field->getType(), $types) && in_array($target_entity_id, $clone_target_types);
    });
  }

}
