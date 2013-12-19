<?php

use Drupal\GithubBehatEditor;

use TQ\Git\StreamWrapper\StreamWrapper,
    TQ\Git\Cli\Binary;

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
    global $user;
    $path = drupal_get_path('module', 'github_behat_editor');

    composer_manager_register_autoloader();

    $client = new Drupal\GithubBehatEditor\RepoModel();
    $repos_found = $client->getAllRepos();

    drupal_set_message(t('Return to !link', array('!link' => l('admin interface', 'admin/behat/github_settings/'))));

    $action = $form_state['build_info']['args'][1];
    $type = $form_state['build_info']['args'][0];
    $uid = $form_state['build_info']['args'][2];
    $id = '';
    $base_url = '';
    $repo_name = '';
    $folder = '';
    $active = 1;
    $repo_url = 0;
    $gid = '';
    $branch = '';

    $button = strtoupper($action);

    $form['type'] = array(
        '#type' => 'hidden',
        '#default_value' => $type,
    );

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

    $existing_repos_formatted = array();
    if($type == 'group') {
        $existing_repos = $client->getGroupRepos($user->uid);
    } else {
        $existing_repos = $client->getUserRepos($user->uid);
    }

    foreach($existing_repos['results'] as $key => $value) {
        $existing_repos_formatted[] = $value['repo_name'];
    }
    $options = array();
    foreach($repos_found as $key => $value) {
        if(!in_array($value['name'], $existing_repos_formatted)) {
            $options["{$value['id']}|{$value['html_url']}|{$value['name']}|{$uid}|{$value['full_name']}"] = $value['name'];
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

    if($type == 'group') {
        $gid = '';
        $options = array(); //get group ui via url group
        $groups = new Drupal\BehatEditor\BehatPermissions($user->uid);
        $form_helper = new Drupal\BehatEditor\BehatSettingsFormHelper();
        $groups = $form_helper->_behat_editor_group_gid_and_title_options_list($groups->getGroupIDs());

        $form['group_name'] = array(
            '#type' => 'select',
            '#default_value' => $gid,
            '#description' => t('Choose which group to relate to'),
            '#options' => $groups,
            '#empty_value' => "-select one-",
            '#multiple' => FALSE,
            '#required' => TRUE
        );
    }

    $title = t('These repos are not seen above since they are already selected in the database for your uid or group id');
    $variables = array('items' => $existing_repos_formatted, 'title' => $title);
    $form['already_repos'] = array(
        '#markup' => theme('item_list', $variables),
    );

    $form['folder'] = array(
        '#title' => t('Folder tests are in'),
        '#type' => 'textfield',
        '#default_value' => $folder,
        '#required' => TRUE,
        '#description' => t('It is required to store tests in a repo\'s folder. This will be the folder that this system looks for files.'),
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
    if($form_state['values']['group_name'] == '-select one-') {
        form_set_error('group_name', t('Um seems you did not choose a group?'));
    }
}

function github_behat_editor_repos_form_submit($form, &$form_state) {
    composer_manager_register_autoloader();
    global $user;
    $repos = $form_state['values']['repo_name'];
    $folder = $form_state['values']['folder'];
    $insert = new Drupal\GithubBehatEditor\RepoModel();
    if($form_state['values']['type'] == 'user') {
        $results = $insert->insertRepo(array('repos' => $repos, 'folder' => $folder));
    }
    if($form_state['values']['type'] == 'group') {
        $gid = $form_state['values']['group_name'];
        $results = $insert->insertRepo(array('repos' => $repos, 'folder' => $folder, 'gid' => $gid));
        $insert->cloneRepoGroup($insert->repos_chosen, array('uid' => $user->uid, 'gid' => $gid));
    }
    //Clone one repo for the group and one for the user
    $insert->cloneRepo($insert->repos_chosen, array('uid' => $user->uid));
}

