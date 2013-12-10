<?php
use Drupal\GithubBehatEditor;

/**
 * @file
 * Setup repo to team and user relations
 */

function github_behat_editor_repos_user(){
    $output = drupal_get_form('github_behat_editor_user_repo_settings_user');
    return $output;
}

function github_behat_editor_user_repo_settings_user($form, &$form_state) {
    composer_manager_register_autoloader();
    global $user;

    $build['intro'] = array(
        '#markup' => t('Manage your repos. See other tab for group related repos')
    );

    $repos = new Drupal\GithubBehatEditor\RepoManager() ;

    $userRepos = $repos->getUserRepos($user->uid);

    $header = array(
        'id' => array('data' => t('ID')),
        'repo_name' => array('data' => t('Repo Name')),
        'repo_url' => array('data' => t('URL')),
        'active' => array('data' => t('Active')),
    );
    $rows = _github_editor_build_table_rows($userRepos['results']);
    $build['dashboard'] = array(
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $rows,
        '#empty' => t('No Repos yet click "Add New Row" below'),
    );

    $options = array(
        'delete' => t('Delete'),
        'deactivate' => t('Set to not-active'),
        'active' => t('Set to active'),
    );

    $build['operations']['operation'] = array(
        '#type' => 'select',
        '#title' => t('Operation'),
        '#title_display' => 'invisible',
        '#options' => $options,
    );

    $build['operations']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit Settings'),
        '#validate' => array('github_behat_editor_user_repo_settings_user_validate'),
        '#submit' => array('github_behat_editor_user_repo_settings_user_submit'),
    );


    $build['operations']['add'] = array(
        '#type' => 'link',
        '#href' => "admin/behat/github_settings/repos/user/add/$user->uid",
        '#title' => t('Add New Row'),
        '#attributes' => array('class' => array('btn btn-md btn-success'))
    );

    return $build;


}

/**
 * Build out the results so they work in the table format
 * with the correct ID being passed
 */
function _github_editor_build_table_rows($rows) {
    $output = array();
    foreach($rows as $key => $value) {
        $output[$value['id']]['id'] = $value['id'];
        $output[$value['id']]['repo_name'] = $value['repo_name'];
        $output[$value['id']]['repo_url'] = $value['repo_url'];
        $output[$value['id']]['active'] = $value['active'];
    }
    return $output;
}

function github_behat_editor_user_repo_settings_user_validate($form, &$form_state) {
    // Error if there are no items to select.
    if (!is_array($form_state['values']['dashboard']) || !count(array_filter($form_state['values']['dashboard']))) {
        form_set_error('', t('No items selected.'));
    }
}

function github_behat_editor_user_repo_settings_user_submit($form, &$form_state) {
    composer_manager_register_autoloader();
    $operation = $form_state['values']['operation'];
    $ids = array_filter($form_state['values']['dashboard']);
    if($operation == 'delete') {
        Drupal\GithubBehatEditor\RepoManager::removeRepo($ids);
        drupal_set_message("Your @count repos where removed from your account", array('@count' => count($ids)));
    }
}