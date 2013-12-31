<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/18/13
 * Time: 10:13 AM
 */

namespace Drupal\GithubBehatEditor;

use Drupal\GithubBehatEditor;


class GithubEditorFormHelper {
    protected $user;
    public $all_repos = array();

    public function __construct($user){
        $this->user = $user;
    }

    public function buttonCloneToRepo() {
        $button = array();
        $this->getAllRepos();
        return $button;
    }

    public function buttonAdd() {
        $button = array();
        $this->getAllRepos();
        dpm($this->all_repos);
        return $button;
    }

    protected function getAllRepos() {
        $repos = new GithubRepoQueries();
        $all_repos = $repos->selectAllByUid($this->user->uid);
        $this->all_repos = $all_repos['results'];
    }


} 