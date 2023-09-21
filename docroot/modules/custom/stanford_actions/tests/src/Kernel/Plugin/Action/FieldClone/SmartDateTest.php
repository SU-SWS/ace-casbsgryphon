<?php

namespace Drupal\Tests\stanford_actions\Kernel\Plugin\Action\FieldClone;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\stanford_actions\Plugin\Action\FieldClone\SmartDate;

/**
 * Test the date field clone plugin functions correctly.
 *
 * @group stanford_actions
 * @coversDefaultClass \Drupal\stanford_actions\Plugin\Action\FieldClone\SmartDate
 */
class SmartDateTest extends FieldCloneTestBase {

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
    'smart_date',
  ];

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installConfig('smart_date');

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'smart_date_field',
      'entity_type' => 'node',
      'type' => 'smartdate',
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ]);
    $this->assertNotFalse($this->field->save());

    $node_display = EntityViewDisplay::load('node.page.default');

    $node_display->setComponent($this->field->getName());
    $node_display->save();

    $timezones = timezone_identifiers_list();
    $timezone_key = array_rand($timezones);
    $this->node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'page',
      $this->field->getName() => [
        [
          'value' => $this->currentDate->format('U'),
          'end_value' => $this->currentDate->format('U'),
          'timezone' => $timezones[$timezone_key],
          'duration' => 0,
        ],
      ],
    ]);
  }

  /**
   * Test the plugin form methods.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testForm() {
    /** @var \Drupal\stanford_actions\Plugin\FieldCloneManagerInterface $field_manager */
    $field_manager = $this->container->get('plugin.manager.stanford_actions_field_clone');
    /** @var \Drupal\stanford_actions\Plugin\Action\FieldClone\DateClone $plugin */
    $plugin = $field_manager->createInstance('smart_date');
    $this->assertInstanceOf(SmartDate::class, $plugin);
    $form = [];
    $form_state = new FormState();
    $form = $plugin->buildConfigurationForm($form, $form_state);
    $this->assertCount(2, $form);
  }

  /**
   * Test the field clone values works as expected.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testDateFieldClone() {
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\stanford_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'clone_entities' => [],
      'clone_count' => 5,
      'field_clone' => [
        'smart_date' => [
          $this->field->getName() => [
            'increment' => 2,
            'unit' => 'years',
          ],
        ],
      ],
    ]);
    $action->execute($this->node);
    $nodes = Node::loadMultiple();

    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = end($nodes);
    $cloned_field_value = $new_node->get($this->field->getName())->getValue();

    $interval = \DateInterval::createFromDateString(2 * 5 . ' year');
    $this->currentDate->add($interval);

    $this->assertEquals($this->currentDate->format('Y-m-d'), date('Y-m-d', $cloned_field_value[0]['value']));

    $test_field_base = new TestFieldCloneBase([], NULL, NULL);
    $form = [];
    $form_state = new FormState();
    $this->assertNull($test_field_base->validateConfigurationForm($form, $form_state));
    $this->assertNull($test_field_base->submitConfigurationForm($form, $form_state));
  }

  /**
   * Test when the date is copied over a daylight savings, it displays correct.
   */
  public function testDaylightSavingsFromJune() {
    $this->node->set($this->field->getName(), [
      [
        'value' => strtotime('2019-06-06 11:00 AM'),
        'end_value' => strtotime('2019-06-06 11:00 AM'),
        'timezone' => 'America/Los_Angeles',
      ],
    ]);
    $this->node->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\stanford_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'clone_entities' => [],
      'field_clone' => [
        'smart_date' => [
          $this->field->getName() => [
            'increment' => 6,
            'unit' => 'months',
          ],
        ],
      ],
    ]);
    $action->execute($this->node);

    $nodes = Node::loadMultiple();
    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = end($nodes);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($this->node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('Wed, Jun 5 2019, 6pm', (string) $rendered_output);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($new_node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('Thu, Dec 5 2019, 6pm', (string) $rendered_output);
  }

  /**
   * Test when the date is copied over a daylight savings, it displays correct.
   */
  public function testDaylightSavingsFromDecember() {
    $this->node->set($this->field->getName(), [
      [
        'value' => strtotime('2019-12-12 5:00 PM'),
        'end_value' => strtotime('2019-12-12 5:00 PM'),
        'timezone' => 'America/Los_Angeles',
      ],
    ]);
    $this->node->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\stanford_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'clone_entities' => [],
      'field_clone' => [
        'smart_date' => [
          $this->field->getName() => [
            'increment' => 6,
            'unit' => 'months',
          ],
        ],
      ],
    ]);
    $action->execute($this->node);
    $nodes = Node::loadMultiple();
    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = end($nodes);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($this->node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('Wed, Dec 11 2019, 10pm', (string) $rendered_output);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($new_node);
    $rendered_output = \Drupal::service('renderer')->renderPlain($pre_render);
    $this->assertStringContainsString('Thu, Jun 11 2020, 10pm', (string) $rendered_output);
  }

}
