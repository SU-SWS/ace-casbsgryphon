<?php

/**
 * @file
 * stanford_profile_helper.post_update.php
 */

 /**
 * Create new ultimate cron job.
 */
function stanford_profile_helper_post_update_create_cron(&$sandbox) {
  \Drupal::entityTypeManager()
    ->getStorage('ultimate_cron_job')
    ->create([
      'id' => 'stanford_profile_helper_cron',
      'label' => 'Stanford Profile Helper Cron',
      'module' => 'stanford_profile_helper',
      'callback' => 'stanford_profile_helper_cron',
    ])->save();
}

/**
 * Implements hook_removed_post_updates().
 */
function stanford_profile_helper_removed_post_updates() {
  return [
    'stanford_profile_helper_post_update_8000' => '9.4.3',
    'stanford_profile_helper_post_update_8001' => '9.4.3',
    'stanford_profile_helper_post_update_8100' => '9.4.3',
    'stanford_profile_helper_post_update_8101' => '9.4.3',
    'stanford_profile_helper_post_update_8102' => '9.4.3',
    'stanford_profile_helper_post_update_8103' => '9.4.3',
    'stanford_profile_helper_post_update_9000' => '9.4.3',
    'stanford_profile_helper_post_update_9001' => '9.4.3',
  ];
}
