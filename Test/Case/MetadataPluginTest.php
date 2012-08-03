<?php

/**
 * @package		metadata
 * @subpackage	metadata.test.case
 * @author		Joshua McNeese <jmcneese@gmail.com>
 * @license     Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright   Copyright (c) 2009-2012 Joshua M. McNeese, HouseParty Inc.
 */

/**
 * MetadataPluginTest
 *
 * @uses CakeTestSuite
 */
class MetadataPluginTest extends CakeTestSuite {

    public static function suite() {
        $suite = new CakeTestSuite('All MetadataPlugin tests');
        $suite->addTestFile(dirname(__FILE__) . DS . 'Model' . DS . 'MetadatumTest.php');
        $suite->addTestFile(dirname(__FILE__) . DS . 'Model' . DS . 'Behavior' . DS . 'MetadataBehaviorTest.php');
        return $suite;
    }

}