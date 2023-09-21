<?php


namespace Drupal\Tests\stanford_actions\Kernel\Plugin\Action\FieldClone;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\stanford_actions\Plugin\Action\FieldClone\FieldCloneBase;

/**
 * Test the date field clone plugin functions correctly.
 *
 * @group stanford_actions
 * @coversDefaultClass \Drupal\stanford_actions\Plugin\Action\FieldClone\DateClone
 */
abstract class FieldCloneTestBase extends KernelTestBase {

  /**
   * Node object to clone.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Date Time field.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Current date time object.
   *
   * @var \DateTime
   */
  protected $currentDate;

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
  ];

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->currentDate = new \DateTime();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('date_format');
    $this->installSchema('node', 'node_access');

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => strtolower($this->randomMachineName()),
      'entity_type' => 'node',
      'type' => 'datetime',
      'settings' => ['datetime_type' => DateRangeItem::DATETIME_TYPE_DATETIME],
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $this->field->save();

    $node_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ]);

    DateFormat::create(['id' => 'medium', 'pattern' => 'F j, Y g:i A'])
      ->save();
    $node_display->setComponent($this->field->getName());
    $node_display->save();

    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'page',
      $this->field->getName() => ['value' => $this->currentDate->format('Y-m-d')],
    ]);
    $this->node->save();

    DateFormat::create([
      'id' => 'fallback',
      'pattern' => 'D, m/d/Y - H:i',
    ])->save();
  }

}

class TestFieldCloneBase extends FieldCloneBase {

  public function alterFieldValue(FieldableEntityInterface $original_entity, FieldableEntityInterface $new_entity, $field_name, array $config = []) {
  }

}
