<?php

/**
 * @file manage the settings for the repo to user/group
 */


/**
 * @param $type
 *   user or group
 * @param $action
 *   add update delete
 */
function github_behat_editor_repos_manage($type, $action) {
    $output = "HW";
    dpm($type);
    dpm($action);

    return $output;
}