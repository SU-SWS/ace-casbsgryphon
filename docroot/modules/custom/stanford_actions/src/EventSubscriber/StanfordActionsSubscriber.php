<?php

namespace Drupal\stanford_actions\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_actions\Events\NodeCloneEvent;
use Drupal\stanford_actions\Events\StanfordActionsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Stanford Actions event subscriber.
 */
class StanfordActionsSubscriber implements EventSubscriberInterface {

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager
   *   Entity field manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected EntityFieldManagerInterface $fieldManager) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [StanfordActionsEvents::POST_NODE_CLONED => 'onNodeClone'];
  }

  /**
   * After a node is cloned, fix the layout paragraph parents.
   *
   * @param \Drupal\stanford_actions\Events\NodeCloneEvent $event
   *   Triggered event.
   */
  public function onNodeClone(NodeCloneEvent $event): void {
    $fields = $this->fieldManager->getFieldDefinitions('node', $event->getNode()
      ->bundle());

    foreach ($fields as $field) {
      if ($field->getSetting('handler') == 'default:paragraph') {
        $this->fixLayoutParagraphEntities($event->getNode(), $event->getOriginalNode(), $field->getName());
      }
    }
  }

  /**
   * Fix the parent uuid references for layout paragraphs.
   *
   * @param \Drupal\node\NodeInterface $new_node
   *   New cloned node.
   * @param \Drupal\node\NodeInterface $old_node
   *   Original node.
   * @param string $field_name
   *   Paragraph field name.
   */
  protected function fixLayoutParagraphEntities(NodeInterface $new_node, NodeInterface $old_node, string $field_name): void {
    $p_storage = $this->entityTypeManager->getStorage('paragraph');
    $original_parent_deltas = [];

    // Create an associative array of delta to uuid for the paragraphs. This
    // will be used next to grab the appropriate uuid for the layout paragraph.
    /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item */
    foreach ($old_node->get($field_name) as $item) {
      $paragraph = $p_storage->load($item->get('target_id')->getString());
      $original_parent_deltas[] = $paragraph?->uuid();
    }

    foreach ($new_node->get($field_name) as $item) {
      /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
      $paragraph = $p_storage->load($item->get('target_id')->getString());
      $behaviors = $paragraph?->getAllBehaviorSettings() ?? [];
      // The parent uuid behavior was set, so we need to reset it to the new
      // uuid on the duplicated item.
      if (!empty($behaviors['layout_paragraphs']['parent_uuid'])) {
        // If the parent uuid changed or doesn't exist in the original node,
        // don't do anything to the paragraph.
        $orig_parent_delta = array_search($behaviors['layout_paragraphs']['parent_uuid'], $original_parent_deltas);
        if ($orig_parent_delta === FALSE) {
          continue;
        }

        $layout_id = $new_node->get($field_name)
          ->get($orig_parent_delta)
          ->get('target_id')
          ->getString();
        $parent_uuid = $p_storage->load($layout_id)->uuid();

        $behaviors['layout_paragraphs']['parent_uuid'] = $parent_uuid;
        $paragraph->setAllBehaviorSettings($behaviors);
        $paragraph->save();
      }
    }
  }

}
