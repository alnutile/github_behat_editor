<?php

namespace Drupal\GithubBehatEditor;

/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/10/13
 * Time: 4:08 AM
 */

class RepoManager {
    private $uid;
    public $results;

    public function __construct() {
        composer_manager_register_autoloader();
    }

    public function getAllRepos() {

    }

    public function getUserRepos($uid){
        $this->uid = $uid;
        $queryRepos = new GithubRepoQueries();
        $results = $queryRepos->selectAllByUid($this->uid);
        $records = $results['results'];
        return array('results' => $records, 'error' => $results['error']);
    }

    public function getGroupRepos(array $repos){
        $fields = GithubRepoQueries::fields();
        foreach($repos as $repo) {
            $repos_split = exploded('|', $repo);
        }
    }

    public function setUserRepos(){

    }

    public function setGroupRepos(){

    }

    public function insertRepo(array $params) {
        $query = new GithubRepoQueries();
        $fields = $query->fields();
        foreach($params['repos'] as $value_of_url_and_id) {
            $split_value = explode('|', $value_of_url_and_id);
            if(!isset($split_value[0])) { break; }
            $fields->github_id = $split_value[0];
            $fields->repo_url = $split_value[1];
            $fields->repo_name = $split_value[2];
            $fields->uid = $split_value[3];
            $results = $query->insertRepo((array) $fields);
            //@todo trap
            if(empty($results)) {
                $repo_name = (isset($split_value[2])) ? $split_value[2] : 'no name found';
                drupal_set_message(t("Problem inserting github repo @name", array('@name' => $repo_name)), 'error');
            } else {
                $repo_name = $split_value[2];
                drupal_set_message(t("Inserted github repo @name", array('@name' => $repo_name)), 'status');
            }
        }
        return array('results' => 0, 'error' => 0);
    }

    public static function removeRepo(array $ids) {
        //@todo ACL?
        GithubRepoQueries::deleteRepo($ids);
    }
} 