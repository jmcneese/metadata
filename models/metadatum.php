<?php

/**
 * Metadatum model
 *
 * @package     metadata
 * @subpackage  metadata.models
 * @see         MetadataBehavior
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, HouseParty Inc.
 */
class Metadatum extends MetadataAppModel {

    /**
     * @var     array
     */
    public $actsAs = array('Tree');

	/**
     * @var     array
     */
    public $validate = array(
        'name'	=> array(
			'rule'		=> 'notEmpty',
			'message'	=> 'Name cannot be empty'
		)
    );

    /**
     * Internal method to locate the entire node represented by a key.
     *
     * @param   mixed   $options
     * @return  mixed
     */
    private function _findNode($options = array()) {

        if (is_string($options)) {

            $options = array(
                'name' => $options
            );

        }

        $options = Set::merge(array(
            'model'     => null,
            'foreign_id'=> null,
            'name'      => null
        ), $options);

        unset($options['value']);

		$this->setScope($options['model'], $options['foreign_id']);

        $path       = explode('.', $options['name']);
        $path_count = count($path);
        $parent_id  = null;

        foreach($path as $idx=>$path_key) {

            $node = $this->find('first', array(
                'conditions'=> array_merge(
                    $options,
                    array(
                        'parent_id' => $parent_id,
                        'name'      => $path_key
                    )
                )
            ));

            if (empty($node)) {

                break;

            }

            $node = $node['Metadatum'];

            if ($path_count == ($idx+1)) {

                if (($node['rght'] > $node['lft']+1)) {

                    $children = $this->children($node['id'], true);

                    if (!empty($children)) {

                        $node['value'] = Set::extract($children, '/Metadatum/.');

                    }

                }

                break;

            }

            $parent_id  = $node['id'];

        }

        return $node;

    }

    /**
     * Mapped method that ensures that the model/fk fields are null if empty.
     *
     * @param   array   $options
     * @return  boolean
     */
    public function beforeSave($options) {

        if (
            !empty($this->data) and
            isset($this->data['Metadatum']) and
            !empty($this->data['Metadatum'])
        ) {

            foreach(array('model','foreign_id') as $field) {

                if (!isset($this->data['Metadatum'][$field])) {

                    continue;

                }

                if (empty($this->data['Metadatum'][$field])) {

                    unset($this->data['Metadatum'][$field]);

                }

            }

        }
        
        return parent::beforeSave($options);

    }

    /**
     * Retrieves a datum by key.
     *
     * @param   mixed   $options
     * @return  mixed
     */
    public function getKey($options = array()) {

        $node = $this->_findNode($options);

        if (!empty($node)) {

            if (is_array($node['value'])) {

                return Set::combine($node['value'], '/name', '/value');

            }

            return $node['value'];

        }

        return null;

    }

	/**
	 * Internal method to save a metadata node
	 *
	 * @param	array	$data
	 * @return	mixed
	 */
	private function _saveNode($data) {

		$data = array_merge(array(
			'parent_id'	=> null,
			'model'		=> null,
			'foreign_id'=> null,
			'name'		=> null,
			'value'		=> null
		), $data);

		if (!empty($data['id'])) {

            $this->delete($data['id']);

            unset($data['id']);

        }

		if(strpos($data['name'], '.') !== false) {

			$path       = explode('.', $data['name']);
			$path_count = count($path);
			$path_exists= array();
			$parent_id  = null;
			$last		= null;
			$value      = null;

			if (isset($data['value'])) {

				$value = $data['value'];

				unset($data['value']);

			}

			foreach ($path as $idx=>$key) {

				$path_check = array_merge($path_exists, array($key));
				$node       = $this->_findNode(array_merge(
					$data,
					array(
						'name' => implode('.', $path_check)
					)
				));

				if (empty($node)) {

					$data = array_merge(
						$data,
						array(
							'parent_id' => $parent_id,
							'name'      => $key
						)
					);

					if ($path_count == ($idx+1)) {

						$data['value'] = $value;

					}

					$node = $this->_saveNode($data);

				} elseif ($path_count == ($idx+1)) {

					if($node['value'] == $value) {

						return true;

					}

					$node['value'] = $value;

					$node = $this->_saveNode($node);

				}

				$path_exists = $path_check;
				$parent_id   = $node['id'];

			}

			return $node;

		} elseif(is_array($data['value'])) {

			$values		= $data['value'];
			$parent		= null;
			$parent_id	= null;

            unset($data['value']);
			
			if(!empty($data['name'])) {

				$parent = $this->_findNode($data);

				if(empty($parent)) {

					$parent	= $this->_saveNode($data);

				}
				
				$parent_id = $parent['id'];
				
			}

            foreach($values as $key=>$val) {

                $node = $this->_saveNode(array_merge(
					$data, array(
						'parent_id' => $parent_id,
						'name'      => $key,
						'value'     => $val
					)
				));

            }

            return ($parent) ? $parent : $node;

		}

		$node = $this->_findNode($data);

		if(!empty($node)) {

			if((!isset($data['value']) || is_null($data['value'])) &&
				$node['rght'] > $node['lft']+1
			) {

				$this->delete($node['id']);

			} else {

				$data = array_merge($node, $data);

			}

		} else {

			$this->create();

		}

		$node = $this->save($data);

		if (!empty($node)) {

			$node['Metadatum']['id'] = $this->id;

		}

		return (!empty($node)) ? $node['Metadatum'] : false;

	}

	/**
	 * Public method to save a metadatum node
	 *
	 * Accepts a key/value, where key is a dot-noted string and value is any type
	 * except null.  Also accepts a multidimensional array as 'key'.  $extra is
	 * data to be merged with the keys contained in a multidim array (when set
	 * as 'key')
	 *
	 * @param	mixed	$key
	 * @param	mixed	$value
	 * @param	array	$extra
	 * @return	boolean
	 */
	public function setKey($key = null, $value = null, $extra = null) {

		$data = array(
			'parent_id'	=> null,
			'model'		=> null,
			'foreign_id'=> null,
			'name'		=> null,
			'value'		=> null
		);

		if (is_string($key)) {

            $data = array_merge($data, array(
                'name'  => $key,
                'value' => $value
            ));
			
			if(is_array($extra) && !empty($extra)) {

				$data = array_merge($data, $extra);
			}

        } elseif (is_array($key) && !empty($key)) {

			$data = array_merge($data, array('value'=>$key));
			
			if(is_array($value) && !empty($value)) {

				$data = array_merge($data, $value);
			}

		}

		return ($this->_saveNode($data) !== false);

	}

	/**
	 * Sets the metadata tree to be scoped on a particular Model/id record, which
	 * results in many trees in the table, each scoped to a particular record.
	 *
	 * @param	string	$model
	 * @param	mixed	$foreign_id
	 */
	public function setScope($model = null, $foreign_id = null) {

		$scope = (!empty($model)) ? array(
			'Metadatum.model'		=> $model,
			'Metadatum.foreign_id'	=> $foreign_id
		) : '1 = 1';

		$this->Behaviors->Tree->settings['Metadatum']['scope'] = $scope;

	}

}

?>