<?php

use Drupal\GithubBehatEditor;

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
    $options = array();
    foreach($existing_repos_user['results'] as $key => $value ){

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
    dpm($form_state['values']);

    //1. get the repo info to build a path
    list($id, $gid, $uid, $repo_name, $folder) = explode('|', $form_state['values']['repo_name']);
    ($gid != 0) ? $root = 'groups' : $root = 'users';
    ($gid != 0) ? $id = $gid : $id = $uid;
    $destination = file_build_uri("/behat_github/" . $root . "/" . $id . "/" . $repo_name . "/" . $folder);

    //2. Decide if this is move of copy
    if($form_state['values']['move'] == 1) {
        $action = 'file_unmanaged_move';
        $action_message = 'moved';
    } else {
        $action = 'file_unmanaged_copy';
        $action_message = 'copied';
    }

    //3. for each file move or copy to that path
    $full_path_with_filename_all = array();
    foreach($form_state['values']['files'] as $value) {
        //Full path
        $file_to_move_or_copy       = $value;
        $file_to_add_to_repo        = array_pop(explode("/", $value));
        $full_path_with_filename    = drupal_realpath($destination . "/$file_to_add_to_repo");
        $full_path_with_filename_all[] = $full_path_with_filename;
        $action($file_to_move_or_copy, $full_path_with_filename, $replace = FILE_EXISTS_REPLACE);
        drupal_set_message(t('File !action to !destination', array('!action' => $action_message, '!destination' => $full_path_with_filename)));
    }

    dpm($full_path_with_filename_all);

    //4. Update that repo to do a add, commit pull push
    //$git = Repository::open(drupal_realpath($destination));
    //$git->add(array($this->full_path_to_file));
    //

}