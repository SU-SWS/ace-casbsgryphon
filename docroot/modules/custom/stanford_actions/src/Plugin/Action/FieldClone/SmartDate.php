<?php

namespace Drupal\stanford_actions\Plugin\Action\FieldClone;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_actions\Attribute\FieldClone;

/**
 * Class Date to increment date fields.
 *
 * @FieldClone(
 *   id = "smart_date",
 *   label = @Translation("Smart Date"),
 *   description = @Translation("Incrementally increase the Smart date on the field for every cloned item."),
 *   fieldTypes = {
 *     "smartdate"
 *   }
 * )
 */
#[FieldClone(
  id: 'smart_date',
  label: new TranslatableMarkup('Smart Date'),
  description: new TranslatableMarkup('Incrementally increase the Smart date on the field for every cloned item.'),
  fieldTypes: ['smartdate']
)]
class SmartDate extends DateClone {

  /**
   * {@inheritDoc}
   */
  protected function incrementDateValue(string $value, string $timezone = 'America/Los_Angeles'): string {
    $increment = $this->configuration['multiple'] * $this->configuration['increment'];

    $timezone = new \DateTimeZone($timezone);
    $new_value = \DateTime::createFromFormat('U', $value, $timezone);
    $new_value->setTimezone($timezone);

    // Add the interval that is in the form of "2 days" or "6 hours".
    $interval = \DateInterval::createFromDateString($increment . ' ' . $this->configuration['unit']);
    $new_value->add($interval);

    return $new_value->format('U');
  }

}
