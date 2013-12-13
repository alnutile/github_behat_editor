<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/12/13
 * Time: 10:02 PM
 */

namespace Drupal\GithubBehatEditor;

use Drupal\BehatEditor,
    Drupal\GithubBehatEditor;

class GithubDownloadedFiles extends BehatEditor\Files {
    public $files = '';
    public $subpath = '';
    public $modules = array();
    public $cache = TRUE;
    public $uid;
    public $gid;
    public $repos;
    public $group_repos = array();
    public $user_repos = array();
    public $relative_path;
    public $absolute_path;
    public $machine_name;
    public $repo_name;

    public function __construct(array $modules = array(), $subpath = FALSE, $cache = TRUE) {
        global $user;
        $this->uid = $user->uid;
        $this->gid = 0;
        $this->subpath = FALSE;
        $this->cache = $cache;
        $this->modules = array();
        $this->module = 'behat_github';
        $this->machine_name = 'behat_github';
        //$this->files = self::_buildModuleFilesArray();
    }

    public function getGroupFilesArray(){
        $repos = new GithubBehatEditor\RepoManager() ;
        $results = $repos->getGroupRepos($this->uid);
        $this->group_repos = $results['results'];
        $this->repos = $results['results'];
        if(!empty($this->group_repos)) {
            $files_array = self::_buildArrayOfAvailableFiles('group');
        }
        return $files_array;
    }

    public function getFilesArray() {
        return $this->files;
    }

    protected function _buildModuleFilesArray() {
        if(empty($this->modules)) {
            $modules = self::_checkForModules();
            $this->modules = array_merge($modules, self::_hasTestFolderArray());
        }
        $files_array = self::_buildArrayOfAvailableFiles();
        return $files_array;
    }

    private function _checkForModules() {
        if( $this->cache !== FALSE ) {
            if($cached = cache_get('behat_editor_modules', 'cache')) {
                return $cached->data;
            } else {
                $module_array = self::getModuleFolders();
                if($this->cache != FALSE) {
                    cache_set('behat_editor_modules', $module_array, 'cache', CACHE_TEMPORARY);
                }
            }
        } else {
            $module_array = self::getModuleFolders();
        }

        return $module_array;

    }

    public static function getModuleFolders() {
        $module_array = array();
        $modules = module_list();
        foreach ($modules as $module) {
            $path = drupal_get_path('module', $module);
            if ($status = self::_hasFolder($module, $path)) {
                $module_array[$module] = $status;
            }
        }
        return $module_array;
    }

    private static function _hasFolder($module, $path, $subpath = FALSE) {
        $status = array();
        $full_path = $path . '/' . BEHAT_EDITOR_FOLDER;
        if($subpath) {
            $full_path = $full_path . '/' . $subpath;
        }
        if(drupal_realpath($full_path)) {
            $status['exists'] = TRUE;
            $status['writable'] = (is_writeable($full_path)) ? TRUE : FALSE;
            $nice_name = system_rebuild_module_data();
            $status['nice_name'] = $nice_name[$module]->info['name'];

            return $status;
        }
    }

    public static function _hasTestFolderArray() {
        return array(
            'behat_tests' => array(
                'exists' => 1,
                'writable' => 1,
                'nice_name' => 'Behat Tmp Folder'
            )
        );
    }

    public static function getFilesByTag(array $tag) {
        $files_found = array();
        $files = new Files();
        $files_pre = $files->getFilesArray();
        foreach($files_pre as $key => $value) {
            foreach($value as $key2 => $value2) {
                //Some tags had ending string so had to
                if(isset($value2['tags_array'])) {
                    foreach($value2['tags_array'] as $tag_key => $tag_value) {
                        if(in_array(trim($tag_value), $tag)) {
                            $files_found[$key2] = $value2;
                        }
                    }
                }
            }
        }
        return $files_found;
    }

    protected function _buildArrayOfAvailableFiles($type = "user") {
        $files_found = array();
        foreach($this->repos as $key => $values) {
            $this->subpath = $values['folder'];
            $this->repo_name = $values['repo_name'];
            if($type == 'group') {
                $gid = $values['gid'];
                $this->gid = $gid;
                $this->relative_path = file_build_uri("/$this->machine_name/groups/$gid/$this->repo_name/$this->subpath");
                $this->absolute_path = drupal_realpath($this->relative_path);
                $files_found[$this->machine_name] = self::_behatEditorScanDirectories();
            } else {

            }

//            if ($machine_name == BEHAT_EDITOR_DEFAULT_STORAGE_FOLDER) {
//                $sub_folder = BEHAT_EDITOR_DEFAULT_STORAGE_FOLDER;
//                $files_folder =  file_build_uri("/{$sub_folder}/");
//                $path = drupal_realpath($files_folder);
//                $files_found[$machine_name] = self::_behatEditorScanDirectories($machine_name, $path);
//            } else {
//                $path = DRUPAL_ROOT . '/' . drupal_get_path('module', $machine_name) . '/' . BEHAT_EDITOR_FOLDER;
//                $files_found[$machine_name] =  self::_behatEditorScanDirectories($machine_name, $path);
//            }
        }
        return $files_found;
    }

    protected function _behatEditorScanDirectories() {
        $file_data = array();
        $files = file_scan_directory($this->absolute_path, '/.*\.feature/', $options = array('recurse' => TRUE), $depth = 0);
        dpm($files);
        foreach($files as $key => $value) {

            $array_key = $key;
            $found_uri = array_slice(explode('/', $files[$key]->uri), 0, -1); //remove file name
            $base_uri = explode('/', $this->absolute_path);
            dpm($this);
            if(count($found_uri) > count($base_uri)) {
                $subpath = array_slice($found_uri, count($base_uri), 1);
                $subpath = $subpath[0];
                $array_key = $array_key . $subpath;
            }
            $filename = $files[$key]->filename;
            //@todo the origin File class is just too heavy on the __construct
            //  making it hard to use here
            $params = array();
            $module = 'behat_github';
            //, $this->repo_name, $filename, 'file', $test_path, $params
            $file = new GithubDownloadedFile();
            $params = array(
                'module' => $this->module,
                'absolute_path' => $this->absolute_path,
                'repo_name' => $this->repo_name,
                'group_id' => $this->gid,
                'user_id' => $this->uid,
                'filename' =>  $this->gid . '/' . $this->repo_name . '/' . $this->subpath . '/' . $filename,
                'full_path_with_file_name' => $files[$key]->uri,
                'relative_path' => url($path = file_create_url("$this->relative_path/$filename")),
                'subpath' => $this->subpath,
            );
            $file_data[$array_key] = $file->get_file_info($params);
        }
        return $file_data;
    }
} 