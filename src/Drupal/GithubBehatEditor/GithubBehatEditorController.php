<?php

namespace Drupal\GithubBehatEditor;

use Drupal\GithubBehatEditor,
    Drupal\BehatEditor;

class GithubBehatEditorController {
    protected $actions = array('view', 'edit', 'delete');
    protected $repo_manager;
    protected $params = array();
    protected $service_path = array();
    protected $type = '';
    protected $repo_name = '';
    protected $action = '';
    protected $relative_path;
    protected $module = '';
    protected $gid = 0;
    protected $filename = '';
    protected $repos = array();
    protected $repo_data = array();
    protected $repo_account = '';
    protected $test_folder = '';
    protected $full_name = '';
    protected $github_download_files = '';
    protected $file_info = '';
    protected $data = '';
    protected $users_groups = array();
    protected $perms = array();
    protected $arg = array();
    public $repos_by_repo_name = array();
    public $files_array = array();
    public $files_array_alter = array();
    public $user_and_group_repos = array();


    public function __construct($params = array()) {
        composer_manager_register_autoloader();
        global $user;
        $this->user = $user;
        $this->repo_manager = new GithubBehatEditor\RepoModel();
    }

    /**
     * This is here to keep a user out of Edit Group/Team repo
     * But redirect them to their repo.
     * @param $params
     */
    public function redirectFromGroupToUserRepo($params) {
        $this->data = $params['data'];
        $this->arg = $params['arg'];
        $path = explode('/', $this->data['absolute_path']);
        $path_start = array_search('behat_github', $path);
        $group_or_user = $path[$path_start + 1];
        $this->action = $params['mode'];
        $this->service_path = $path;
        if($group_or_user == 'groups') {
            $this->repo_manager->checkEditPathRedirectIfNeeded(array('uid' => $this->user->uid, 'path' => $this->arg, 'action' => $this->action));
        }
    }

    /**
     * Check Access
     */
    public function checkAccess($params){
        $this->data = $params['data'];
        $this->arg = $params['arg'];
        $path = explode('/', $this->data['absolute_path']);
        $path_start = array_search('behat_github', $path);
        $group_or_user = $path[$path_start + 1];
        $this->action = $params['mode'];
        $this->service_path = $path;
        if($group_or_user == 'groups') {
            $this->checkGroupRequest();
            $this->checkGroupRepoAccess();
        }
        //if groups check user access to group and repo

        //if users check users access to repo
    }

    protected function checkGroupRequest(){
        $this->perms = new BehatEditor\BehatPermissions($this->user->uid);
        $this->users_groups = $this->perms->getGroupIDs();
        if(!in_array($this->gid, $this->users_groups)){
            //@todo better exit plan here
            drupal_set_message('You are not in this group');
            //drupal_goto('admin/behat/index');
        }
    }

    protected function checkGroupRepoAccess(){
        $this->repos = $this->repo_manager->getGroupRepo(array('gid' => $this->gid, 'repo_name' => $this->repo_name));
        if(empty($this->repos['results']) || $this->repos['error'] == 1){
            //@todo better exit plan here
            drupal_set_message(t('The !repo repo could not be found for the group', array('!repo' => $this->repo_name)));
            //drupal_goto('admin/behat/index');
        }
    }

    /**
     * Get the users repos and the groups repos
     * merge the array this leaves us the users ones first.
     * then for each one
     * get the relatated files
     *
     * @param array $data
     */
    public function index($data = array()){
        //@todo move much of this logic into model
        $this->files_array = $data;
        $this->getUserRepos();
        $this->updateAllRepos($this->user_and_group_repos);
        $this->repos_by_repo_name = $this->user_and_group_repos;
        $this->getUsersGroupRepo();
        $this->updateAllRepos($this->user_and_group_repos, 'groups');
        $this->repos_by_repo_name = array_merge($this->user_and_group_repos, $this->repos_by_repo_name);
        //now parse the directories for these files
        $this->getRepoFiles();
        $this->files_array = array_merge($this->files_array, $this->files_array_alter);
        return $this->files_array;
    }


    public function update($data = array()) {
        $git_action = new GitActions();
        $results = $git_action->update($data);
        return $results;
    }

    public function create($data = array()) {
        $git_action = new GitActions();
        $results = $git_action->create($data);
        return $results;
    }

    public function delete($data = array()) {
        $git_action = new GitActions();
        $results = $git_action->delete($data);
        return $results;
    }

    public function getAllReposForUser($user) {
        $this->getUserRepos();
        $this->repos_by_repo_name = $this->user_and_group_repos;
        $this->getUsersGroupRepo();
        $this->repos_by_repo_name = array_merge($this->user_and_group_repos, $this->repos_by_repo_name);
        //now parse the directories for these files
        return $this->repos_by_repo_name;
    }

    /**
     * Check if folder exists
     * if not make folder and do a clone
     *
     */
    public function checkIfRepoFolderExists(array $repos){
        foreach($repos as $key => $value) {
            $path = $this->repoBasePath($value);
            $repo_root_exists = file_exists($path);
            $account_and_reponame = $value['repo_account'] . '/' . $value['repo_name'];
            /**
             * Need to see if the folder exists if not make it and then clone
             */
            if(!$repo_root_exists) {
                drupal_mkdir($path, $mode = NULL, $recursive = TRUE);
            }

            $repo_actions = new RepoModel();
            $repo_actions->cloneRepo(array($account_and_reponame), array('uid' => $value['uid'], 'gid' => $value['gid']));
        }
    }


    /**
     * Setup path
     * from behat_github/users or groups/ID/repo_name
     * and get absolute path
     *
     * @param $value array
     *   repo_name
     *   gid
     *   uid
     *   folder
     */
    protected function repoBasePath($value) {
        $this->repoRelativePath($value);
        if(($value['gid'] == 0)) {
            $type = 'users';
        } else {
            $type = 'groups';
        }
        $path_uri =  file_build_uri("/behat_github/");
        $path_uri = drupal_realpath($path_uri);
        $path_full = $path_uri . '/' . $type . '/' . $this->relative_path;
        return $path_full;
    }

    /**
     * Setup the folder path starting at id
     * @param $value
     * @return string
     */
    protected function repoRelativePath($value) {
        if(($value['gid'] == 0)) {
            $id = $value['uid'];
        } else {
            $id = $value['gid'];
        }
        $repo_name = $value['repo_name'];
        $this->relative_path = "$id/$repo_name";
        return $this->relative_path;
    }

    /**
     * @params
     *  array of user repos from Repomanager::getUserRepos
     *
     */
    public function updateAllRepos($repos, $type = 'users') {
        //Repos start in behat_github
        //from there depending on the $type is where they sit
        foreach($repos as $key => $value){
            $repo_name = $value['repo_name'];
            $folder = $value['folder'];
            ($type == 'users') ? $id = $value['uid'] : $id = $value['gid'];
            $path = "behat_github/$type/$id/$repo_name/$folder";
            $path_uri =  file_build_uri("/{$path}/");
            $absolute_path = drupal_realpath($path_uri);
            exec("cd $absolute_path && git pull", $output, $return_val);
        }
    }

    public function getUserRepos($keyed_by_name = TRUE){
        $repos = $this->repo_manager->getUserRepos($this->user->uid);
        $this->repos = $repos['results'];
        if($keyed_by_name) {
            $this->user_and_group_repos = $this->keyReposByName();
        } else {
            $this->user_and_group_repos = $this->repos;
        }
        return $this->user_and_group_repos;
    }

    protected  function getUsersGroupRepo(){
        $repos = $this->repo_manager->getGroupRepos($this->user->uid);
        $this->repos = $repos['results'];
        $this->user_and_group_repos = $this->keyReposByName();
    }

    protected function keyReposByName() {
        $repos_by_name = array();
        if(isset($this->repos)){
            foreach($this->repos as $key => $value){
                if($value['active'] == 1) {
                    $repos_by_name[$value['repo_name']]['repo_name'] = $value['repo_name'];
                    $repos_by_name[$value['repo_name']]['gid'] = $value['gid'];
                    $repos_by_name[$value['repo_name']]['uid'] = $value['uid'];
                    $repos_by_name[$value['repo_name']]['folder'] = $value['folder'];
                }
            }
        }
        return $repos_by_name;
    }

    /**
     * FileModal Class _buildArrayOfAvailableFilesInPublicFolders
     * can replace this class
     * if I made an interface for these different file types
     */
    protected function getRepoFiles() {
        $filename = null;
        $file_data = array();
        foreach($this->repos as $key => $value) {
            ($value['gid'] == 0) ? $base = 'users' : $base = 'groups';
            ($value['gid'] == 0) ?  $id = $value['uid'] : $id = $value['gid'];
            $service_path = "behat_github/{$base}/{$id}/{$value['repo_name']}/{$value['folder']}";

            $root_path = file_build_uri("$service_path");
            $full_root_path = drupal_realpath($root_path);
            $files = file_scan_directory($full_root_path, '/.*\.feature/', $options = array('recurse' => TRUE), $depth = 0);
            $file_data = array();
            foreach($files as $file_key => $file_value) {
                $array_key =$file_value->uri;
                $filename = $file_value->filename;
                $full_service_path_string = $service_path . '/' . $filename;
                $full_service_path_array = explode('/', $full_service_path_string);
                $params = array(
                    'filename' => $filename,
                    'module' => 'behat_github',
                    'parse_type' => 'file',
                    'service_path' => $full_service_path_array
                );
                $file = new BehatEditor\FileModel($params);
                $file_data[$array_key] = $file->getFile();
            }
            $this->files_array_alter[$value['repo_name']] = $file_data;
        }
    }

}