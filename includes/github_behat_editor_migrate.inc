<?php

use Drupal\GithubBehatEditor,
    Drupal\BehatEditor;

/**
 * @file
 *
 * Migrate files into Github workflow moving them out of behat tests
 */


function github_behat_editor_migrate(){
    $path = drupal_get_path('module', 'github_behat_editor');
    drupal_add_css($path . '/css/github_behat_editor_app.css');

    $build['form'] = drupal_get_form('github_behat_editor_migratate_form');
    return $build;
}


function github_behat_editor_migratate_form(){
    composer_manager_register_autoloader();

    //1. what files

    $files_folder =  file_build_uri("/behat_tests/");
    $files = file_scan_directory($files_folder, '/.*\.feature/', $options = array('recurse' => FALSE, 'key' => 'filename'), $depth = 0);
    ksort($files);
    $file_option_list = array();
    foreach($files as $key) {
        $file_option_list[$key->uri] = $key->filename;
    }

    $form['files'] = array(
        '#type' => 'select',
        '#multiple' => TRUE,
        '#options' => $file_option_list,
        '#attributes' => array('id' => 'migrate-github-files')
    );

    //2. what repo
    global $user;
    $client = new Drupal\GithubBehatEditor\RepoModel();
    $existing_repos_user =  $client->getUserRepos($user->uid);
    $existing_group_repos = $client->getGroupRepos(array('uid' => $user->uid));
    $options = array();
    foreach(array_merge($existing_repos_user['results'], $existing_group_repos['results']) as $key => $value ){

        $options["{$value['id']}|{$value['gid']}|{$value['uid']}|{$value['repo_name']}|{$value['folder']}"] = $value['repo_name'];
    }

    $form['repo_name'] = array(
        '#type' => 'select',
        '#description' => t('Choose which repo to move it into'),
        '#options' => $options,
        '#description' => t("If you do not see your repo here please make sure it is added under your account. !link", array('!link' => l('repo admin user', 'admin/behat/github_settings')))
    );

    //3. move or copy
    $form['move'] = array(
        '#title' => t("Move files - default is to copy"),
        '#type' => 'checkbox'
    );

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => "Submit",
    );

    return $form;
}

function github_behat_editor_migratate_form_validate($form, &$form_state){
    if(empty($form_state['values']['files'])) {
        form_set_error('files', t('No files chosen?'));
    }

}

function github_behat_editor_migratate_form_submit($form, &$form_state){

    //1. get the repo info to build a path
    list($id, $gid, $uid, $repo_name, $folder) = explode('|', $form_state['values']['repo_name']);
    ($gid != 0) ? $root = 'groups' : $root = 'users';
    ($gid != 0) ? $id = $gid : $id = $uid;
    $settings_path = "/behat_github/" . $root . "/" . $id . "/" . $repo_name;
    $git_repo_root_path = file_build_uri("/behat_github/" . $root . "/" . $id . "/" . $repo_name);
    $destination = $git_repo_root_path . "/" . $folder;

    //2. Decide if this is move of copy
    if($form_state['values']['move'] == 1) {
        $action_message = 'moved';
    } else {
        $action_message = 'copied';
    }

    //Make this a copy then remove/unlink incase there is a failure in the git work

    //3. for each file move or copy to that path
    $full_path_with_filename_all = array();
    $files_to_move_or_copy_all = array();
    $files_name_only_to_add_to_repo_all = array();
    foreach($form_state['values']['files'] as $value) {
        //Full path
        $file_to_move_or_copy       = $value;
        $files_to_move_or_copy_all[] = $value;
        $file_to_add_to_repo        = array_pop(explode("/", $value));
        $files_name_only_to_add_to_repo_all[]  = $file_to_add_to_repo;
        $full_path_with_filename    = drupal_realpath($destination . "/$file_to_add_to_repo");
        $full_path_with_filename_all[] = $full_path_with_filename;
        file_unmanaged_copy($file_to_move_or_copy, $full_path_with_filename, $replace = FILE_EXISTS_REPLACE);
        drupal_set_message(t('File !action to !destination', array('!action' => $action_message, '!destination' => $full_path_with_filename)));
        $new_path = explode('/', $settings_path . '/' . $file_to_move_or_copy);
    }
    //4. Update that repo to do a add, commit pull push
    $git = new GithubBehatEditor\GitActions();
    //Add Null files
    $results = $git->updateManyFiles(drupal_realpath($git_repo_root_path), $folder, NULL, $message = "Bulk move of files into the repo");
    //4.a if error move the file back and do not do db work till it succeeds
    if(empty($results) || $results['error'] == 1) {
        //unlink($full_path_with_filename);
        $message = t('Your files could not be placed into the repo, the error was !message in the mean time the files were copied back', array('!message' => $results['message']));
        drupal_set_message($message, $type = 'error');
    } else {
        $message = t('Your file was added to the the !repo repo', array('!repo' => $repo_name));
        drupal_set_message($message);
        _github_behat_editor_remove_old_file(array($action_message, $files_to_move_or_copy_all));
        foreach($files_name_only_to_add_to_repo_all as $file_to_add_to_repo) {
            dpm($file_to_add_to_repo);
            $result = _github_behat_editor_get_records(array($file_to_add_to_repo));
            if ($result) {
                foreach ($result as $record) {
                    $record->settings = unserialize($record->settings);
                    $record->settings['path'] = $new_path;
                    $record->settings = serialize($record->settings);
                    _github_behat_editor_update_records(array($record->rid, $record->settings));
                }
            }
        }
    }
}

function _github_behat_editor_get_records($params) {
    list($file) = $params;
    $query = db_select('behat_editor_results', 'b');
    $query->fields('b');
    $query->condition('b.filename', $file, 'LIKE');
    $query->condition('b.module', 'behat_tests', 'LIKE');
    $query->orderBy('b.created', 'DESC');
    $result = $query->execute();
    return $result;
}

function _github_behat_editor_update_records(array $params){
    list($rid, $settings) = $params;
    db_update('behat_editor_results')
        ->fields(array(
            'settings' => $settings,
            'module' => 'behat_github'
        ))
        ->condition('rid', $rid, '=')
        ->execute();
}

function _github_behat_editor_remove_old_file(array $params){
    list($action_message, $files_to_move_or_copy_all) = $params;
    if($action_message == 'moved') {
        foreach($files_to_move_or_copy_all as $value) {
            unlink($value);
        }
    }
}