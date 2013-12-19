<?php
use Drupal\GithubBehatEditor;

/**
 * @file
 * Setup repo to team and user relations
 */

function github_behat_editor_repos_user(){
    drupal_set_title("Admin Github Repos for User");
    $output = drupal_get_form('github_behat_editor_user_repo_settings');
    return $output;
}

function github_behat_editor_user_repo_settings($form, &$form_state) {
    composer_manager_register_autoloader();
    global $user;

    $build['intro'] = array(
        '#markup' => t('Manage your repos. See other tab for group related repos')
    );

    $repos = new Drupal\GithubBehatEditor\RepoModel() ;

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
        '#empty' => t('No Repos yet click "Add New Repo" below'),
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
        '#validate' => array('github_behat_editor_user_repo_settings_validate'),
        '#submit' => array('github_behat_editor_user_repo_settings_submit'),
    );


    $build['operations']['add'] = array(
        '#type' => 'link',
        '#href' => "admin/behat/github_settings/repos/user/add/$user->uid",
        '#title' => t('Add New Repo'),
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
        $output[$value['id']]['#attributes'] = array('class' => array($value['repo_name']));
        //$output[] = array('data' => $row, 'class' => array($value['repo_name']));
    }
    return $output;
}

function github_behat_editor_user_repo_settings_validate($form, &$form_state) {
    // Error if there are no items to select.
    if (!is_array($form_state['values']['dashboard']) || !count(array_filter($form_state['values']['dashboard']))) {
        form_set_error('', t('No items selected.'));
    }
}

function github_behat_editor_user_repo_settings_submit($form, &$form_state) {
    composer_manager_register_autoloader();
    $operation = $form_state['values']['operation'];
    $ids = array_filter($form_state['values']['dashboard']);
    if($operation == 'delete') {
        Drupal\GithubBehatEditor\RepoModel::removeRepo($ids);
        drupal_set_message(t("Your @count repos where removed from your account", array('@count' => count($ids))));
    }
}