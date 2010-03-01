<?php

App::import('Model', 'Metadata.MetadataAppModel');

/**
 * Override CakeTestModel to use AppModel as parent instead of Model
 *
 * @package     metadata
 * @subpackage  metadata.tests.cases.behaviors
 * @uses		MetadataAppModel
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, HouseParty Inc.
 */
class MyCakeTestModel extends MetadataAppModel {

	public $useDbConfig	 = 'test_suite';
	public $cacheSources = false;
	public $displayField = 'name';

}

/**
 * Generic MetaThing Model
 *
 * @package     metadata
 * @subpackage  metadata.tests.cases.behaviors
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, HouseParty Inc.
 */
class MetaThing extends MyCakeTestModel {

    public function isGarbage($field = null) {

        return $field == 'garbage';
        
    }

    public function isGarbageLength($field = null, $length = null) {

        if(empty($field) && !empty($length)) {

            return strlen($field) == intval($length) && $field == 'garbage';

        }

        return false;
        
    }

}

/**
 * MetadataBehavior Test Case
 *
 * @package     metadata
 * @subpackage  metadata.tests.cases.behaviors
 * @see         MetadataBehavior
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, HouseParty Inc.
 */
class MetadataTestCase extends CakeTestCase {

	/**
	 * @var     array
	 */
	public $fixtures = array(
		'plugin.metadata.meta_thing',
		'plugin.metadata.metadatum'
	);

	/**
	 * @return  void
	 */
	public function start() {

		parent::start();

		$this->Metadatum	=& ClassRegistry::init('Metadata.Metadatum');
		$this->MetaThing	=& ClassRegistry::init('Metadata.MetaThing');
		$this->MetaThing->Behaviors->attach('Metadata.Metadata', array(
			'validate' => array(
                'garbage'   => array(
                    array(
                        'rule'      => 'isGarbage',
                        'message'   => 'Must be garbage'
                    ),
                    array(
                        'rule'      => array('isGarbageLength', 7),
                        'message'   => 'Must be garbage length 7'
                    )
                ),
                'empty'	=> array(
					'rule'		=> 'isGarbage',
					'message'	=> 'Must be garbage',
                    'allowEmpty'=> true
				),
				'postal'	=> array(
					'rule'		=> 'postal',
					'message'	=> 'Must be valid postal code',
				),
				'dimensions'=> array(
					'inches' => array(
						'height'	=> array(
							array(
								'rule'		=> 'numeric',
								'message'	=> 'Must be numeric'
							),
							array(
								'rule'		=> array('decimal', 2),
								'message'	=> 'Must be decimal'
							)
						),
						'width'     => array(
							'rule'		=> 'numeric',
							'message'	=> 'Must be numeric'
						),
						'depth'     => array(
							'rule'		=> 'numeric',
							'message'	=> 'Must be numeric'
						),
						'girth'     => array(
							'rule'		=> 'numeric',
							'message'	=> 'Must be numeric',
							'last'		=> true
						)
					)
				)
			)
		));

	}

	/**
	 * Test Instance Creation
	 *
	 * @return  void
	 */
	public function testInstanceSetup() {

		$this->assertIsA($this->MetaThing, 'Model');
		$this->assertTrue($this->MetaThing->Behaviors->attached('Metadata'));

	}

	/**
	 * Test Validation
	 *
	 * @return  void
	 */
	public function testValidation() {

		$data1 = array(
            'empty'  => '',
            'garbage'=> 'trash',
			'postal' => 'abcd',
			'dimensions' => array(
				'inches' => array(
					'height'    => 42.5,
					'width'     => 11.75,
					'depth'     => 3.25,
					'girth'     => 'a'
				)
			)
		);

		$data2 = array(
			'dimensions' => array(
				'inches' => array(
					'height'    => 42.23
				)
			)
		);

		$this->MetaThing->id = 1;

		$result1 = $this->MetaThing->invalidMeta($data1);
		$this->assertTrue($result1);
		$this->assertEqual(count(Set::flatten($result1)), 5);

		$result2 = $this->MetaThing->setMeta($data1);
		$this->assertFalse($result2);

		$result3 = $this->MetaThing->setMeta($data2);
		$this->assertTrue($result3);

		$result4 = $this->MetaThing->setMeta('dimensions.inches.height', 42);
		$this->assertFalse($result4);

		$result5 = $this->MetaThing->validationErrorsMeta();
		$this->assertTrue($result5);
		$this->assertEqual(count(Set::flatten($result5)), 1);

	}

	/**
	 * Test afterSave callback
	 *
	 * @return  void
	 */
	public function testAfterSave() {

		$data1 = array(
			'height'    => 42.5,
			'width'     => 11.75,
			'depth'     => 3.25,
			'girth'     => 13
		);

		$this->MetaThing->create();

		$result1 = $this->MetaThing->save(array(
			'MetaThing'	=> array(
				'name'	=> 'Flippedygibbet',
				'desc'	=> 'A Flippedygibbet is a type of thing',
				'Metadatum' => $data1
			)
		));
		$this->assertTrue($result1);

		$result2 = $this->MetaThing->getMeta();
		$this->assertEqual($result2, $data1);

		$this->MetaThing->create();

		$result3 = $this->MetaThing->save(array(
			'MetaThing'	=> array(
				'name'	=> 'Glomber',
				'desc'	=> 'A Glomber is a type of thing'
			)
		));
		$this->assertTrue($result3);

		$result4 = $this->MetaThing->getMeta();
		$this->assertFalse($result4);

		$data2 = array(
			'postal' => 'abcd',
			'dimensions' => array(
				'inches' => array(
					'height'    => 42.5,
					'width'     => 11.75,
					'depth'     => 3.25,
					'girth'     => 'a'
				)
			)
		);

		$this->MetaThing->create();

		$result5 = $this->MetaThing->save(array(
			'MetaThing'	=> array(
				'name'	=> 'Glomber',
				'desc'	=> 'A Glomber is a type of thing',
				'Metadatum' => $data2
			)
		));
		$this->assertFalse($result5);

	}

	/**
	 * Test basic getting and setting of metadata via behavior.
	 *
	 * @return  void
	 */
	public function testGetAndSet() {

		$key1  = 'dimensions.inches';
		$data1 = array(
			'height'    => 42.52,
			'width'     => 11.75,
			'depth'     => 3.25,
			'girth'     => 13
		);

		$data2 = array(
			'height'    => 41.55,
			'width'     => 10,
			'depth'     => 4,
			'girth'     => 17
		);


		$this->MetaThing->create();

		// should be false, as foreign_id isn't specified
		$result0 = $this->MetaThing->getMeta($key1);
		$this->assertFalse($result0);

		$result1 = $this->MetaThing->setMeta($key1);
		$this->assertFalse($result1);

		$thing1  = $this->MetaThing->read(null, 1);
		$result2 = $this->MetaThing->getMeta($key1);
		$this->assertNull($result2);

		$result3 = $this->MetaThing->setMeta($key1, $data1);
		$this->assertTrue($result3);

		$result4 = $this->MetaThing->getMeta($key1);
		$this->assertEqual($result4, $data1);

		$result5 = $this->MetaThing->getMeta(array(
			'foreign_id' => $thing1['MetaThing']['id'],
			'name'       => $key1
		));
		$this->assertEqual($result5, $result4);

		$thing2  = $this->MetaThing->read(null, 2);

		$result6 = $this->MetaThing->getMeta($key1);
		$this->assertNull($result6);

		$result7 = $this->MetaThing->setMeta($key1, $data2);
		$this->assertTrue($result7);

		$result8 = $this->MetaThing->getMeta($key1);
		$this->assertEqual($result8, $data2);

		$result9 = $this->MetaThing->getMeta();

		$expect9 = array(
			'dimensions'    => array(
				'inches'    => $data2
			)
		);
		$this->assertEqual($result9, $expect9);

		$thing3  = $this->MetaThing->read(null, 3);

		$result10 = $this->MetaThing->getMeta();
		$this->assertNull($result10);

		$result11 = $this->MetaThing->setMeta($expect9);
		$this->assertTrue($result11);

	}

	/**
	 * Test replacement of existant of metadata via behavior.
	 *
	 * @return  void
	 */
	public function testReplace() {

		$key1  = 'settings.diddy';
		$data1 = array(
			'shoop',
			'shoop',
			'do',
			'waaaa'
		);

		$data2 = 42;

		$this->MetaThing->id = 1;
		$this->MetaThing->setMeta($key1, $data1);

		$result1 = $this->MetaThing->setMeta($key1, $data2);
		$this->assertTrue($result1);

		$result2 = $this->MetaThing->getMeta($key1);
		$this->assertEqual($result2, $data2);

		$result3 = $this->MetaThing->getMeta($key1.'.0');
		$this->assertNull($result3);

	}

	/**
	 * Test the scoping of meta trees
	 *
	 * @return  void
	 */
	public function testScope() {

		$data1 = array(
			'foo'	=> 'bar',
			'bar'	=> 'baz',
			'baz'	=> 'bat'
		);

		$this->MetaThing->id = 1;
		$this->MetaThing->setMeta('deep.nested.meta', $data1);

		$result1 = $this->Metadatum->find('first', array(
			'fields'	=> array('lft','rght'),
			'conditions'=> array(
				'model'		=> 'MetaThing',
				'foreign_id'=> 1
			),
			'order'		=> 'lft ASC'
		));

		$this->MetaThing->id = 2;
		$this->MetaThing->setMeta('deep.nested.meta', array_flip($data1));

		$result2 = $this->Metadatum->find('first', array(
			'fields'	=> array('lft','rght'),
			'conditions'=> array(
				'model'		=> 'MetaThing',
				'foreign_id'=> 2
			),
			'order'		=> 'lft ASC'
		));

		$this->assertEqual($result1, $result2);

	}

	/**
	 * Test verification and recover of scoped trees
	 *
	 * @return  void
	 */
	public function testVerifyAndRecover() {

		$this->MetaThing->id = 1;
		$this->MetaThing->setMeta('deep.nested.meta', array(
			'foo'	=> 'bar',
			'bar'	=> 'baz',
			'baz'	=> 'bat'
		));

		$this->assertTrue($this->MetaThing->verifyMeta());

		$result1 = $this->Metadatum->find('first', array(
			'fields'	=> array('id','lft','rght'),
			'conditions'=> array(
				'model'		=> 'MetaThing',
				'foreign_id'=> 1
			),
			'order'		=> 'lft ASC'
		));

		$this->Metadatum->id = $result1['Metadatum']['id'];
		$this->Metadatum->save(array(
			'rght'	=> $result1['Metadatum']['rght']+2
			), false, false);

		$verify = $this->MetaThing->verifyMeta();
		$this->assertTrue($verify !== true);
		$this->assertTrue($this->MetaThing->recoverMeta());
		$this->assertTrue($this->MetaThing->verifyMeta());

	}

}

?>