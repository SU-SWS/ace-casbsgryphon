<?php

namespace Drupal\stanford_actions\Plugin\Action\FieldClone;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_actions\Attribute\FieldClone;

/**
 * Class Date to increment date fields.
 */
#[FieldClone(
  id: 'date',
  label: new TranslatableMarkup('Date'),
  description: new TranslatableMarkup('Incrementally increase the date on the field for every cloned item.'),
  fieldTypes: ['datetime', 'datetime_range', 'daterange']
)]
class DateClone extends FieldCloneBase {

  /**
   * Keyed array to count how many times to clone the entity id (key)
   *
   * @var array
   */
  protected $entityIds = [];

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $increment = range(0, 12);
    unset($increment[0]);

    $form['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => [
        'year' => $this->t('Year'),
        'month' => $this->t('Month'),
        'week' => $this->t('Week'),
        'day' => $this->t('Day'),
        'hour' => $this->t('Hour'),
        'minute' => $this->t('Minute'),
        'second' => $this->t('Second'),
      ],
    ];
    $form['increment'] = [
      '#type' => 'select',
      '#title' => $this->t('Increment Amount'),
      '#options' => $increment,
      '#empty_option' => $this->t('- Do Not Change -'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldValue(FieldableEntityInterface $original_entity, FieldableEntityInterface $new_entity, $field_name) {
    if (!$new_entity->hasField($field_name) || empty($this->configuration['increment'])) {
      return;
    }

    // To allow us to increase the date value for each subsequent clone, keep
    // track of how many times we've seen this original entity.
    if (!isset($this->entityIds[$original_entity->id()])) {
      $this->entityIds[$original_entity->id()] = 0;
    }
    $this->entityIds[$original_entity->id()]++;

    // Use the multiple to multiply how much to increment from the original
    // entity.
    $this->configuration['multiple'] = $this->entityIds[$original_entity->id()];

    // Loop through all field values and increment them, then set the new values
    // back to the cloned entity.
    $this->incrementFieldValues($new_entity->get($field_name));
  }

  /**
   * Loop through the field values and increase them the configured amount.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_values
   *   The field values object from the entity.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Modified field values object.
   *
   * @throws \Exception
   */
  protected function incrementFieldValues(FieldItemListInterface $field_values): FieldItemListInterface {
    /** @var \Drupal\Core\Field\FieldItemInterface $field_value */
    foreach ($field_values as $field_value) {
      $new_value = $field_value->getValue();

      $properties = array_keys($field_value->getProperties());
      $value_properties = array_filter($properties, function ($key) {
        return in_array($key, ['value', 'end_value']);
      });

      foreach ($value_properties as $property) {
        $string_value = $field_value->get($property)->getString();
        $timezone = 'UTC';
        if (in_array('timezone', $properties)) {
          $timezone = $field_value->get('timezone')->getString();
        }
        $new_value[$property] = $this->incrementDateValue($string_value, $timezone);
      }
      $field_value->setValue($new_value);
    }
    return $field_values;
  }

  /**
   * Increase the given date value by the configured amount.
   *
   * @param string $value
   *   Original date value.
   * @param string $timezone
   *   PHP Timezone String.
   *
   * @return string
   *   The new increased value.
   *
   * @throws \Exception
   */
  protected function incrementDateValue(string $value, string $timezone = 'UTC'): string {
    $increment = $this->configuration['multiple'] * $this->configuration['increment'];

    $new_value = new \DateTime($value, new \DateTimeZone($timezone));
    $daylight_savings = date('I', $new_value->getTimestamp());

    // Add the interval that is in the form of "2 days" or "6 hours".
    $interval = \DateInterval::createFromDateString($increment . ' ' . $this->configuration['unit']);
    $new_value->add($interval);

    // Date fields that don't collect the time use a different date format. We
    // check if the date length is the same length as an example format.
    if (strlen($value) == strlen('2019-02-21')) {
      return $new_value->format('Y-m-d');
    }

    // Adjust the time of the string if the new value skips over the daylight
    // savings time.
    if (date('I', $new_value->getTimestamp()) != $daylight_savings) {
      // Accommodate both going into and out of daylight savings time.
      $interval = $daylight_savings ? '1 hour' : '-1 hour';
      $interval = \DateInterval::createFromDateString($interval);
      $new_value->add($interval);
    }

    return $new_value->format('Y-m-d\TH:i:s');
  }

}
