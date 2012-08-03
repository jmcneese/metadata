<?php

/**
 * @package        metadata
 * @subpackage     metadata.test.case.model.behavior
 * @license        Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright      Copyright (c) 2009-2012 Joshua M. McNeese, HouseParty Inc.
 */

/**
 * MetaThing Model
 *
 * @uses CakeTestModel
 */
class MetaThing extends CakeTestModel {

	/**
	 * Behaviors
	 *
	 * @var array
	 */
	public $actsAs = array(
		'Metadata.Metadata' => array(
			'validate' => array(
				'garbage' => array(
					array(
						'rule' => 'isGarbage',
						'message' => 'Must be garbage'
					),
					array(
						'rule' => array(
							'isGarbageLength',
							7
						),
						'message' => 'Must be garbage length 7'
					)
				),
				'empty' => array(
					'rule' => 'isGarbage',
					'message' => 'Must be garbage',
					'allowEmpty' => true
				),
				'postal' => array(
					'rule' => 'postal',
					'message' => 'Must be valid postal code',
				),
				'dimensions' => array(
					'inches' => array(
						'height' => array(
							array(
								'rule' => 'numeric',
								'message' => 'Must be numeric'
							),
							array(
								'rule' => array(
									'decimal',
									2
								),
								'message' => 'Must be decimal'
							)
						),
						'width' => array(
							'rule' => 'numeric',
							'message' => 'Must be numeric'
						),
						'depth' => array(
							'rule' => 'numeric',
							'message' => 'Must be numeric'
						),
						'girth' => array(
							'rule' => 'numeric',
							'message' => 'Must be numeric',
							'last' => true
						)
					)
				)
			)
		)

	);

	/**
	 * isGarbage method
	 *
	 * @param   string $field
	 * @return  boolean
	 */
	public function isGarbage($field = null) {
		return $field == 'garbage';
	}

	/**
	 * isGarbageLength method
	 *
	 * @param   string  $field
	 * @param   integer $length
	 * @return  boolean
	 */
	public function isGarbageLength($field = null, $length = null) {
		if (empty($field) && !empty($length)) {
			return strlen($field) == intval($length) && $field == 'garbage';
		}
		return false;
	}

}

/**
 * MetadataBehaviorTest
 *
 * @see         MetadataBehavior
 * @uses        CakeTestCase
 * @property    Metadatum   Metadatum
 * @property    MetaThing   MetaThing
 */
class MetadataBehaviorTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
		'plugin.metadata.meta_thing',
		'plugin.metadata.metadatum'
	);

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->Metadatum = ClassRegistry::init('Metadata.Metadatum');
		$this->MetaThing = ClassRegistry::init('Metadata.MetaThing');
	}

	/**
	 * Test Validation
	 *
	 * @return void
	 */
	public function testValidation() {
		$data1 = array(
			'empty' => '',
			'garbage' => 'trash',
			'postal' => 'abcd',
			'dimensions' => array(
				'inches' => array(
					'height' => 42.5,
					'width' => 11.75,
					'depth' => 3.25,
					'girth' => 'a'
				)
			)
		);
		$data2 = array(
			'dimensions' => array(
				'inches' => array(
					'height' => 42.23
				)
			)
		);
		$this->MetaThing->id = 1;
		$result1 = $this->MetaThing->invalidMeta($data1);
		$this->assertCount(5, Set::flatten($result1));
		$this->assertFalse($this->MetaThing->setMeta($data1));
		$this->assertTrue($this->MetaThing->setMeta($data2));
		$this->assertFalse($this->MetaThing->setMeta('dimensions.inches.height', 'forty-two'));
		$this->assertCount(1, $this->MetaThing->validationErrorsMeta());

	}

	/**
	 * Test afterSave callback
	 *
	 * @return void
	 */
	public function testAfterSave() {
		$data1 = array(
			'height' => 42.5,
			'width' => 11.75,
			'depth' => 3.25,
			'girth' => 13
		);
		$this->MetaThing->create();
		$result1 = $this->MetaThing->save(array(
			'MetaThing' => array(
				'name' => 'Flippedygibbet',
				'desc' => 'A Flippedygibbet is a type of thing',
				'Metadatum' => $data1
			)
		));
		$this->assertNotEmpty($result1);
		$this->assertEqual($data1, $this->MetaThing->getMeta());
		$this->MetaThing->create();
		$this->assertNotEmpty($this->MetaThing->save(array(
			'MetaThing' => array(
				'name' => 'Glomber',
				'desc' => 'A Glomber is a type of thing'
			)
		)));
		$this->assertNull($this->MetaThing->getMeta());
	}

	/**
	 * Test basic getting and setting of metadata via behavior.
	 *
	 * @return void
	 */
	public function testGetAndSet() {
		$key1 = 'dimensions.inches';
		$data1 = array(
			'height' => 42.52,
			'width' => 11.75,
			'depth' => 3.25,
			'girth' => 13
		);

		$data2 = array(
			'height' => 41.55,
			'width' => 10,
			'depth' => 4,
			'girth' => 17
		);
		$this->MetaThing->create();
		// should be false, as foreign_id isn't specified
		$this->assertFalse($this->MetaThing->getMeta($key1));
		$this->assertFalse($this->MetaThing->setMeta($key1));
		$thing1 = $this->MetaThing->read(null, 1);
		$this->assertNull($this->MetaThing->getMeta($key1));
		$this->assertTrue($this->MetaThing->setMeta($key1, $data1));
		$result4 = $this->MetaThing->getMeta($key1);
		$this->assertEqual($result4, $data1);
		$this->assertEqual($result4, $this->MetaThing->getMeta(array(
			'foreign_id' => $thing1['MetaThing']['id'],
			'name' => $key1
		)));
		$this->assertTrue($this->MetaThing->setMeta($key1, $data2));
		$this->assertEqual($data2, $this->MetaThing->getMeta($key1));
		$expect = array(
			'dimensions' => array(
				'inches' => $data2
			)
		);
		$this->assertEqual($expect, $this->MetaThing->getMeta());
		$this->assertTrue($this->MetaThing->setMeta($expect));
	}

	/**
	 * Test replacement of existant of metadata via behavior.
	 *
	 * @return void
	 */
	public function testReplace() {
		$key1 = 'settings.diddy';
		$data1 = array(
			'shoop',
			'shoop',
			'doo',
			'waaaa'
		);
		$data2 = 42;
		$this->MetaThing->id = 1;
		$this->MetaThing->setMeta($key1, $data1);
		$this->assertTrue($this->MetaThing->setMeta($key1, $data2));
		$this->assertEqual($data2, $this->MetaThing->getMeta($key1));
		$this->assertNull($this->MetaThing->getMeta($key1 . '.0'));
	}

	/**
	 * Test the scoping of meta trees
	 *
	 * @return void
	 */
	public function testScope() {
		$data1 = array(
			'foo' => 'bar',
			'bar' => 'baz',
			'baz' => 'bat'
		);
		$this->MetaThing->id = 1;
		$this->MetaThing->setMeta('deep.nested.meta', $data1);
		$result1 = $this->Metadatum->find('first', array(
			'fields' => array(
				'lft',
				'rght'
			),
			'conditions' => array(
				'model' => 'MetaThing',
				'foreign_id' => 1
			),
			'order' => 'lft ASC'
		));
		$this->MetaThing->id = 2;
		$this->MetaThing->setMeta('deep.nested.meta', array_flip($data1));
		$result2 = $this->Metadatum->find('first', array(
			'fields' => array(
				'lft',
				'rght'
			),
			'conditions' => array(
				'model' => 'MetaThing',
				'foreign_id' => 2
			),
			'order' => 'lft ASC'
		));
		$this->assertEqual($result1, $result2);
	}

	/**
	 * Test verification and recover of scoped trees
	 *
	 * @return void
	 */
	public function testVerifyAndRecover() {
		$this->MetaThing->id = 1;
		$this->MetaThing->setMeta('deep.nested.meta', array(
			'foo' => 'bar',
			'bar' => 'baz',
			'baz' => 'bat'
		));
		$this->assertTrue($this->MetaThing->verifyMeta());
		$result1 = $this->Metadatum->find('first', array(
			'fields' => array(
				'id',
				'lft',
				'rght'
			),
			'conditions' => array(
				'model' => 'MetaThing',
				'foreign_id' => 1
			),
			'order' => 'lft ASC'
		));
		$this->Metadatum->id = $result1['Metadatum']['id'];
		$this->Metadatum->save(array(
			'rght' => $result1['Metadatum']['rght'] + 2
		), false, false);
		$verify = $this->MetaThing->verifyMeta();
		$this->assertTrue($verify !== true);
		$this->assertTrue($this->MetaThing->recoverMeta());
		$this->assertTrue($this->MetaThing->verifyMeta());
	}

}