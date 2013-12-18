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


    public function __construct($params = array()) {
        composer_manager_register_autoloader();
        global $user;
        $this->user = $user;
        $this->repo_manager = new GithubBehatEditor\RepoManager();
        $this->setupParams($params);
        $this->setUserOrGroupBasedRequest();
    }

    public function getData(){
        return $this->data;
    }

    public function cloneButton() {
        $this->cloneButton = new GithubBehatEditor\GithubEditorFormHelper($this->user);
        return $this->buttonCloneToRepo();
    }


    /**
     * If the request comes in via View, Edit or Index
     * the settings will come from arg
     * Else they are being passed in a REQUEST
     * @param $params
     */
    protected function setupParams($params){
        if(isset($params) && !in_array($params['action'], $this->actions)) {
            //Run, CreateRun or Targeted Actions
            $this->service_path = $params['service_path'];
            $this->type = $this->service_path[5];
            $this->repo_name = $this->service_path[7];
            $this->action = $this->service_path[3];
            $this->module = $this->service_path[4];
            //Set Vars
            if($this->type == 'groups') {
                $this->gid = $this->service_path[6];
            } else {
                $this->gid = 0;
            }
        } else {
            if(isset($params) && $params['action'] == 'delete') {
                $this->service_path = $params['service_path'];
                $this->repo_name = $this->service_path[3];
                $this->type = $this->service_path[1];
                $this->action = 'delete';
                $this->module = $this->service_path[0];
            } else {
                $this->service_path = arg();
                $this->action = $this->service_path[2];
                $this->repo_name = $this->service_path[6];
                $this->type = $this->service_path[4];
                $this->module = $this->service_path[3];
            }
            //Set Vars
            if($this->type == 'groups') {
                $this->gid = $this->service_path[5];
                $this->repo_manager->checkEditPathRedirectIfNeeded(array('uid' => $this->user->uid, 'path' => $this->service_path, 'action' => $this->action));
            } else {
                $this->gid = 0;
            }
        }
        $service_path_tweaked = $this->service_path;
        $this->filename = array_pop($service_path_tweaked);
    }


    protected function setUserOrGroupBasedRequest() {

        if($this->gid == 0 && arg(4) != 'groups') {
            //This is a user based repo so just need to verify access
            $this->setUserBasedRequest();
        } else if ($this->gid != 0) { //Must be a group repo and view action
            $this->setGroupRequest();
        }
    }

    protected function setUserBasedRequest() {
        //Get repos and see if
        //  1. user has repo
        if(!empty($this->repo_name)) {
            $this->repo_name = arg(6);
        }
        $this->repo_manager = new RepoManager();
        $this->repos = $this->repo_manager->getUserRepoByRepoName(array('uid' => $this->user->uid, 'repo_name' => $this->repo_name));
        if(empty($this->repos['results'])) {
            //check at group level
            $this->repos = $this->repo_manager->getGroupRepos(array('uid' => $this->user->uid));
            if(empty($this->repos['results'])) {
                drupal_set_message('You do not have access to this repo');
                drupal_goto('admin/behat/index');
            }
            foreach($this->repos['results'] as $key => $value) {
                if($value['repo_name'] == $this->repo_name) {
                    $this->setupFileInfo();
                    $this->data = $this->file_info;
                    break;
                }
            }
        }
        return $this->data;
    }

    protected function setGroupRequest(){
        $this->perms = new BehatEditor\BehatPermissions($this->user->uid);
        $this->users_groups = $this->perms->getGroupIDs();
        if(!in_array($this->gid, $this->users_groups)){
            //@todo better exit plan here
            drupal_set_message('You are not in this group');
            //drupal_goto('admin/behat/index');
        }
        //See now if they already have this folder
        //Lets grab the full db info for this repo
        $this->repo_manager = new RepoManager();
        $this->repos = $this->repo_manager->getGroupRepo(array('gid' => $this->gid, 'repo_name' => $this->repo_name));
        if(empty($this->repos['results']) || $this->repos['error'] == 1){
            //@todo better exit plan here
            drupal_set_message(t('The !repo repo could not be found for the group', array('!repo' => $this->repo_name)));
            //drupal_goto('admin/behat/index');
        }
        $this->setupFileInfo();
        $this->data = $this->file_info;
        return $this->data;
    }

    private function setupFileInfo(){
        if (isset($this->repos)) {
            $this->repo_data = $this->repos['results'][0];
            $this->test_folder = $this->repo_data['folder'];
            $this->repo_account = $this->repo_data['repo_account'];
            $this->full_name = $this->repo_account .'/'. $this->repo_name;
            $this->repo_manager->cloneRepo(array($this->full_name), array('uid' => $this->user->uid, ''));
            $this->github_download_files = new GithubBehatEditor\GithubDownloadedFile();
            $this->params = $this->fileObjectParams();
            $this->file_info = $this->github_download_files->buildObject($this->params);
        }
    }

    private function fileObjectParams() {
        return array(
            'service_path' => $this->service_path,
            'module' => $this->module,
            'filename' => $this->filename,
            'action' => $this->action,
            'subfolder' => $this->test_folder,
        );
    }

}