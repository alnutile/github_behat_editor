<?php

/**
 * @file the services resrources
 *
 */


/**
 * Implementation of hook_services_resources().
 */
function github_behat_editor_services_resources() {
    $path = drupal_get_path('module', 'github_behat_editor');
    $resources = array(
        'github_behat_editor_actions' => array(
            'operations' => array(
                'retrieve' => array(
                    'help' => t('Return the files for the given repos'),
                    'callback' => '_github_behat_editor_coming_soon',
                    'access callback' => '_github_behat_editor_acl',
                    'access arguments' => array('behat view test'),
                    'access arguments append' => TRUE,
                    'args' => array(
                        array(
                            'name' => 'module',
                            'type' => 'string',
                            'description' => t('Name of Module'),
                            'source' => array('path' => '0'),
                            'optional' => FALSE,
                        ),
                        array(
                            'name' => 'filename',
                            'type' => 'string',
                            'description' => t('Name of file.'),
                            'source' => array('path' => '1'),
                            'optional' => FALSE,
                        ),
                        array(
                            'name' => 'path',
                            'type' => 'string',
                            'description' => t('Path of file.'),
                            'source' => array('path' => '3'),
                            'optional' => FALSE,
                        ),
                    ),
                ),
                'create' => array(
                    'help' => t('Method to create a test file.'),
                    'callback' => '_github_behat_editor_coming_soon',
                    'access callback' => '_github_behat_editor_acl',
                    'access arguments' => array('behat add test'),
                    'access arguments append' => FALSE,
                    'args' => array(
                        array(
                            'name' => 'data',
                            'type' => 'struct',
                            'description' => t('Params'),
                            'source' => 'data',
                            'optional' => FALSE,
                        ),
                    ),
                ),
                'update' => array(
                    'help' => t('Method to update a test file'),
                    'callback' => '_github_behat_editor_coming_soon',
                    'access callback' => '_github_behat_editor_acl',
                    'access arguments' => array('behat edit test'),
                    'access arguments append' => FALSE,
                    'args' => array(
                        array(
                            'name' => 'module',
                            'type' => 'string',
                            'description' => t('Name of Module'),
                            'source' => array('path' => '0'),
                            'optional' => FALSE,
                        ),
                        array(
                            'name' => 'filename',
                            'type' => 'string',
                            'description' => t('Name of file.'),
                            'source' => array('path' => '1'),
                            'optional' => FALSE,
                        ),
                        array(
                            'name' => 'path',
                            'type' => 'string',
                            'description' => t('Path of file.'),
                            'source' => array('path' => '3'),
                            'optional' => FALSE,
                        ),
                        array(
                            'name' => 'data',
                            'type' => 'struct',
                            'description' => t('Scenario'),
                            'source' => 'data',
                            'optional' => FALSE,
                        ),
                    ),
                ),
                'delete' => array(
                    'help' => t('Method to delete a test file'),
                    'callback' => '_github_behat_editor_coming_soon',
                    'access callback' => '_github_behat_editor_acl',
                    'access arguments' => array('behat delete test'),
                    'access arguments append' => TRUE,
                    'args' => array(
                        array(
                            'name' => 'module',
                            'type' => 'string',
                            'description' => t('Name of Module'),
                            'source' => array('path' => '0'),
                            'optional' => FALSE,
                        ),
                        array(
                            'name' => 'filename',
                            'type' => 'string',
                            'description' => t('Name of file.'),
                            'source' => array('path' => '1'),
                            'optional' => FALSE,
                        ),
                        array(
                            'name' => 'path',
                            'type' => 'string',
                            'description' => t('Path of file.'),
                            'source' => array('path' => '3'),
                            'optional' => FALSE,
                        ),
                    ),
                ),
                'index' => array(
                    'help' => t('Show all repos'),
                    'callback' => '_github_behat_editor_coming_soon',
                    'access callback' => '_github_behat_editor_acl',
                    'access arguments' => array('behat view test'),
                    'access arguments append' => TRUE,
                ),
            ),
        ),
    );
    return $resources;
}

function _github_behat_editor_coming_soon($module, $filename, $path, $query) {
    return "Coming soon";
}

function _github_behat_editor_acl($arg){
    if(user_access($arg)) {
        return TRUE;
    } else {
        return FALSE;
    }
}
