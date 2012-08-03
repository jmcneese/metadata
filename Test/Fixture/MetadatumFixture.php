<?php

/**
 * MetadatumFixture
 *
 * @package     metadata
 * @subpackage  metadata.test.fixture
 * @see         Metadatum
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009-2012 Joshua M. McNeese, HouseParty Inc.
 */
class MetadatumFixture extends CakeTestFixture {

	/**
	 * Fields
	 *
     * @var array
     */
	public $fields = array(
		'id' => array(
			'type'		=> 'integer',
			'null'		=> false,
			'default'	=> NULL,
			'key'		=> 'primary'
		),
		'parent_id' => array(
			'type'		=> 'integer',
			'null'		=> true,
			'default'	=> NULL,
			'key'		=> 'index'
		),
		'model'	=> array(
			'type'		=> 'string',
			'null'		=> true,
			'default'	=> NULL,
			'length'	=> 32,
			'key'		=> 'index'
		),
		'foreign_id' => array(
			'type'		=> 'integer',
			'null'		=> true,
			'default'	=> NULL
			),
		'name' => array(
			'type'		=> 'string',
			'null'		=> false,
			'default'	=> NULL,
			'length'	=> 64
			),
		'value'	=> array(
			'type'		=> 'text',
			'null'		=> true,
			'default'	=> NULL
			),
		'lft' => array(
			'type'		=> 'integer',
			'null'		=> true,
			'default'	=> NULL
			),
		'rght' => array(
			'type'		=> 'integer',
			'null'		=> true,
			'default'	=> NULL
			),
		'created' => array(
			'type'		=> 'datetime',
			'null'		=> false,
			'default'	=> NULL
			),
		'modified' => array(
			'type'		=> 'datetime',
			'null'		=> false,
			'default'	=> NULL
			),
		'indexes' => array(
			'PRIMARY' => array(
				'column' => 'id',
				'unique' => 1
			),
			'rght_idx' => array(
				'unique' => 0,
				'column' => array(
					'model',
					'foreign_id',
					'rght',
					'lft'
				)
			),
			'lft_idx' => array(
				'unique' => 0,
				'column' => array(
					'model',
					'foreign_id',
					'lft',
					'rght'
				)
			),
			'parent_idx' => array(
				'unique' => 0,
				'column' => array(
					'model',
					'foreign_id',
					'parent_id'
				)
			)
		)
	);

}