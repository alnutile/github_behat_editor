<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/12/13
 * Time: 10:41 PM
 */

namespace Drupal\GithubBehatEditor;

use Drupal\BehatEditor;


class GithubDownloadedFile extends BehatEditor\FileBuilder {
    public $module = '';
    public $filename = '';
    public $parse_type = '';
    public $scenario_array = array();
    public $type;
    public $scenario = '';
    public $feature = '';
    public $repo_name = '';
    public $subpath = '';
    public $relative_path = '';
    public $full_path_with_file = '';
    public $full_path = '';

    const ROOT = 'behat_github';

    /**
     * Move this into an abstract static class
     * so that the construct is not so complex.
     * and make an abstract class for both types of files
     * to extend eg save_to_temp and save_to_module
     * @param $request
     * @param $module string
     * @param $filename
     * @param $parse_type
     */
    public function __construct($params = array()) {

    }

    /**
     *
     */

    /**
     * @param $params
     *   module name = string
     *   service_path = array()
     *   filename = string
     * @return fileObject
     */
    public function buildObject($params){
        $this->module = $params['module'];
        $this->filename = $params['filename'];
        $path = 'behat_github';
        $file_object = $this->buildFileObjectGithub($params, $path);
        return $file_object;
    }

    public function buildFileObjectGithub(array $params, $path) {
        $service_path_full = $params['service_path'];
        $test_folder_and_test_file_name = array_slice($service_path_full, 4);
        $this->test_folder_and_file = implode('/', $test_folder_and_test_file_name);
        $test_folder = array_slice($test_folder_and_test_file_name, 0, -1);
        $test_folder_string = implode('/', $test_folder);
        $this->subpath = $params['subpath'];
        $this->root_folder = file_build_uri("/behat_github/" . $test_folder_string . "/");
        $this->full_path =  drupal_realpath($this->root_folder);
        $this->full_path_with_file =  $this->full_path . '/' . $this->filename;
        $this->relative_path = file_create_url($this->root_folder . '/' . $this->filename);
        $this->get_file_info();
        $file_object = $this->setFileObject();
        return $file_object;
    }

    /**
     * Replaces fileObjectBuilder
     */
    protected function setFileObject() {
        watchdog('test_line_83', print_r($this->root_folder, 1));
        $file_object = array();
        $file_object['absolute_path_with_file'] = $this->full_path_with_file;
        $file_object['absolute_path'] = $this->full_path;
        $file_object['relative_path'] = $this->relative_path;
        $file_object['filename'] = $this->filename;
        $file_object['subpath'] = FALSE;
        $file_object['scenario'] = $this->file_text;
        $file_object['filename_no_ext'] = substr($this->filename, 0, -8);
        $file_object['tags_array'] = $this->tags_array;
        $file_object['module'] = $this->module;
        return $file_object;
    }

}