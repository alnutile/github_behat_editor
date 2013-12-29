<?php

namespace Drupal\GithubBehatEditor;

use TQ\Git\StreamWrapper\StreamWrapper,
    TQ\Git\Cli\Binary;

use Drupal\BehatEditor;

/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/10/13
 * Time: 4:08 AM
 */

class RepoModel {
    private $uid;
    public $results;
    public $repos;
    public $repos_chosen = array();
    public $client;
    public $git_path;
    public $public_absolute_path;
    public $repo_name;
    public $gid;

    const URI = 'github.com';
    const PROTOCOL = 'https';
    const GITHUB_FOLDER = 'behat_github';

    public function __construct() {
        composer_manager_register_autoloader();
        $this->client = github_api_client();
    }

    public function getAllRepos() {
        $this->client->api('current_user')->setPerPage(200);
        $this->repos =  $this->client->api('current_user')->repositories();
        return $this->repos;
    }

    public function getUserRepos($uid){
        $this->uid = $uid;
        $queryRepos = new GithubRepoQueries();
        $results = $queryRepos->selectAllByUid($this->uid);
        $records = $results['results'];
        return array('results' => $records, 'error' => $results['error']);
    }


    public function getUserRepoByRepoName($params){
        $repos = array();
        $this->uid = $params['uid'];
        $repo_name = $params['repo_name'];
        $queryRepos = new GithubRepoQueries();
        $results = $queryRepos->selectAllByUid($this->uid);
        $records = $results['results'];
        foreach($records as $key => $value) {
            if($value['repo_name'] == $repo_name) {
                $repos[] = $value;
            }
        }
        return $repos;
    }

    public function getUsersGroupRepoByRepoName($params){
        $repos = array();
        $repo_name = $params['repo_name'];
        $this->uid = $params['uid'];
        $settings = new BehatEditor\BehatPermissions($this->uid);
        $gids = $settings->getGroupIDs();
        $queryRepos = new GithubRepoQueries();
        $results = $queryRepos->selectAllByGid($gids);
        $records = $results['results'];
        foreach($records as $key => $value) {
            if($value['repo_name'] == $repo_name) {
                $repos[] = $value;
            }
        }
        return $repos;
    }

    public function getGroupRepos($params){
        $this->uid = $params['uid'];
        $settings = new BehatEditor\BehatPermissions($this->uid);
        $gids = $settings->getGroupIDs();
        $queryRepos = new GithubRepoQueries();
        $results = $queryRepos->selectAllByGid($gids);
        $records = $results['results'];
        return array('results' => $records, 'error' => $results['error']);
    }

    public function getGroupReposByGid(array $gids){
        $queryRepos = new GithubRepoQueries();
        $results = $queryRepos->selectAllByGid($gids);
        $records = $results['results'];
        return array('results' => $records, 'error' => $results['error']);
    }

    public function getGroupRepo($params){
        $this->gid = $params['gid'];
        $this->repo_name = $params['repo_name'];
        $queryRepos = new GithubRepoQueries();
        $results = $queryRepos->selectAllByGidAndRepoName($this->gid, $this->repo_name);
        $records = $results['results'];
        return array('results' => $records, 'error' => $results['error']);
    }

    public function checkEditPathRedirectIfNeeded($params) {
        if($params['action'] == 'edit') {
            $path = $params['path'];
            $path[4] = 'users';
            $path[5] = $params['uid'];
            drupal_goto(implode('/', $path));
        }
    }

    public function setUserRepos(){

    }

    public function setGroupRepos(){

    }

    private function _conditions() {
        //Make the users folder
        if($this->gid > 0) {
            $relative_path = self::_build_path_group();
        } else {
            $relative_path= self::_build_path_user();
        }

        if(!file_prepare_directory($relative_path, FILE_CREATE_DIRECTORY)){
            return $relative_path;
        }
    }

    /**
     *
     *  array of repos so I do not have to requery github
     *  each array item is a string with a | separator.
     *  this way the needed values can be stored in the form
     *  git_id|html_url|name|uid|full_name
     *
     */
    public function insertRepo(array $params) {
        $query = new GithubRepoQueries();
        $fields = $query->fields();
        foreach($params['repos'] as $value_of_url_and_id) {
            $split_value = explode('|', $value_of_url_and_id);
            if(!isset($split_value[0])) {
                break;
            }
            //Test if we can even make this work
            $folder = $this->_conditions();
            if($folder){
                drupal_set_message(t("Could not make folder for @repo so it was not added to your list of repos. Please contact system admin to see about write permissions on the folder @folder", array('@repo' => $split_value[2], '@folder' => $folder)));
                break;
            }
            if(isset($params['gid'])) {
                $fields->gid = $params['gid'];
            }
            $repo_account = explode('/', $split_value[4]); //grab the account from the full name
            $fields->github_id = $split_value[0];
            $fields->repo_url = $split_value[1];
            $fields->repo_name = $split_value[2];
            $fields->repo_account = $repo_account[0];
            $fields->uid = $split_value[3];
            $fields->folder = $params['folder'];
            $results = $query->insertRepo((array) $fields);
            $this->repos_chosen[] = $split_value[4];

            //@todo trap
            if(empty($results)) {
                $repo_name = (isset($split_value[2])) ? $split_value[2] : 'no name found';
                drupal_set_message(t("Problem inserting github repo @name", array('@name' => $repo_name)), 'error');
            } else {
                $repo_name = $split_value[2];
                drupal_set_message(t("Inserted github repo @name", array('@name' => $repo_name)), 'status');
            }
        }
        //@todo pass back more info
        return array('results' => 0, 'error' => 0);
    }

    public static function removeRepo(array $ids) {
        //@todo ACL?
        GithubRepoQueries::deleteRepo($ids);
    }

    /**
     * @param array $repos eg useraccount/repo_name
     */
    public function cloneRepoGroup(array $repos, array $params) {

        foreach($repos as $repo) {
            $repo_params = explode('/', $repo);
            $this->repo_name = $repo_params[1];
            $this->uid = $params['uid'];
            $this->gid = $params['gid'];
            $this->public_absolute_path = drupal_realpath($this->_build_path_group());
            //Get username and passwords
            $username = variable_get('github_api_username');
            $password = variable_get('github_api_password');
            $full_repo_path = RepoModel::PROTOCOL . "://$username:$password@".RepoModel::URI."/$repo";

            if( self::_conditions() ) {
                drupal_set_message(t('Folder @folder already exists so I will not create it', array('@folder' => $this->repo_name)), 'info');
                return array('error' => 1, 'response' => 'Folder already exists I will not create it');
            }

            $git = new GitActions();
            watchdog('test_group_path', print_r($this->public_absolute_path, 1));
            if($git->checkIfGitFolder($this->public_absolute_path)) {
                $clone = $git->gitClone(array('destination' => $this->public_absolute_path, 'full_repo_path' => $full_repo_path, 'use_current_path' => TRUE));
                if($clone['error'] == 0) {
                    drupal_set_message(t("There is not a git folder @folder so a new clone was made.", array('@folder' => $this->public_absolute_path)));
                } else {
                    drupal_set_message(t("There was a problem during the clone here is the @output", array('@output' => implode($clone['response']))));
                }
            } else {
                drupal_set_message(t("This @folder is a git folder already so we will just leave it alone for now.", array('@folder' => $this->public_absolute_path)));
            }
        }
    }


    /**
     * @param array $repos eg useraccount/repo_name
     */
    public function cloneRepo(array $repos, array $params) {

        foreach($repos as $repo) {
            $repo_params = explode('/', $repo);
            $this->repo_name = $repo_params[1];
            $this->uid = $params['uid'];
            $this->public_absolute_path = drupal_realpath($this->_build_path_user());
            //Get username and passwords
            $username = variable_get('github_api_username');
            $password = variable_get('github_api_password');
            $full_repo_path = RepoModel::PROTOCOL . "://$username:$password@".RepoModel::URI."/$repo";

            $this->gid = 0;
            if( self::_conditions() ) {
                drupal_set_message(t('Folder @folder already exists so no new clone', array('@folder' => $this->repo_name)), 'info');
                watchdog('github_behat_editor_clone_repo', t('Folder @folder already exists so no new clone', array('@folder' => $this->repo_name)), 'info');
                return array('error' => 0, 'response' => 'Folder already exists so will not clone again');
            }

            $git = new GitActions();
            if($git->checkIfGitFolder($this->public_absolute_path)) {
                watchdog('test_repo_path', print_r($full_repo_path, 1));
                watchdog('test_public_absolute', print_r($this->public_absolute_path, 1));
                $clone = $git->gitClone(array('destination' => $this->public_absolute_path, 'full_repo_path' => $full_repo_path, 'use_current_path' => TRUE));
                if($clone['error'] == 0) {
                    $message = t("There is not a git folder @folder so a new clone was made.", array('@folder' => $this->public_absolute_path));
                    drupal_set_message($message);
                    watchdog('github_behat_editor', $message, $variables = array(), $severity = WATCHDOG_NOTICE, $link = FALSE);
                } else {
                    $message = t("There was a problem during the clone here is the @output", array('@output' => implode($clone['response'])));
                    drupal_set_message($message);
                    watchdog('github_behat_editor', $message, $variables = array(), $severity = WATCHDOG_NOTICE, $link = FALSE);
                }
            } else {
                $message = t("This is a git folder already", array('@folder' => $this->public_absolute_path));
                drupal_set_message($message);
                watchdog('github_behat_editor', $message, $variables = array(), $severity = WATCHDOG_NOTICE, $link = FALSE);
            }
        }
    }


    private function _build_path_group() {
        $repo_name = $this->repo_name;
        $gid = $this->gid;
        $path = "public://".RepoModel::GITHUB_FOLDER."/groups/$gid";
        file_prepare_directory($path, FILE_CREATE_DIRECTORY);
        $public = file_build_uri("/".RepoModel::GITHUB_FOLDER."/groups/$gid");
        $relative_path = $public . '/' . $repo_name;
        return $relative_path;
    }

    private function _build_path_user() {
        $repo_name = $this->repo_name;
        $uid = $this->uid;
        $path = "public://".RepoModel::GITHUB_FOLDER."/users/$uid";
        file_prepare_directory($path, FILE_CREATE_DIRECTORY);
        $public = file_build_uri("/".RepoModel::GITHUB_FOLDER."/users/$uid");
        $relative_path = $public . '/' . $repo_name;
        return $relative_path;
    }

    public function pullRequest(array $repos) {
        $public = file_build_uri("/behat_github/");
        foreach($repos as $repo) {
            //split the info
            $repo_params = explode('/', $repo);
            $repo_name = $repo_params[1];
            $full_path = $public . '/' . $repo_name;
            $public_realpath = drupal_realpath($full_path);
            if( file_exists($public_realpath) ) {
                drupal_set_message(t('Folder @folder already exists so no new clone', array('@folder' => $repo_name)), 'info');
                break;
            }
            if(!file_prepare_directory($public_realpath, FILE_CREATE_DIRECTORY)){
                drupal_set_message(t('Folder @folder could no be made', array('@folder' => $public_realpath)), 'info');
                break;
            }
            $username = variable_get('github_api_username');
            $password = variable_get('github_api_password');
            exec("cd $public_realpath && /usr/bin/git clone https://$username:$password@github.com/$repo .", $output, $return_val);
        }
    }
} 