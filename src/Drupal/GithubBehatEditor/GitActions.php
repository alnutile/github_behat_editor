<?php

namespace Drupal\GithubBehatEditor;

use TQ\Git\Repository\Repository,
    TQ\Git\Cli\Binary;

/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/10/13
 * Time: 4:10 AM
 */


class GitActions {
    protected $git;
    protected $git_path;
    protected $data;
    protected $full_path_to_repo_folder;
    protected $full_path_to_file_folder;
    protected $full_path_to_file;

    public function __construct() {
        composer_manager_register_autoloader();
        $this->git_path =  Binary::locateBinary();
    }

    public function checkIfGitFolder($absolute_path) {
        if(!file_exists($absolute_path . '/.git')) {
            return TRUE;
        }
    }

    public function open($repo_absolute_path){
        $this->git = Repository::open($repo_absolute_path, $this->git_path);
    }

    public function createCommit(){

    }

    public function create($data){
        $this->data = $data;
        $service_path_full_array = $this->data['service_path'];
        //This next one will get us down to the repo folder
        $service_path_full_trim_to_root_folder = array_slice($service_path_full_array, 0, 4);
        $service_path_full_trim_to_root_folder_string = implode('/', $service_path_full_trim_to_root_folder);
        $service_path_full_trim_to_root_folder_absolute = file_build_uri($service_path_full_trim_to_root_folder_string);
        $this->full_path_to_repo_folder = drupal_realpath($service_path_full_trim_to_root_folder_absolute);
        $this->full_path_to_file = $this->data['absolute_path_with_file'];
        $this->full_path_to_file_folder = $this->data['absolute_path'];
        $this->git = Repository::open($this->full_path_to_repo_folder, $this->git_path);
        $this->git->add(array($this->full_path_to_file));
        $this->git->commit("Testing 123", array($this->full_path_to_file), $author = null);
        //If results good
        //git pull in that dir
        exec("cd $this->full_path_to_file_folder && git pull", $output, $return_var);
        watchdog('test_pull', print_r($output, 1));
        exec("cd $this->full_path_to_file_folder && git push", $output, $return_var);
        watchdog('test_push', print_r($output, 1));
        //git push just that file
        //git push
        watchdog('test_git', print_r($this->git->getLog(5), 1));
        watchdog('test_git_output', print_r($output, 1));

    }

    public function setCommitStatus(){

    }

    public function getCommitStatuses(){

    }

    /**
     * @params array
     *  'destination' =>
     *    where to put the repo full path
     *  'full_repo_path' =>
     *    full url to clone the repo from
     */
    public function gitClone(array $params){
        $current = (isset($params['use_current_path']) && $params['use_current_path'] == TRUE) ? '.' : '';
        exec("cd {$params['destination']} && $this->git_path clone {$params['full_repo_path']} $current", $output, $return_val);
        return array('response' => $output, 'error' => $return_val);
    }

    public function gitPull(){

    }

    public function gitGetStatus($repo_absolute_path){

    }

    public function gitIsDirty(){

    }

    public function gitLog(){

    }

    public function listDirectory(){

    }

    public function showFile(){

    }

    public function writeFile() {

    }

    public function removeFile(){

    }

    public function renameFile(){

    }

} 