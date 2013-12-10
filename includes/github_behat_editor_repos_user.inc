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
    dpm($userRepos);

    $header = array(
        'sid' => array('data' => t('ID')),
        'repo_name' => array('data' => t('Repo Name')),
        'repo_url' => array('data' => t('URL')),
        'active' => array('data' => t('Active')),
        'edit' => array('data' => t('Edit')),
    );

    $build['dashboard'] = array(
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $userRepos['results'],
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
        '#validate' => array('behat_editor_base_url_settings_user_validate'),
        '#submit' => array('behat_editor_base_url_settings_user_submit'),
    );


    $build['operations']['add'] = array(
        '#type' => 'link',
        '#href' => "admin/behat/github_settings/repos/user/add/$user->uid",
        '#title' => t('Add New Row'),
        '#attributes' => array('class' => array('btn btn-md btn-success'))
    );



    return $build;


}