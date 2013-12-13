<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 12/12/13
 * Time: 10:41 PM
 */

namespace Drupal\GithubBehatEditor;

use Drupal\BehatEditor;


class GithubDownloadedFile extends BehatEditor\File {
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

    public function build_paths($params = array()){
        /**
         *     public $relative_path = '';
         *     public $full_path_with_file = '';
         *     public $full_path = '';
         */
            $gid = $params['gid'];
            $uid = $params['uid'];
            $id = $params['id'];
            $this->type = $params['type'];
            $this->repo_name = $params['repo_name'];
            $this->subpath = $params['subpath'];
            $this->filename = $params['filename'];

            $files_folder =  file_build_uri("/" . GithubDownloadedFile::ROOT . "/" . $this->type . "/{$id}/". $this->repo_name ."/" . $this->subpath);
            //Setup some info about the file
            $this->relative_path = url($path = file_create_url("$files_folder/$this->filename"));
            $this->full_path = drupal_realpath($files_folder);
            $this->full_path_with_file = $this->full_path . '/' . $this->filename;
    }

    /**
     * Save HTML and make File
     *
     * @return array
     */
    public function save_html_to_file() {
        //@todo throw expection if this is a fail
        $this->scenario_array = self::_parse_questions();
        $this->feature =  self::_create_file();
        $output = self::_figure_out_where_to_save_file();
        return $output;
    }

    /**
     * Make HTML array from a file
     *
     * @param $file_text
     * @return array
     */
    public function output_file_text_to_html_array($file_text) {
        $this->scenario = self::_turn_file_to_array($file_text);
        $this->scenario_array = self::_parse_questions();
        return $this->scenario_array;
    }

    /**
     * Build out the file_object used in most functions.
     *
     * @return array
     */
    public function get_file_info($params = array(0)) {
        if(file_exists($this->full_path_with_file) == FALSE) {
            $message = t('The file does not exist !file', array('!file' => $this->full_path_with_file));
            throw new \RuntimeException($message);
        } else {
            $file_text = self::read_file($this->full_path_with_file);
            $file_data = array(
                'module' => $this->module,
                'filename' => $this->filename,
                'absolute_path' => $this->full_path,
                'absolute_path_with_file' => $this->full_path_with_file,
                'scenario' => $file_text,
                'filename_no_ext' => substr($this->filename, 0, -8),
                'relative_path' => $this->relative_path,
                'subpath' => $this->subpath,
                'tags_array' => self::_tags_array($file_text, $this->module)
            );
            $file_data = array_merge( self::fileObjecBuilder(), $file_data);
            return $file_data;
        }
    }

    /**
     * Read file
     *
     * @param $full_path_with_file
     * @return string
     */
    public function read_file($full_path_with_file) {
        if(filesize($full_path_with_file) > 0) {
            $file_open = fopen($full_path_with_file, "r");
            $file_read = fread($file_open, filesize($full_path_with_file));
            return $file_read;
        }
    }

    /**
     * Read file
     *
     * @param $full_path_with_file
     * @return string
     */
    public function delete_file() {
        $file = self::get_file_info();
        $response = file_unmanaged_delete($file['absolute_path_with_file']);
        if($response == FALSE) {
            watchdog('behat_editor', "File could not be deleted...", $variables = array(), $severity = WATCHDOG_ERROR, $link = NULL);
            $output = array('message' => "Error file could not be deleted", 'file' => $response, 'error' => '1');
        } else {
            $gherkin_linkable_path = '';
            $url = '';
            $file_url = '';
            $date = format_date(time(), $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL);
            watchdog('behat_editor', "%date File deleted %name", $variables = array('%date' => $date, '%name' => $this->filename), $severity = WATCHDOG_NOTICE, $link = $file_url);
            $output =  array('message' => t('@date: <br> File deleted !name to download ', array('@date' => $date, '!name' => $file_url)), 'file' => $gherkin_linkable_path, 'error' => '0');
        }
        return $output;
    }


    /**
     * Quick Helper to figure out save path
     * based on permissions.
     *
     * @return array
     */
    protected function _figure_out_where_to_save_file(){
        if (user_access('behat add test') && $this->module != variable_get('behat_editor_default_folder', BEHAT_EDITOR_DEFAULT_FOLDER)) {
            /* Derived from features.admin.inc module */
            $output = self::_save_file_to_module_folder();
            return $output;
        } else {
            $output = self::_save_file_to_temp_folder();
            return $output;
        }
    }


    /**
     * Save to module folder
     *
     * @return array
     */
    protected function _save_file_to_module_folder() {
        $full_path = self::_save_path();
        $response = file_put_contents("{$full_path}/{$this->filename}", $this->feature);
        if($response == FALSE) {
            watchdog('behat_editor', "File could not be made...", $variables = array(), $severity = WATCHDOG_ERROR, $link = NULL);
            $output = array('message' => "Error file could not be saved", 'file' => $response, 'error' => '1');
        } else {
            $gherkin_linkable_path = self::_linkable_path($this->module, $this->filename);
            $url = url($gherkin_linkable_path, $options = array('absolute' => TRUE));
            $file_url = l('click here', $url, array('attributes' => array('target' => '_blank', 'id' => array('test-file'))));
            $date = format_date(time(), $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL);
            watchdog('behat_editor', "%date File made %name", $variables = array('%date' => $date, '%name' => $this->filename), $severity = WATCHDOG_NOTICE, $link = $file_url);
            $output =  array('message' => t('@date: <br> File created !name to download ', array('@date' => $date, '!name' => $file_url)), 'file' => $gherkin_linkable_path, 'error' => '0');
        }
        return $output;
    }

    /**
     * Make a linkable path to the file.
     *
     * @return string
     */
    protected function _linkable_path() {
        $module_path = drupal_get_path('module', $this->module);
        return $module_path . '/' . variable_get('behat_editor_folder', BEHAT_EDITOR_FOLDER) . '/' . $this->filename;
    }

    /**
     * Make a save path for the file
     *
     * @return string
     */
    protected function _save_path() {
        $module_path = drupal_get_path('module', $this->module);
        return  DRUPAL_ROOT . '/' . $module_path . '/' . variable_get('behat_editor_folder', BEHAT_EDITOR_FOLDER);
    }

    protected function _save_file_to_temp_folder() {
        $folder = variable_get('behat_editor_default_folder', BEHAT_EDITOR_DEFAULT_FOLDER);
        $path = file_build_uri("/{$folder}/");
        $response = file_unmanaged_save_data($this->feature, $path . '/' . $this->filename, $replace = FILE_EXISTS_REPLACE);
        if($response == FALSE) {
            $message = t('The file could not be saved !file', array('!file' => $path . '/' . $this->filename));
            throw new \RuntimeException($message);
        } else {
            $file_uri = $response;
            $file_url = l('click here', file_create_url($response), array('attributes' => array('target' => '_blank', 'id' => array('test-file'))));
            $date = format_date(time(), $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL);
            watchdog('behat_editor', "%date File made %name", $variables = array('%date' => $date, '%name' => $response), $severity = WATCHDOG_NOTICE, $link = $file_url);
            $output = array('message' => t('@date: <br> File created !name to download ', array('@date' => $date, '!name' => $file_url)), 'file' => $file_uri, 'error' => '0');
        }
        return $output;
    }


    /**
     * Format the file creation from the array
     *
     * @return string
     */
    protected function _create_file(){
        $file = '';
        foreach($this->scenario_array as $key) {
            $new_line = self::_new_line($key['new_line']);
            $new_line_above = self::_new_line($key['new_line_above']);
            $spaces = self::_spaces($key['spaces']);
            $file = $file . "{$new_line_above}" . "{$spaces}" . $key['string'] . "{$new_line}\r\n";
        }
        return $file;
    }

    /**
     * New line parse
     *
     * @param $new_line
     * @return string
     */
    protected function _new_line($new_line) {
        if($new_line == 1) {
            return "\r\n";
        } else {
            return "";
        }
    }

    /**
     * Spaces needed to output the HTML or file to look
     * right.
     *
     * @param $spaces
     * @return string
     */
    protected function _spaces($spaces) {
        $spaces_return = '';
        for($i = 0; $i <= $spaces; $i++) {
            $spaces_return = $spaces_return . " ";
        }
        return $spaces_return;
    }

    public static function fileObjecBuilder() {
        composer_manager_register_autoloader();
        $path = drupal_get_path('module', 'behat_editor');
        $file_object['absolute_path_with_file'] = '';
        $file_object['absolute_path'] = '';
        $file_object['relative_path'] = '';
        $file_object['filename'] = '';
        $file_object['subpath'] = FALSE;
        $file_object['filename_no_ext'] = '';
        $file_object['tags_array'] = '';
        $file_object['module'] = '';
        return $file_object;
    }
}