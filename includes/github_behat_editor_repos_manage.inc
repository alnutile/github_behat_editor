<?php

use Drupal\GithubBehatEditor;

/**
 * @file manage the settings for the repo to user/group
 */


/**
 * @param $type
 *   user or group
 * @param $action
 *   add update delete
 */
function github_behat_editor_repos_manage($type, $action, $id) {
    $build['intro'] = t("Manage Repoos");

    $build['repos'] = drupal_get_form('github_behat_editor_repos_form', $type, $action, $id);

    return $build;
}

function github_behat_editor_repos_form($form, &$form_state) {
    composer_manager_register_autoloader();
    $client = github_api_client();

    $repos_found = $client->api('current_user')->repositories();

    drupal_set_message(t('Return to !link', array('!link' => l('admin interface', 'admin/behat/github_settings/'))));

    $action = $form_state['build_info']['args'][1];
    $type = $form_state['build_info']['args'][0];
    $uid = $form_state['build_info']['args'][2];
    $id = '';
    $base_url = '';
    $repo_name = '';
    $active = 1;
    $repo_url = 0;
    $gid = '';
    $branch = '';

    $button = strtoupper($action);

    $form['rid'] = array(
        '#type' => 'hidden',
        '#default_value' => $id,
    );

    $form['gid'] = array(
        '#type' => 'hidden',
        '#default_value' => $gid,
    );

    $form['uid'] = array(
        '#type' => 'hidden',
        '#default_value' => $uid,
    );

    $existing_repos_formatted = _github_behat_editor_formatted_list_of_existing_repos_in_db($uid);
    $options = array();
    foreach($repos_found as $key => $value) {
        if(!in_array($value['name'], $existing_repos_formatted)) {
            $options["{$value['id']}|{$value['html_url']}|{$value['name']}|{$uid}"] = $value['name'];
        }
    };

    $form['repo_name'] = array(
        '#type' => 'select',
        '#default_value' => $repo_name,
        '#description' => t('Choose which repo to add to your user list'),
        '#options' => $options,
        '#empty_value' => "-select one-",
        '#multiple' => TRUE,
    );

    $title = t('These repos are not seen above since they are already selected in the database for your id');
    $variables = array('items' => $existing_repos_formatted, 'title' => $title);
    $form['already_repos'] = array(
        '#markup' => theme('item_list', $variables),
    );

    $form['repo_url'] = array(
        '#type' => 'hidden',
        '#default_value' => $base_url,
    );

    $form['active'] = array(
        '#type' => 'checkbox',
        '#default_value' => $active,
        '#description' => t('Set to active'),
    );

    $form['add'] = array(
        '#type' => "submit",
        '#value' => t($button),
    );

    return $form;
}

function _github_behat_editor_formatted_list_of_existing_repos_in_db($uid){
    $existing_user_repos_in_db = new GithubBehatEditor\GithubRepoQueries();
    $repos_already = $existing_user_repos_in_db->selectAllByUid($uid);
    $results = array();
    foreach($repos_already['results'] as $key => $value) {
        $results[] = $value['repo_name'];
    }
    return $results;
}

/**
 * @param $form
 * @param $form_state
 *
 * @todo all info comes from the github api so should be not empty?
 *
 */
function github_behat_editor_repos_form_validate($form, &$form_state) {
    if(empty($form_state['values']['repo_name'])) {
        form_set_error('repo_name', t('Um seems you did not choose a repo?'));
    }
}

function github_behat_editor_repos_form_submit($form, &$form_state) {
    composer_manager_register_autoloader();
    $repos = $form_state['values']['repo_name'];
    $insert = new Drupal\GithubBehatEditor\RepoManager();
    $results = $insert->insertRepo(array('repos' => $repos));
    dpm($results);
}

