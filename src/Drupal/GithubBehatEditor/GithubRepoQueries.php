<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/10/13
 * Time: 4:50 AM
 */

namespace Drupal\GithubBehatEditor;


class GithubRepoQueries {

    public function __construct() {
        global $user;
        $this->uid = $user->uid;
        $this->fields = self::fields();
    }

    public function selectAllByUid($uid = FALSE){
        (!empty($uid)) ? $this->uid = $uid : null ;
        $query = db_select('github_behat_editor_repos', 'r');
        $query->fields('r');
        $query->condition('r.uid', $this->uid, '=');
        $query->orderBy('r.id', 'DESC');
        $result = $query->execute();
        $rows = array();
        if ($result) {
            foreach ($result as $record) {
                $rows[] = (array) $record;
            }
        }
        return array('results' => $rows, 'error' => 0);
    }

    public function insertRepo($params) {
        $insert = db_insert('github_behat_editor_repos')->fields($params)->execute();
        return $insert;
    }

    static public function deleteRepo(array $ids) {
        db_delete('github_behat_editor_repos')
            ->condition('id', $ids, 'IN')
            ->execute();
    }

    static public function fields() {
        global $user;
        return (object) array(
            'uid' => $user->uid,
            'gid' => 0,
            'repo_name' => '',
            'repo_url' => '',
            'branch' => 'master',
            'active' => 1,
            'github_id' => 0,
        );

    }

} 