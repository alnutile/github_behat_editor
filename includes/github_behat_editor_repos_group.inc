<?php
use Drupal\GithubBehatEditor;

/**
 * @file
 * Setup repo to team and user relations
 */

function github_behat_editor_repos_group(){
    drupal_set_title("Admin Github Repos for Group");

    $output = drupal_get_form('github_behat_editor_group_repo_settings');
    return $output;
}


function github_behat_editor_group_repo_settings($form, &$form_state) {
    composer_manager_register_autoloader();
    global $user;

    $build['intro'] = array(
        '#markup' => t('Manage your repos. See other tab for user related repos')
    );

    $vendor = variable_get('composer_manager_vendor_dir', FALSE) . '/twitter/bootstrap';
    $path = drupal_get_path('module', 'github_behat_editor');
    $build['#attached']['js'][] = $vendor . '/js/tooltip.js';
    $build['#attached']['js'][] = $path . '/js/github_behat_editor_repo_admin.js';
    $build['#attached']['css'][] = $path . '/css/github_behat_editor_app.css';

    $repos = new Drupal\GithubBehatEditor\RepoModel() ;

    $groupRepos = $repos->getGroupRepos($user->uid);

    $header = array(
        'id' => array('data' => t('ID')),
        'repo_name' => array('data' => t('Repo Name')),
        'group_name' => array('data' => t('Group')),
        'repo_url' => array('data' => t('URL')),
        'active' => array('data' => t('Active')),
    );
    $rows = _github_editor_build_table_group_rows($groupRepos['results']);
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
        '#validate' => array('github_behat_editor_group_repo_settings_validate'),
        '#submit' => array('github_behat_editor_group_repo_settings_submit'),
    );


    $build['operations']['add'] = array(
        '#type' => 'link',
        '#href' => "admin/behat/github_settings/repos/group/add/$user->uid",
        '#title' => t('Add New Repo'),
        '#attributes' => array('class' => array('btn btn-md btn-success'))
    );

    $build['operations']['udpate_all'] = array(
        '#type' => 'submit',
        '#value' => 'Update and Verify all Repos <i class="glyphicon glyphicon-question-sign"></i>',
        '#submit' => array('github_behat_editor_batch_groups_repos_submit'),
        '#attributes' =>
            array(
                'id' => 'update-repos-users',
                'container' => 'body',
                'class' => array('btn btn-sm btn-danger span3'),
                'data-toggle' => "tooltip",
                'title' =>  t('This will update all repos for you that are in your groups.'))
    );


    return $build;
}

function github_behat_editor_batch_groups_repos_submit($form, &$form_state) {
    $batch = _github_behat_editor_batch_groups_repos_update();
    batch_set($batch);
}

function _github_behat_editor_batch_groups_repos_update(){
    $operations = array();
    global $user;
    $user_repos = new GithubBehatEditor\GithubBehatEditorController($user);
    $user_repos = $user_repos->getUsersGroupRepo($keyed_by_name = FALSE);


    $total = count($user_repos);
    $count = 1;
    foreach($user_repos as $key => $value) {
        $operations[] = array('batch_update_group_repo',
            array(
                'repo_array' => $value,
                t("Working on repo update @operation", array('@operation' => $count)),
                'total_found' => $total,
                'current' => $count
            )
        );
        $count++;
    }
    $batch = array(
        'operations' => $operations,
        'finished' => '_github_behat_editor_batch_group_repos_finished',
        'init_message' => t('Starting Repo Updates'),
        'error_message' => t('An error occurred. Please check the Reports/DB Logs'),
        'file' => drupal_get_path('module', 'github_behat_editor') . '/includes/github_behat_editor_repos_group.inc',
        'progress_message' => t('Total Repos found @number', array('@number' => $total)),
    );
    return $batch;
}


function batch_update_group_repo($repo_array, $message, $total, $current, &$context){
    if (empty($context['sandbox'])) {
        $context['sandbox'] = array();
        $context['sandbox']['progress'] = 1;

        // Save node count for the termination message.
        $context['sandbox']['max'] = $total;
    }

    $context['sandbox'];
    global $user;
    //Take repo_array and run an update
    $repo_action = new GithubBehatEditor\GithubBehatEditorController();
    $repo_action->checkIfRepoFolderExists(array($repo_array), $uid = $user->uid);

    //Once that is done return
    $context['sandbox']['progress']= $current;
    $context['results'][] = $repo_array['repo_name'];
    $context['message'] = t("Repo updated @name", array('@name' => $repo_array['repo_name']));

}

function _github_behat_editor_batch_group_repos_finished($success, $results, $operations) {

    if ($success) {
        drupal_set_message(t('@count repos where updated', array('@count' => count($results))));

        foreach($results as $key => $value) {
            drupal_set_message(t('repo !repo was updated and or added as needed', array('!repo' => $value)));
        }
    }
    else {
        $error_operation = reset($operations);
        drupal_set_message(t('An error occurred while processing @operation with arguments : @args', array('@operation' => $error_operation[0], '@args' => print_r($error_operation[0], TRUE))));
    }
}



/**
 * Build out the results so they work in the table format
 * with the correct ID being passed
 */
function _github_editor_build_table_group_rows($rows) {
    $output = array();
    foreach($rows as $key => $value) {
        $output[$value['id']]['id'] = $value['id'];
        $output[$value['id']]['repo_name'] = $value['repo_name'];
        $output[$value['id']]['repo_url'] = $value['repo_url'];
        $gid = $value['gid'];
        //@todo not find the right og api call for this
        $node = node_load(og_membership_load($gid)->etid)->title;
        $output[$value['id']]['group_name'] = $node;
        $output[$value['id']]['active'] = $value['active'];
        $output[$value['id']]['#attributes'] = array('class' => array($value['repo_name']));
        //$output[] = array('data' => $row, 'class' => array($value['repo_name']));
    }
    return $output;
}

function github_behat_editor_group_repo_settings_validate($form, &$form_state) {
    // Error if there are no items to select.
    if($form_state['triggering_element']['#id'] == 'edit-submit') {
        if (!is_array($form_state['values']['dashboard']) || !count(array_filter($form_state['values']['dashboard']))) {
            form_set_error('', t('No items selected.'));
        }
    }
}

function github_behat_editor_group_repo_settings_submit($form, &$form_state) {
    composer_manager_register_autoloader();
    $operation = $form_state['values']['operation'];
    $ids = array_filter($form_state['values']['dashboard']);
    if($operation == 'delete') {
        Drupal\GithubBehatEditor\RepoModel::removeRepo($ids);
        drupal_set_message(t("Your @count repos where removed from your account", array('@count' => count($ids))));
    }
}