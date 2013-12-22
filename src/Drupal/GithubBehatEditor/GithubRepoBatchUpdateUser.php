<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/22/13
 * Time: 7:56 AM
 */

namespace Drupal\GithubBehatEditor;


class GithubRepoBatchUpdateUser {
    public $operations;
    public $rid;
    public $test_results;

    public function __construct(){
        composer_manager_register_autoloader();

    }

    public function setup($params) {
        $this->user = $params['user'];
        $this->setBatch();
    }

    protected function setBatch() {
        $batch = array(
            'operations' => $this->operations,
            'title' => t('Batch update or repos for User'),
            'file' => drupal_get_path('module', 'github_behat_editor') . '/includes/behat_editor_tag.batch.inc',
            'init_message' => t('Starting Behat Tests'),
            'error_message' => t('An error occurred. Please check the Reports/DB Logs'),
            'finished' => 'github_editor_batch_user_repo_done',
            'progress_message' => t('Running tests for @number modules. Will return shortly with results.', array('@number' => count($this->operations))),
        );
        $this->batch = $batch;
    }

} 