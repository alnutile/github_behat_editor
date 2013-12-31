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
            'drush gbs wikipedia2.feature' => 'This will run the setup steps making a test file called wikipedia2.feature'
        ),
        'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
        'arguments' => array(
            'filename' => 'The name of the file to create during this setup if none is given it default to wikipedia.feature',
            'uid' => 'User UID if needed else 1 will be used',
        ),
    );

    return $items;
}


//@todo add profile switch as well
function drush_github_behat_editor_github_behat_setup() {
    if(func_num_args() >= 1) {
        $filename = func_get_arg(0);
    } else {
        $filename = 'wikipedia.feature';
    }

    if(func_num_args() >= 2) {
        $uid = func_get_arg(1);
    } else {
        $uid = '1';
    }

    composer_manager_register_autoloader();
    $public = file_build_uri('/behat_github/');
    $public = drupal_realpath($public);
    $full_path = $public . '/users/' . $uid . '/aaaa_test/tests/' . $filename;
    _setup_new_test_file($full_path);

    $public = file_build_uri('/behat_github/');
    $public = drupal_realpath($public);
    $full_path = $public . '/users/' . $uid . '/bbbbb_test/tests/' . $filename;
    _setup_new_test_file($full_path);

    $public = file_build_uri('/behat_github/');
    $public = drupal_realpath($public);
    $full_path = $public . '/groups/3/bbbbb_test/tests/' . $filename;
    _setup_new_test_file($full_path);

    $public = file_build_uri('/behat_tests/');
    $public = drupal_realpath($public);
    $full_path = $public . '/' . $filename;
    _setup_new_test_file($full_path);

    //Setup a test repo for non group tests
    $public = file_build_uri('/behat_github/');
    $public = drupal_realpath($public);
    $full_path = $public . '/groups/10000/cccc_test';
    _setup_new_test_file($full_path);

    if(!file_exists($full_path)) {
        mkdir("$full_path", 0777, $recursive = TRUE);
        //update perms
        exec("chgrp -R www-data $full_path");
    }

    _setup_test_repo();
    $public = file_build_uri('/behat_github/');
    $public = drupal_realpath($public);
    $full_path = $public . '/groups/10000/cccc_test/tests/' . $filename;
    _setup_new_test_file($full_path);
}

function _setup_test_repo() {
    $repos = array('alnutile/cccc_test');
    $clone = new Drupal\GithubBehatEditor\RepoModel();
    $clone->cloneRepoGroup($repos, array('uid' => 0, 'gid' => '10000'));
}

function _setup_new_test_file($full_path) {

    $data = <<<HEREDOC
     @example
 Feature: Example Test for WikiPedia

   Scenario: WikiPedia
     Given I am on "http://en.wikipedia.org/wiki/Main_Page"
     Then I should see "WikiPedia"
     Then I should see "Muffins"

HEREDOC;
    $file = fopen($full_path, 'w');
    fwrite($file, $data);
    fclose($file);

    drupal_chmod($full_path, $mode = 0775); // in case drush makes it I want www-data to write to this
}
