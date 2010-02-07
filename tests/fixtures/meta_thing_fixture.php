<?php

/**
 * Thing Fixture
 *
 * @package     metadata
 * @subpackage  metadata.tests.fixtures
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, HouseParty Inc.
 */
class MetaThingFixture extends CakeTestFixture {

    /**
     * @var     string
     */
    public $name    = 'MetaThing';

    /**
     * @var     array
     */
    public $fields  = array(
        'id'        => array(
            'type'  => 'integer',
            'length'=> 11,
            'key'   => 'primary'
        ),
        'name'      => array(
            'type'  => 'string',
            'length'=> 32,
            'null'  => false
        ),
        'desc'      => 'text'
    );

    /**
     * @var     array
     */
    public $records = array(
        array(
            'id'        => 1,
            'name'      => 'Gadget',
            'desc'      => 'A Gadget is a type of thing'
        ),
        array(
            'id'        => 2,
            'name'      => 'Widget',
            'desc'      => 'A Widget is a type of thing'
        ),
        array(
            'id'        => 3,
            'name'      => 'Doodad',
            'desc'      => 'A Doodad is a type of thing'
        )
    );

}

?>