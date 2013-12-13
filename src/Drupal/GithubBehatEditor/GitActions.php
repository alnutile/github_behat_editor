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