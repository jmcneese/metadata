<?php

/**
 * Metadatum Model Test Case
 *
 * @package     metadata
 * @subpackage  metadata.tests.cases.models
 * @see         Metadatum
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, HouseParty Inc.
 */
class MetadatumTestCase extends CakeTestCase {

    /**
     * @var     array
     */
    public $fixtures = array(
        'plugin.metadata.metadatum'
    );

    /**
     * @return  void
     */
    public function start() {

        parent::start();

        $this->Metadatum =& ClassRegistry::init('Metadata.Metadatum');

    }

	/**
     * Test Instance Creation
     *
     * @return  void
     */
    public function testInstanceSetup() {

        $this->assertIsA($this->Metadatum, 'Model');

    }

	/**
	 * Test Validation
	 *
	 * @return  void
	 */
	public function testValidation() {

		$this->Metadatum->create();

		$data1 = array(
			'name'	=> null
		);
		$result1 = $this->Metadatum->save($data1);
		$this->assertFalse($result1);
		$this->assertEqual(count($this->Metadatum->invalidFields()), count($data1));

	}

    /**
     * Test basic getting and setting of metadata.
     *
     * @return  void
     */
    public function testPlainGetAndSet() {
		
        $result1 = $this->Metadatum->getKey('settings.foo');
        $this->assertNull($result1);

        $result2 = $this->Metadatum->setKey('settings.foo', 'bar');
        $this->assertTrue($result2);

        $result3 = $this->Metadatum->getKey('settings.foo');
        $this->assertEqual($result3, 'bar');
        
        $result4 = $this->Metadatum->setKey(array(
			'settings' => array(
				'baz'	=> 'bat'
			)
		));
        $this->assertTrue($result4);

        $result5 = $this->Metadatum->getKey('settings.baz');
        $this->assertEqual($result5, 'bat');

        $data6  = array(
            'shoop',
            'shoop',
            'do',
            'waaaa'
        );

        $result6 = $this->Metadatum->setKey('settings.diddy', $data6);
        $this->assertTrue($result6);

        $result7 = $this->Metadatum->getKey('settings.diddy');
        $this->assertEqual($result7, $data6);


		$result8 = $this->Metadatum->setKey('settings', null);
        $this->assertTrue($result8);

    }

    /**
     * Test replacement of existent metadata
     *
     * @return  void
     */
    public function testReplace() {
		
        $key1  = 'settings.diddy';
		$key2  = 'answer';
        $data1 = array(
            'shoop',
            'shoop',
            'do',
            'waaaa'
        );

        $data2 = 42;
		$data3 = 'orange';

        $this->Metadatum->setKey($key1, $data1);

        $result1 = $this->Metadatum->setKey($key1, $data2);
        $this->assertTrue($result1);

        $result2 = $this->Metadatum->getKey($key1);
        $this->assertEqual($result2, $data2);

        $result3 = $this->Metadatum->getKey($key1.'.0');
        $this->assertNull($result3);

		$this->Metadatum->setKey($key2, $data2);

		$result4 = $this->Metadatum->setKey($key2, $data3);
		$this->assertTrue($result4);

        $result5 = $this->Metadatum->getKey($key2);
        $this->assertEqual($result5, $data3);

    }

    /**
     * Test unsetting of existent metadata
     *
     * @return  void
     */
    public function testUnset() {

        $key1  =
        $data1 = 'test.unset';

        $this->Metadatum->setKey($key1, $data1);

        $result1 = $this->Metadatum->setKey($key1, null);
        $this->assertTrue($result1);

        $result2 = $this->Metadatum->getKey($key1);
        $this->assertNull($result2);

        $this->Metadatum->setKey($key1, $data1);

        $result3 = $this->Metadatum->setKey($key1);
        $this->assertTrue($result1);

        $result4 = $this->Metadatum->getKey($key1);
        $this->assertNull($result2);

    }

    /**
     * Test getting and setting of metadata in relation to a model/scoped.
     *
     * @return  void
     */
    public function testModelGetAndSet() {
		
        $data1  = array(
            'Mario',
            'Luigi',
            'Princess Toadstool',
            'Toad'
        );

		$data2 = array(
            'model'         => 'Character',
            'foreign_id'    => 1,
            'name'          => 'settings.friends'
        );

		$data3 = array(
			'Bowser',
			'Koopa',
			'Goomba',
			'Hammer Bros.'
		);

        $result1 = $this->Metadatum->setKey($data1, $data2);
        $this->assertTrue($result1);

        $result2 = $this->Metadatum->getKey(array(
            'model'         => 'Character',
            'foreign_id'    => 1,
            'name'          => 'settings.friends'
        ));
        $this->assertEqual($result2, $data1);

		$result3 = $this->Metadatum->setKey(array(
			'settings' => array(
				'enemies' => $data3
			)
		), array(
            'model'         => 'Character',
            'foreign_id'    => 1
        ));
        $this->assertTrue($result3);

        $result4 = $this->Metadatum->getKey(array(
            'model'         => 'Character',
            'foreign_id'    => 1,
            'name'          => 'settings.enemies'
        ));
        $this->assertEqual($result4, $data3);

		$result5 = $this->Metadatum->setKey('settings.friends', $data1, $data2);
        $this->assertTrue($result5);

		$this->Metadatum->setScope();
		$this->assertEqual($this->Metadatum->find('count'), 11);

    }

    /**
     * Test field cleanup in beforeSave()
     *
     * @return  void
     */
    public function testBeforeSave() {
		
        $result1= $this->Metadatum->setKey(array(
			'app' => array(
				'debug' => 1
			)
		), array(
            'model'         => '',
            'foreign_id'    => ''
        ));

        $this->assertTrue($result1);

        $result2 = $this->Metadatum->getKey('app.debug');
        $this->assertEqual($result2, 1);

    }

    /**
     * Test setting dupe key/value
     *
     * @return  void
     */
    public function testDupeSet() {
		
        $key    = 'app.debug';
        $value  = 1;
        $result1= $this->Metadatum->setKey($key, $value);
        $this->assertTrue($result1);

        $result2 = $this->Metadatum->setKey($key, $value);
        $this->assertTrue($result2);

        $result3 = $this->Metadatum->getKey($key);
        $this->assertEqual($result3, $value);

    }

}

?>