<?php
/**
 * @file
 * To setup custom Step Definitions
 * user the api DrupalExtensions offers
 *
 * @link
 * http://dspeak.com/drupalextension/subcontexts.html
 */

use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Behat\Behat\Context\BehatContext;

class GithubBehatSubContext extends BehatContext implements DrupalSubContextInterface {

    public function __construct(){

    }

    /**
     * Setup the needed SubContext alias
     */
    public static function getAlias() {
        return 'github_behat';
    }

    /**
     * @Given /^I reset the test$/
     */
    public static function resetTheTest(){
        //@todo need to work this
        return TRUE;
    }

//    /** @AfterSuite */
//    public static function teardown($event)
//    {
//        var_dump("This is triggered");
//        var_dump($event->drush);
//    }
}