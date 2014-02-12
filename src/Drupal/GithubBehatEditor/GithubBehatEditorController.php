<?php

namespace Drupal\GithubBehatEditor;

use Drupal\GithubBehatEditor,
    Drupal\BehatEditor;
use TQ\Git\Repository\Repository,
    TQ\Git\Cli\Binary;

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
    public $repoClass;


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
            $this->gid = $params['arg'][5];
            $this->checkGroupRequest();
            $this->checkGroupRepoAccess();
        }
        //@TODO right now there is a redirect to keep a user on their
        //  own pages. But this could help as well.
        //  check for user repos
        //  check for users groups repos
        //  if no match redirect
    }

    protected function checkGroupRequest(){
        $this->perms = new BehatEditor\BehatPermissions($this->user->uid);
        $this->users_groups = $this->perms->getGroupIDs();
        if(!in_array($this->gid, $this->users_groups)){
            //@todo better exit plan here
            drupal_set_message('You are not in this group');
            drupal_goto('admin/behat/index');
        }
    }

    protected function checkGroupRepoAccess(){
        $this->repos = $this->repo_manager->getGroupRepo(array('gid' => $this->gid, 'repo_name' => $this->repo_name));
        if ( (empty($this->repos['results']) || $this->repos['error'] == 1) && isset($this->repo_name) ) {
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
        $all_repos = array();
        $this->getUserRepos();
        $this->updateAllRepos($this->user_and_group_repos);
        $all_repos = $this->user_and_group_repos;
        $this->getUsersGroupRepo();
        $this->updateAllRepos($this->user_and_group_repos, 'groups');
        $all_repos = array_merge($this->user_and_group_repos, $all_repos);
        $this->repos = $all_repos;
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

    public function pull(array $params = array()) {
        $git_actions = new GitActions();
        $results = $git_actions->pull($params);
        return $results;
    }

    /**
     * Check if folder exists
     * if not make folder and do a clone
     * @todo this method is doing too much too
     *
     */
    public function checkIfRepoFolderExists(array $repos, $uid = FALSE){

        foreach($repos as $key => $value) {
            $user = user_load($value['uid']);
            $path = $this->repoBasePath($value);
            $repo_root_exists = file_exists($path);
            $account_and_reponame = $value['repo_account'] . '/' . $value['repo_name'];
            /**
             * Need to see if the folder exists if not make it and then clone
             */

            if(!$repo_root_exists) {
                $repo_actions = new RepoModel();
                drupal_mkdir($path, $mode = NULL, $recursive = TRUE);
                drupal_chmod($path, $mode = 0775);
                if(!isset($uid)) {
                    $uid = $value['uid'];
                }
                $repo_actions->cloneRepo(array($account_and_reponame), array('uid' => $uid, 'gid' => $value['gid']));
            } else {
                //do a pull to make sure it is up to date
                $path_with_folder = $path;
                $results = $this->pull(array('full_path_to_repo_folder' => $path_with_folder, 'user' => $user, 'files' => null, 'message' => t('Group update to repo')));
                return $results;
            }
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

    public function getUsersGroupRepo($keyed_by_name = TRUE){
        $repos = $this->repo_manager->getGroupRepos(array('uid' => $this->user->uid));
        $this->repos = $repos['results'];
        if($keyed_by_name) {
            $this->user_and_group_repos = $this->keyReposByName();
        } else {
            $this->user_and_group_repos = $this->repos;
        }
        return $this->user_and_group_repos;
    }

    public function getReposByRepoTableId(array $repos_ids, $keyed_by_name = TRUE) {
        $repos = $this->repo_manager->getReposByTableId($repos_ids);
        $this->repos = $repos['results'];
        if($keyed_by_name) {
            $this->user_and_group_repos = $this->keyReposByName();
        } else {
            $this->user_and_group_repos = $this->repos;
        }
        return $this->user_and_group_repos;
    }

    public function getReposByRepoGroupId(array $repos_ids, $keyed_by_name = TRUE) {
        $repos = $this->repo_manager->getGroupReposByGid($repos_ids);
        $this->repos = $repos['results'];
        if($keyed_by_name) {
            $this->user_and_group_repos = $this->keyReposByName();
        } else {
            $this->user_and_group_repos = $this->repos;
        }
        return $this->user_and_group_repos;
    }

    public function getReposByRepoGroupIdAndRepoName($gid, $name, $keyed_by_name = TRUE) {
        $repos = $this->repo_manager->getGroupReposByGidAndName($gid, $name);
        $this->repos = $repos['results'];
        if($keyed_by_name) {
            $this->user_and_group_repos = $this->keyReposByName();
        } else {
            $this->user_and_group_repos = $this->repos;
        }
        return $this->user_and_group_repos;
    }

    public function getRepoByRepoName($name, $keyed_by_name = TRUE) {
        $repos = $this->repo_manager->getRepoByRepoName($name);
        $this->repos = $repos['results'];
        if($keyed_by_name) {
            $this->user_and_group_repos = $this->keyReposByName();
        } else {
            $this->user_and_group_repos = $this->repos;
        }
        return $this->user_and_group_repos;
    }

    public function getReposByRepoUserIdAndRepoName($uid, $name, $keyed_by_name = TRUE) {
        $repos = $this->repo_manager->getUsersReposByUidAndName($uid, $name);
        $this->repos = $repos['results'];

        if($keyed_by_name) {
            $this->user_and_group_repos = $this->keyReposByName();
        } else {
            $this->user_and_group_repos = $this->repos;
        }
        return $this->user_and_group_repos;
    }

    public function getUsersGroupRepoByGid(array $gid){
        $repos = $this->repo_manager->getGroupReposByGid($gid);
        return $repos['results'];
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
                    $repos_by_name[$value['repo_name']]['repo_account'] = $value['repo_account'];
                    $repos_by_name[$value['repo_name']]['repo_url'] = $value['repo_url'];
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

    public function setRepoArrayForUserFromGid(array $params) {
        list($repo_array, $uid, $repo_name) = $params;
        if(empty($repo_array)) {
            $perms = new BehatEditor\BehatPermissions($uid);
            $groups = $perms->getGroupIDs();
            if(!empty($groups)){
                $repo_array_groups = $this->getReposByRepoGroupId($groups);
                while(empty($repo_array)) {
                    foreach($repo_array_groups as $key => $value) {
                        if($key == $repo_name) {
                            $value['gid'] = 0;
                            $repo_array = array($value);
                        }
                    }
                }
            }
        }
        return $repo_array;
    }


    public function updateFiles(array $params) {
        list($repo_array) = $params;
        if(!empty($repo_array)) {
            $repo_array = array_pop($repo_array);
            $path = $this->get_full_path_using_repo_query_results(array('repo_array' => $repo_array));
            if ( !$this->checkIfFolderExists(array('repo_array' => $repo_array)) ) {
                $path = $this->getFullPath(array('repo_array' => $repo_array));
                $output = $this->setFolderForGit( array('full_path' => $path) );
            }

            if ( !$this->checkIfGitFolderExists(array('repo_array' => $repo_array)) ) {
                //do a git clone in this folder
                $repo_url = $this->buildAuthGitUrl(array('repo_array' => $repo_array));
                $this->cloneRepo(array('destination' => $path, 'use_current_path' => TRUE, 'full_repo_path' => $repo_url));
            } else {
                $this->checkIfDirty(array('full_path' => $path));
                $test_folder = $path . '/' . $repo_array['folder'];
                $output = $this->simplePull(array('full_path' => $test_folder));
            }

            return $output;
        }
    }

    public function updateFileShow(&$data) {
                $action = arg(2);
                drupal_set_message("Updating file based on latest github info");
                $file = new BehatEditor\FileController();
                $filename = array_pop(arg());
                $module = arg(3);
                $service_path = array_slice(arg(), 3, count(arg()));
                $params = array(
                    'service_path' => $service_path,
                    'module' => $module,
                    'filename' => $filename,
                    'action' => $action
                );
                $data = $file->show($params);
    }


    /**
     * Not happy with all the methods I made that do too much
     * This one will break out into smaller pieces and begin the process
     * of making it easier to reuse these helpers/methods
     * @param array $params
     * @return TRUE if exists
     */
    public function checkIfFolderExists(array $params) {
        $path = $this->get_full_path_using_repo_query_results($params);
        $full_path = drupal_realpath($path);
        return file_exists($full_path);
    }

    public function checkIfGitFolderExists(array $params) {
        $full_path = $this->get_full_path_using_repo_query_results($params);
        return file_exists($full_path . '/.git');
    }

    public function get_full_path_using_repo_query_results(array $params){
        $repo_db_results_array = $params['repo_array'];
        $root = 'behat_github';
        if($repo_db_results_array['gid'] == 0) {
            $type = 'users';
            $id = $repo_db_results_array['uid'];
        } else {
            $type = 'groups';
            $id = $repo_db_results_array['gid'];
        }
        $repo_name = $repo_db_results_array['repo_name'];
        $path = file_build_uri("/behat_github/$type/$id/$repo_name");
        $full_path = drupal_realpath($path);
        return $full_path;
    }

    public function setFolderForGit(array $params) {
        $full_path = $params['full_path'];
        if (!file_prepare_directory($full_path, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
            $message = t('Folder could not be made at @folder', array('@folder' => $full_path));
            return array('message' => $message, 'error' => 1);
        } else {
            drupal_chmod($full_path, $mode = 0775); // in case drush makes it I want www-data to write to this
            $message = t('Folder made at @folder', array('@folder' => $full_path));
            return array('message' => $message, 'error' => 1);
        }
    }

    public function getFullPath(array $params) {
        $repo_array = $params['repo_array'];
        if($repo_array['gid'] == 0) {
            $id = $repo_array['uid'];
            $type = 'users';
        } else {
            $id = $repo_array['gid'];
            $type = 'groups';
        }
        $repo_name = $repo_array['repo_name'];
        $path = file_build_uri("/behat_github/$type");
        $path = drupal_realpath($path);
        $full_path = $path . "/$id/$repo_name";
        return $full_path;
    }

    public function cloneRepo(array $params) {
        $git_actions = new GitActions();
        return $git_actions->gitClone($params);

    }

    public function getAllReposForUser($user) {
        $this->getUserRepos();
        $this->repos_by_repo_name = $this->user_and_group_repos;
        $this->getUsersGroupRepo();
        $this->repos_by_repo_name = array_merge($this->user_and_group_repos, $this->repos_by_repo_name);
        //now parse the directories for these files
        return $this->repos_by_repo_name;
    }

    public function buildAuthGitUrl(array $params) {
        $repo_name = $params['repo_array']['repo_name'];
        $account_name = $params['repo_array']['repo_account'];
        $username = variable_get('github_api_username');
        $password = variable_get('github_api_password');
        return "https://$username:$password@github.com/$account_name/$repo_name";
    }

    public function checkIfDirty(array $params) {
        $path = $params['full_path'];
        $git = Repository::open($path);
        if($git->isDirty()) {
            $git->add();
            //$git->commit("Bulk add and update repos for users and groups", $file = null, $author = null);
        }
    }

    public function manualCommit(array $params) {
        $path = $params['full_path'];
        global $user;
        exec("cd $path && git commit -i -m 'Manual Commmit by Sync button user " + $user->mail + "'", $output, $return_var);
        return array('message' => implode("\n", $output), 'error' => $return_var);
    }

    public function manualAdd(array $params) {
        $path = $params['full_path'];
        exec("cd $path && git add .", $output, $return_var);
        return array('message' => implode("\n", $output), 'error' => $return_var);
    }


    public function simplePull(array $params) {
        $path = $params['full_path'];
        exec("cd $path && git pull origin master", $output, $return_var);
        return array('message' => implode("\n", $output), 'error' => $return_var);
    }

    public function simplePush(array $params) {
        $path = $params['full_path'];
        exec("cd $path && git push origin master", $output, $return_var);
        return array('message' => "Git Simple Push " . implode("\n", $output), 'error' => $return_var);
    }

    public function simpleAdd(array $params) {
        $path = $params['full_path'];
        $git = Repository::open($path);
        try {
            $git->add();
            $output = $git->getLog(1, 0);

            return array('message' => "Git Simple Add " . implode("\n", $output), 'error' => 0);
        }

        catch(\Exception $e){
            return array('message' => "Git Simple Add Error " . $e, 'error' => 1);
        }

    }

    public function simpleCommit(array $params) {
        global $user;
        $path = $params['full_path'];
        $git = Repository::open($path);
        if($git->isDirty()) {
            try {
                $git->commit("Commit via behat editor by $user->name", array($path), $author = null, array('-i'));
                $output = $git->getLog(1, 0);
                return array('message' => "Git Simple Commit " . $output, 'error' => 0);
            }
                catch(\Exception $e) {
                return array('message' => "Git Simple Commit " . $e, 'error' => 1);
            }
        } else {
            return array('message' => "Git Simple Commit isDirty returned FALSE", 'error' => 0);
        }

    }
}