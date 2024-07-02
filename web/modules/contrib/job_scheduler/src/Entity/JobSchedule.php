<?php

namespace Drupal\job_scheduler\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the job schedule entity.
 *
 * @ContentEntityType(
 *   id = "job_schedule",
 *   label = @Translation("Job Schedule"),
 *   handlers = {
 *     "storage_schema" = "Drupal\job_scheduler\JobScheduleStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "job_schedule",
 *   entity_keys = {
 *     "id" = "jid",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class JobSchedule extends ContentEntityBase implements ContentEntityInterface {

  /**
   * Sets the job name.
   *
   * @param string $name
   *   The job name.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * Returns the job name.
   *
   * @return string
   *   The job name.
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * Sets the job type.
   *
   * @param string $type
   *   The job type.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * Returns the job type.
   *
   * @return string
   *   The job type.
   */
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * Sets the job id.
   *
   * @param int $id
   *   The job id.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setId($id) {
    $this->set('id', $id);
    return $this;
  }

  /**
   * Returns the job id.
   *
   * @return int
   *   The job id.
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * Sets the job period.
   *
   * @param int $period
   *   Time period after which job is to be executed.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setPeriod($period) {
    $this->set('period', $period);
    return $this;
  }

  /**
   * Returns the job period.
   *
   * @return int
   *   Time period after which job is to be executed.
   */
  public function getPeriod() {
    return $this->get('period')->value;
  }

  /**
   * Sets the job crontab.
   *
   * @param string $crontab
   *   The job crontab.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setCrontab($crontab) {
    $this->set('crontab', $crontab);
    return $this;
  }

  /**
   * Returns the job crontab.
   *
   * @return string
   *   The job crontab.
   */
  public function getCrontab() {
    return $this->get('crontab')->value;
  }

  /**
   * Sets the job data.
   *
   * @param array $data
   *   The job data.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setData(array $data) {
    $this->set('data', serialize($data));
    return $this;
  }

  /**
   * Returns the job data.
   *
   * @return mixed
   *   The job data.
   */
  public function getData() {
    $data = $this->get('data')->getValue();
    return reset($data);
  }

  /**
   * Sets the job periodic.
   *
   * @param bool $periodic
   *   The job periodic.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setPeriodic($periodic) {
    $this->set('periodic', (bool) $periodic);
    return $this;
  }

  /**
   * Returns TRUE if job will be automatically rescheduled and FALSE if not.
   *
   * @return bool
   *   The job rescheduling state.
   */
  public function getPeriodic() {
    return (bool) $this->get('periodic')->value;
  }

  /**
   * Sets the job last.
   *
   * @param int $last
   *   The job last.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setLast($last) {
    $this->set('last', $last);
    return $this;
  }

  /**
   * Returns the job last execution.
   *
   * @return int
   *   Timestamp when a job was last executed.
   */
  public function getLast() {
    return $this->get('last')->value;
  }

  /**
   * Sets the job next.
   *
   * @param int $next
   *   The job next.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setNext($next) {
    $this->set('next', $next);
    return $this;
  }

  /**
   * Returns the job next execution.
   *
   * @return int
   *   Timestamp when a job is to be executed.
   */
  public function getNext() {
    return $this->get('next')->value;
  }

  /**
   * Sets the job scheduled.
   *
   * @param int $scheduled
   *   The job scheduled.
   *
   * @return JobSchedule
   *   The job schedule entity.
   */
  public function setScheduled($scheduled) {
    $this->set('scheduled', $scheduled);
    return $this;
  }

  /**
   * Returns the job scheduled.
   *
   * @return int
   *   Timestamp when a job was scheduled.
   */
  public function getScheduled() {
    return $this->get('scheduled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['jid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the job schedule.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the job schedule.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the job.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the job.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Job ID'))
      ->setDescription(t('The ID of the job.'))
      ->setRequired(TRUE);

    $fields['period'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Period'))
      ->setDescription(t('Time period after which job is to be executed.'))
      ->setDefaultValue(0);

    $fields['crontab'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Crontab'))
      ->setDescription(t('Crontab line in *NIX format.'));

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('The arbitrary data for the job.'));

    $fields['periodic'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Periodic'))
      ->setDescription(t('If true job will be automatically rescheduled.'))
      ->setDefaultValue(FALSE);

    $fields['last'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last execution'))
      ->setDescription(t('Timestamp when a job was last executed.'));

    $fields['next'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Next execution'))
      ->setDescription(t('Timestamp when a job is to be executed.'));

    $fields['scheduled'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Scheduled'))
      ->setDescription(t('Timestamp when a job was scheduled.'))
      ->setDefaultValue(0);

    return $fields;
  }

}
