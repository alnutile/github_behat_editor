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

    public function __construct($user){
        $this->user = $user;
    }

    public function buttonCloneToRepo() {
        $button = array();
        $this->getAllRepos();

        return $button;
    }

    protected function getAllRepos() {
        $repos = new GithubRepoQueries();
        $users = $repos->selectAllByUid($this->user->uid);
        dpm($users);
    }


} 