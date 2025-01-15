<?php

namespace Drupal\Tests\stanford_actions\Kernel\Plugin\Action\FieldClone;

use Drupal\Core\Form\FormState;
use Drupal\stanford_actions\Plugin\Action\FieldClone\DateClone;
use Drupal\node\Entity\Node;

/**
 * Test the date field clone plugin functions correctly.
 *
 * @group stanford_actions
 * @coversDefaultClass \Drupal\stanford_actions\Plugin\Action\FieldClone\DateClone
 */
class DateTest extends FieldCloneTestBase {

  /**
   * Test the plugin form methods.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testForm() {
    /** @var \Drupal\stanford_actions\Plugin\FieldCloneManagerInterface $field_manager */
    $field_manager = $this->container->get('plugin.manager.stanford_actions_field_clone');
    /** @var \Drupal\stanford_actions\Plugin\Action\FieldClone\DateClone $plugin */
    $plugin = $field_manager->createInstance('date');
    $this->assertInstanceOf(DateClone::class, $plugin);
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
        'date' => [
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
    $cloned_field_value = $new_node->get($this->field->getName())->getString();

    $interval = \DateInterval::createFromDateString(2 * 5 . ' year');
    $this->currentDate->add($interval);

    $this->assertEquals($this->currentDate->format('Y-m-d'), $cloned_field_value);

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
    $this->node->set($this->field->getName(), '2019-06-01T16:15:00');
    $this->node->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\stanford_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'clone_entities' => [],
      'field_clone' => [
        'date' => [
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
    $rendered_output = \Drupal::service('renderer')->renderInIsolation($pre_render);
    $this->assertStringContainsString('June 2, 2019 2:15 AM', (string) $rendered_output);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($new_node);
    $rendered_output = \Drupal::service('renderer')->renderInIsolation($pre_render);
    $this->assertStringContainsString('December 2, 2019 2:15 AM', (string) $rendered_output);
  }

  /**
   * Test when the date is copied over a daylight savings, it displays correct.
   */
  public function testDaylightSavingsFromDecember() {
    $this->node->set($this->field->getName(), '2019-12-01T16:15:00');
    $this->node->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');
    /** @var \Drupal\stanford_actions\Plugin\Action\CloneNode $action */
    $action = $action_manager->createInstance('node_clone_action');
    $action->setConfiguration([
      'clone_entities' => [],
      'field_clone' => [
        'date' => [
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
    $rendered_output = \Drupal::service('renderer')->renderInIsolation($pre_render);
    $this->assertStringContainsString('December 2, 2019 3:15 AM', (string) $rendered_output);

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $pre_render = $view_builder->view($new_node);
    $rendered_output = \Drupal::service('renderer')->renderInIsolation($pre_render);
    $this->assertStringContainsString('June 2, 2020 3:15 AM', (string) $rendered_output);
  }

}
