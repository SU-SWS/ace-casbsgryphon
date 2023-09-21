<?php

namespace Drupal\stanford_actions\Events;

use Drupal\node\NodeInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Node clone event.
 */
class NodeCloneEvent extends Event {

  /**
   * Event constructor.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   * @param \Drupal\node\NodeInterface $originalNode
   *   Original node entity.
   */
  public function __construct(protected NodeInterface $node, protected NodeInterface $originalNode) {}

  /**
   * Get the node that was created.
   *
   * @return \Drupal\node\NodeInterface
   *   Duplicated node.
   */
  public function getNode(): NodeInterface {
    return $this->node;
  }

  /**
   * Get the original node.
   *
   * @return \Drupal\node\NodeInterface
   *   Original node that was used to duplicate
   */
  public function getOriginalNode(): NodeInterface {
    return $this->originalNode;
  }

}
