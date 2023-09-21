<?php

namespace Drupal\Tests\stanford_actions\Kernel\Plugin\Action;

use Drupal\Core\Form\FormState;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\stanford_actions\Plugin\Action\CloneNode;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Class TestCloneNode.
 *
 * @coversDefaultClass \Drupal\stanford_actions\Plugin\Action\CloneNode
 * @group stanford_actions
 */
class CloneNodeTest extends KernelTestBase {

  /**
   * Node object to clone.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'user',
    'stanford_actions',
    'field',
    'datetime',
    'paragraphs',
    'entity_reference_revisions',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('field_config');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('paragraph');

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => strtolower($this->randomMachineName()),
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_DATE],
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $field->save();

    // Create a field with paragraphs for the node type.
    $paragraph_field = FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'field_name' => strtolower($this->randomMachineName()),
      'settings' => [
        'target_type' => 'paragraph',
      ],
      'cardinality' => -1,
      'translatable' => TRUE,
    ]);
    $paragraph_field->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => $paragraph_field->getName(),
      'label' => $this->randomString(),
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'negate' => 1,
          'target_bundles' => NULL,
          'target_bundles_drag_drop' => [],
        ],
      ],
    ])->save();

    ParagraphsType::create([
      'id' => 'layout',
      'label' => 'layout',
    ])->save();
    ParagraphsType::create([
      'id' => 'text',
      'label' => 'text',
    ])->save();
    $layout = Paragraph::create(['type' => 'layout']);
    $layout->save();
    $text = Paragraph::create(['type' => 'text']);
    $text->setAllBehaviorSettings(['layout_paragraphs' => ['parent_uuid' => $layout->uuid()]]);
    $text->save();

    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'page',
      $field_storage->getName() => date('Y-m-d'),
      $paragraph_field->getName() => [
        [
          'target_id' => $layout->id(),
          'target_revision_id' => $layout->getRevisionId(),
        ],
        [
          'target_id' => $text->id(),
          'target_revision_id' => $text->getRevisionId(),
        ],
      ],
    ]);
    $this->node->save();
  }

  /**
   * Test the action methods.
   */
  public function testAction() {
    $this->assertEquals(1, $this->getNodeCount());

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\stanford_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $this->assertEquals(CloneNode::class, get_class($action));

    // Simple methods.
    $this->assertArrayHasKey('clone_entities', $action->defaultConfiguration());
    $this->assertArrayHasKey('clone_count', $action->defaultConfiguration());
    $this->assertArrayHasKey('field_clone', $action->defaultConfiguration());

    $form = [];
    $form_state = new FormState();
    $context = ['list' => [$this->node->id()]];
    $action->setContext($context);
    $this->assertGreaterThanOrEqual(2, $action->buildConfigurationForm($form, $form_state));
    $this->assertArrayHasKey('clone_count', $action->buildConfigurationForm($form, $form_state));

    $form_state->setValue('clone_count', 7);
    $action->validateConfigurationForm($form, $form_state);
    $action->submitConfigurationForm($form, $form_state);
    $this->assertEquals(7, $action->getConfiguration()['clone_count']);

    $action->execute($this->node);
    $this->assertEquals(8, $this->getNodeCount());

    $form_state->setValue('clone_count', 1);
    $form_state->setValue('prepend_title', 'foo bar');
    $action->submitConfigurationForm($form, $form_state);
    $action->execute($this->node);
    $cloned = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => 'foo bar ' . $this->node->getTitle()]);
    $this->assertCount(1, $cloned);
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\stanford_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $this->assertFalse($action->access($this->node));
  }

  /**
   * Get the number of nodes in the database with the name we need.
   *
   * @return int
   *   Count of rows.
   *
   * @throws \Exception
   */
  protected function getNodeCount() {
    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->container->get('database');

    return $database->select('node_field_data', 'n')
      ->fields('n')
      ->condition('title', $this->node->getTitle())
      ->countQuery()
      ->execute()
      ->fetchField();
  }

}
