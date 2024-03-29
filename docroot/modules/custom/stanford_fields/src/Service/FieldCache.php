<?php

namespace Drupal\stanford_fields\Service;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Cache invalidator based on field values.
 *
 * @package Drupal\stanford_fields\Service
 */
class FieldCache implements FieldCacheInterface {

  /**
   * Drupal core field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Drupal core entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal core state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * FieldCron constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Drupal core field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Drupal core entity manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Drupal core state service.
   */
  public function __construct(EntityFieldManagerInterface $field_manager, EntityTypeManagerInterface $entity_type_manager, StateInterface $state) {
    $this->fieldManager = $field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * {@inheritDoc}
   */
  public function invalidateDateFieldsCache() {
    $date_field_types = ['datetime', 'daterange', 'smartdate'];
    $cache_tags = [];

    // Loop through all fields to invalidate entities on date fields.
    foreach ($this->fieldManager->getFieldMap() as $entity_type => $fields) {
      foreach ($fields as $field_name => $field_info) {

        // Only invalidate desired date fields.
        if (in_array($field_info['type'], $date_field_types)) {
          $tags = $this->getExpiredDateCacheTags($entity_type, $field_name, $field_info['bundles']);
          $cache_tags = array_merge($cache_tags, $tags);
        }
      }
    }

    Cache::invalidateTags(array_unique($cache_tags));
    $this->state->set('stanford_fields.dates_cleared', time() - 5);
  }

  /**
   * Use an entity query to check for date fields that have recently passed.
   *
   * @param string $entity_type
   *   Entity type machine name.
   * @param string $field_name
   *   Field definition name.
   * @param array $bundles
   *   Array of entity bundle names.
   *
   * @return int[]
   *   Keyed array of entity ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getExpiredDateCacheTags($entity_type, $field_name, array $bundles = []) {
    $entity_storage = $this->entityTypeManager->getStorage($entity_type);
    $bundle_key = $this->entityTypeManager->getDefinition($entity_type)
      ->getKey('bundle');

    $field_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $now = new DrupalDateTime();
    $last_ran = DrupalDateTime::createFromTimestamp($this->state->get('stanford_fields.dates_cleared', 0));

    /** @var \Drupal\field\FieldStorageConfigInterface $field_definition */
    $field_definition = $field_storage->load("$entity_type.$field_name");
    $field_date_format = $field_definition->getSetting('datetime_type') == 'date' ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;

    // Smartdate fields are formatted differently.
    if ($field_definition->getType() == 'smartdate') {
      $field_date_format = 'U';
    }

    // Query all entities for the given date field that is between the last ran
    // time and the current time.
    $start_query = $entity_storage->getQuery()
      ->accessCheck(FALSE)
      ->exists($field_name);
    $end_query = clone $start_query;
    $condition_group = $start_query->andConditionGroup()
      ->condition($field_name, $now->format($field_date_format), '<=')
      ->condition($field_name, $last_ran->format($field_date_format), '>=');
    $start_query->condition($condition_group);

    // Some entity types don't have bundles, so don't add the condition if not
    // applicable.
    if ($bundle_key) {
      $start_query->condition($bundle_key, $bundles, 'IN');
      $end_query->condition($bundle_key, $bundles, 'IN');
    }

    $field_properties = $field_definition->getPropertyNames();
    // If the field type has an end value, modify the end query's conditions to
    // check for those values.
    if (in_array('end_value', $field_properties)) {
      $condition_group = $end_query->andConditionGroup()
        ->condition("$field_name.end_value", $now->format($field_date_format), '<=')
        ->condition("$field_name.end_value", $last_ran->format($field_date_format), '>=');
    }
    $end_query->condition($condition_group);

    // Merge the start query ids with the end query ids.
    $entity_ids = array_merge($start_query->execute(), $end_query->execute());

    $tags = [];
    // If no entity ids were found, no tags should be invalidated.
    if (!empty($entity_ids)) {
      $entities = $entity_storage->loadMultiple($entity_ids);
      foreach ($entities as $entity) {
        $tags = array_merge($tags, $entity->getCacheTagsToInvalidate());
      }
    }
    return $tags;
  }

}
