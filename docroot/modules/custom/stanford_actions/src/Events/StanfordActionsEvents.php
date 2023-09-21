<?php

namespace Drupal\stanford_actions\Events;

/**
 * Action events.
 */
final class StanfordActionsEvents {

  /**
   * Before saving the cloned node.
   */
  const PRE_NODE_CLONED = 'pre_node_cloned';

  /**
   * After saving the cloned node.
   */
  const POST_NODE_CLONED = 'post_node_cloned';

}
