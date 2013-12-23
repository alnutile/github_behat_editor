<?php

/**
 * @file
 * Drush hook implementations for BehatEditor
 */

/**
 * Implements hook_drush_command().
 */
function github_behat_editor_drush_command() {
    $items = array();

    $items['github-behat-setup'] = array(
        'description' => 'Setup Tests',
        'allow-additional-options' => TRUE,
        'aliases' => array('gbs'),
        'examples' => array(
            'drush gbs' => 'This will run the setup steps'
        ),
        'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
        'arguments' => array(),
    );

    return $items;
}


//@todo add profile switch as well
function drush_github_behat_editor_github_behat_setup() {
    composer_manager_register_autoloader();
    $public = file_build_uri('/behat_github/');
    $public = drupal_realpath($public);
    $full_path = $public . '/users/1/aaaa_test/tests/wikipedia.feature';
    _setup_new_test_file($full_path);


    $public = file_build_uri('/behat_github/');
    $public = drupal_realpath($public);
    $full_path = $public . '/users/1/bbbbb_test/tests/wikipedia.feature';
    _setup_new_test_file($full_path);

    $public = file_build_uri('/behat_tests/');
    $public = drupal_realpath($public);
    $full_path = $public . '/wikipedia.feature';
    _setup_new_test_file($full_path);


}

function _setup_new_test_file($full_path) {

    $data = <<<HEREDOC
     @example @drush
 Feature: Example Test for WikiPedia

   Scenario: WikiPedia
     Given I am on "http://en.wikipedia.org/wiki/Main_Page"
     Then I should see "WikiPedia"
     Then I should see "Muffins"

HEREDOC;
    $file = fopen($full_path, 'w');
    fwrite($file, $data);
    fclose($file);
}
