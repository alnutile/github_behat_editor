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

    public function pull(array $params = null) {
        $files = array();
        global $user;
        $message = null;
        if(!empty($params)) {
            $this->full_path_to_file_folder = $params['full_path_to_repo_folder'];
            $files = $params['files'];
            $user = $params['user'];
            $message = $params['message'];
        }
        //1. clean up the folder if there is work in there
        $this->git = Repository::open($this->full_path_to_file_folder, $this->git_path);
        if(!$this->git->isDirty()) {
            return $this->gitPull();
        } else {
            return FALSE;
        }
    }

    /**
     *
     */
    public function delete($data){
        global $user;
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
        $this->git->removeFile($this->full_path_to_file, $commitMsg = "Commit via behat editor by $user->name", $recursive = false, $force = false, $author = null);
        $results = $this->gitPull();
        if($results['error'] == 1) {
            return array('error' => 1, 'message' => $results['message']);
        }
        $results = $this->gitPush();
        if($results['error'] == 1) {
            return array('error' => 1, 'message' => $results['message']);
        }
        $message = $this->git->getLog(1);
        watchdog('github_behat_editor', t('Git delete message !message'), array('!message' => implode("\n", $message)), WATCHDOG_NOTICE);
        return array('error' => 0, 'message' => t('File deleted from git repo and committed.'));
    }

    protected function gitPull() {
        exec("cd $this->full_path_to_file_folder && git pull", $output, $return_var);

        $message = implode("\n", $output);
        if($return_var == 1) {
            watchdog('github_behat_editor', t('During the git pull action there was this error !error'), array('!error' => $message), WATCHDOG_ERROR);
            return array('error' => 1, 'message' => $message);
        } else {
            return array('error' => 0, 'message' => $message);
        }
    }

    protected function gitPush(){
        exec("cd $this->full_path_to_file_folder && git push", $output, $return_var);

        $message = implode("\n", $output);
        if($return_var == 1) {
            watchdog('github_behat_editor', t('During the git push action there was an error !error'), array('!error' => $message), WATCHDOG_ERROR);
            return array('error' => 1, 'message' => $message);
        } else {
            return array('error' => 0, 'message' => $message);
        }
    }

    /**
     * @todo merge create and update code into a more generic
     * method for both create and update to share
     *
     * @param $data
     * @return array
     */
    public function create($data){
        global $user;
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
        $this->git->commit("Commit via behat editor by $user->name", array($this->full_path_to_file), $author = null);
        exec("cd $this->full_path_to_file_folder && git pull", $output, $return_var);
        //@todo work gitPull and gitPush into here
        if($return_var == 1) {
            $message = implode("\n", $output);
            watchdog('github_behat_editor', t('During the git pull action there was this error !error'), array('!error' => $message), WATCHDOG_ERROR);
            return array('error' => 1, 'message' => $message);
        }
        exec("cd $this->full_path_to_file_folder && git push", $output, $return_var);
        if($return_var == 1) {
            $message = implode("\n", $output);
            watchdog('github_behat_editor', t('During the git push action there was an error !error'), array('!error' => $message), WATCHDOG_ERROR);
            return array('error' => 1, 'message' => $message);
        }
        $message = $this->git->getLog(1);
        watchdog('github_behat_editor', t('Git push message !message'), array('!message' => implode("\n", $message)), WATCHDOG_NOTICE);
        return array('error' => 0, 'message' => t('File added to git and committed.'));
    }


    public function update($data){
        global $user;
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
        //@todo work gitPull and gitPush into here
        if($this->git->isDirty()) {
            $this->git->commit("Commit via behat editor by $user->name", array($this->full_path_to_file), $author = null);
            exec("cd $this->full_path_to_file_folder && git pull", $output, $return_var);
            if($return_var == 1) {
                $message = implode("\n", $output);
                watchdog('github_behat_editor', t('During the git pull action there was this error !error'), array('!error' => $message), WATCHDOG_ERROR);
                return array('error' => 1, 'message' => $message);
            }
            exec("cd $this->full_path_to_file_folder && git push", $output, $return_var);
            if($return_var == 1) {
                $message = implode("\n", $output);
                watchdog('github_behat_editor', t('During the git push action there was an error !error'), array('!error' => $message), WATCHDOG_ERROR);
                return array('error' => 1, 'message' => $message);
            }
            $message = $this->git->getLog(1);
            watchdog('github_behat_editor', t('Git push message !message'), array('!message' => implode("\n", $message)), WATCHDOG_NOTICE);
            //@todo offer modal for more feedback
            return array('error' => 0, 'message' => t('File commited and pushed to repo. See logs for more info.'));
        }
        watchdog('github_behat_editor', t('No changes to the file'), array(), WATCHDOG_NOTICE);
        return array('error' => 0, 'message' => 'No changes to the file so no git commit');
    }

    /**
     * Use null for files have not tested with many files and a commit push
     *
     * @param $full_path_to_repo_folder
     * @param array $files
     * @return array
     */
    public function updateManyFiles($full_path_to_repo_folder, $sub_folder, array $files = null, $message = FALSE) {
        global $user;
        $this->full_path_to_file_folder = $full_path_to_repo_folder . '/' . $sub_folder;
        $this->full_path_to_repo_folder = $full_path_to_repo_folder;
        $this->git = Repository::open($this->full_path_to_repo_folder, $this->git_path);
        $this->git->add($files);
        $this->git->commit("Commit via behat editor by $user->name $message", NULL, $author = null);
        $results = $this->gitPull();
        if($results['error'] == 1) {
            return array('error' => 1, 'message' => $results['message']);
        }
        $results = $this->gitPush();
        if($results['error'] == 1) {
            return array('error' => 1, 'message' => $results['message']);
        }
        $message = $this->git->getLog(1);
        watchdog('github_behat_editor', t('Git delete message !message'), array('!message' => implode("\n", $message)), WATCHDOG_NOTICE);
        return array('error' => 0, 'message' => t('Files Committed to repo'));
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