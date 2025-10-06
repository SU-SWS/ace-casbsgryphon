<?php

namespace Drupal\stanford_actions\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\stanford_actions\Annotation\FieldClone as FieldCloneAnnotation;
use Drupal\stanford_actions\Attribute\FieldClone;
use Drupal\stanford_actions\Plugin\Action\FieldClone\FieldCloneInterface;

/**
 * Class CloneFieldsManager for field clone plugins.
 */
class FieldCloneManager extends DefaultPluginManager implements FieldCloneManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/Action/FieldClone',
      $namespaces,
      $module_handler,
      FieldCloneInterface::class,
      FieldClone::class,
      FieldCloneAnnotation::class
    );
    $this->alterInfo('field_clone_info');
    $this->setCacheBackend($cache_backend, 'field_clone_info_plugins');
  }

}
