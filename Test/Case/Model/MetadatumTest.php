<?php

/**
 * @package        metadata
 * @subpackage     metadata.test.case.model.behavior
 * @license        Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright      Copyright (c) 2009-2012 Joshua M. McNeese, HouseParty Inc.
 */

/**
 * Metadatum Model Test Case
 *
 * @see         Metadatum
 * @property    Metadatum   Metadatum
 */
class MetadatumTest extends CakeTestCase {

	/**
	 * Fixtures
	 *
	 * @var array
	 */
	public $fixtures = array(
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
	}

	/**
	 * Test Validation
	 *
	 * @return void
	 */
	public function testValidation() {

		$data = array(
			'name' => null
		);
		$this->Metadatum->create();
		$this->assertFalse($this->Metadatum->save($data));
		$this->assertSameSize($data, $this->Metadatum->invalidFields());

	}

	/**
	 * Test basic getting and setting of metadata.
	 *
	 * @return  void
	 */
	public function testPlainGetAndSet() {

		$data = array(
			'shoop',
			'shoop',
			'doo',
			'waaaa'
		);
		$this->assertNull($this->Metadatum->getKey('settings.foo'));
		$this->assertTrue($this->Metadatum->setKey('settings.foo', 'bar'));
		$this->assertEqual('bar', $this->Metadatum->getKey('settings.foo'));
		$this->assertTrue($this->Metadatum->setKey(array(
			'settings' => array(
				'baz' => 'bat'
			)
		)));
		$this->assertEqual('bat', $this->Metadatum->getKey('settings.baz'));
		$this->assertTrue($this->Metadatum->setKey('settings.diddy', $data));
		$this->assertEqual($data, $this->Metadatum->getKey('settings.diddy'));
		$this->assertTrue($this->Metadatum->setKey('settings', null));
		$this->assertNull($this->Metadatum->getKey('settings.diddy'));
	}

	/**
	 * Test replacement of existent metadata
	 *
	 * @return  void
	 */
	public function testReplace() {

		$key1 = 'settings.diddy';
		$key2 = 'answer';
		$data1 = array(
			'shoop',
			'shoop',
			'doo',
			'waaaa'
		);
		$data2 = 42;
		$data3 = 'orange';
		$this->Metadatum->setKey($key1, $data1);
		$this->assertTrue($this->Metadatum->setKey($key1, $data2));
		$this->assertEqual($data2, $this->Metadatum->getKey($key1));
		$this->assertNull($this->Metadatum->getKey($key1 . '.0'));
		$this->Metadatum->setKey($key2, $data2);
		$this->assertTrue($this->Metadatum->setKey($key2, $data3));
		$this->assertEqual($data3, $this->Metadatum->getKey($key2));
	}

	/**
	 * Test unsetting of existent metadata
	 *
	 * @return  void
	 */
	public function testUnset() {

		$this->Metadatum->setKey('test.unset', 1);
		$this->assertTrue($this->Metadatum->setKey('test.unset', null));
		$this->assertNull($this->Metadatum->getKey('test.unset'));
	}

	/**
	 * Test getting and setting of metadata in relation to a model/scoped.
	 *
	 * @return  void
	 */
	public function testModelGetAndSet() {

		$data1 = array(
			'Mario',
			'Luigi',
			'Princess Toadstool',
			'Toad'
		);
		$data2 = array(
			'model' => 'Character',
			'foreign_id' => 1,
			'name' => 'settings.friends'
		);
		$data3 = array(
			'Bowser',
			'Koopa',
			'Goomba',
			'Hammer Bros.'
		);
		$this->assertTrue($this->Metadatum->setKey($data1, $data2));
		$this->assertEqual($data1, $this->Metadatum->getKey(array(
			'model' => 'Character',
			'foreign_id' => 1,
			'name' => 'settings.friends'
		)));
		$this->assertTrue($this->Metadatum->setKey(array(
			'settings' => array(
				'enemies' => $data3
			)
		), array(
			'model' => 'Character',
			'foreign_id' => 1
		)));
		$this->assertEqual($data3, $this->Metadatum->getKey(array(
			'model' => 'Character',
			'foreign_id' => 1,
			'name' => 'settings.enemies'
		)));
		$this->assertTrue($this->Metadatum->setKey('settings.friends', $data1, $data2));
		$this->Metadatum->setScope();
		$this->assertEqual(11, $this->Metadatum->find('count'));
	}

	/**
	 * Test field cleanup in beforeSave()
	 *
	 * @return  void
	 */
	public function testBeforeSave() {

		$this->assertTrue($this->Metadatum->setKey(array(
			'app' => array(
				'debug' => 1
			)
		), array(
			'model' => '',
			'foreign_id' => ''
		)));
		$this->assertEqual(1, $this->Metadatum->getKey('app.debug'));
	}

	/**
	 * Test setting dupe key/value
	 *
	 * @return  void
	 */
	public function testDupeSet() {

		$key = 'app.debug';
		$value = 1;
		$this->assertTrue($this->Metadatum->setKey($key, $value));
		$this->assertTrue($this->Metadatum->setKey($key, $value));
		$this->assertEqual($value, $this->Metadatum->getKey($key));
	}

}