<?php

/**
 * MetadataBehavior
 *
 * Model behavior to support metadata
 *
 * @package     metadata
 * @subpackage  metadata.model.behavior
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009-2012 Joshua M. McNeese, HouseParty Inc.
 * @property    Metadatum   Metadatum
 */
class MetadataBehavior extends ModelBehavior {

	/**
	 * Contain validation errors per model
	 *
	 * @var array
	 */
	protected $_validationErrors = array();

	/**
	 * afterSave callback
	 *
	 * @param	Model	$Model
	 * @param	boolean $created
	 * @return	boolean
	 */
	public function afterSave(&$Model, $created) {
		return (
			isset($Model->data[$Model->alias]['Metadatum']) &&
			!empty($Model->data[$Model->alias]['Metadatum'])
		) ? $this->setMeta($Model, $Model->data[$Model->alias]['Metadatum']) : true;
	}

	/**
	 * _parseThreaded method
	 *
	 * Parse a nested array of data into a nicely formatted threaded array.
	 *
	 * @param   array   $data
	 * @return  array
	 */
	protected function _parseThreaded($data = array()) {
		$parsed = array();
		foreach($data as $datum) {
			$parsed[$datum['Metadatum']['name']] = (!empty($datum['children']))
				? $this->_parseThreaded($datum['children'])
				: $datum['Metadatum']['value'];
		}
		return $parsed;
	}

	/**
	 * _unflatten method
	 *
	 * "Unflattens" a delimited string into a nested array
	 *
	 * @param	string  $key
	 * @param	mixed	$val
	 * @param	string	$sep
	 * @return	array
	 */
	protected function _unflatten($key, $val, $sep = '.') {
		$data  = array();
		$parts = explode($sep, $key);
		$last  = end($parts);
		reset($parts);
		$tmp =& $data;
		foreach($parts as $part) {
			if($part == $last) {
				$tmp[$part] = $val;
			} elseif(!isset($tmp[$part])) {
				$tmp[$part] = array();
			}
			$tmp =& $tmp[$part];
		}
		return $data;
	}

	/**
	 * getMeta method
	 *
	 * Mapped method to retrieve metadata for an attached model.
	 *
	 * @param   Model   $Model
	 * @param   mixed   $options
	 * @return  mixed
	 */
	public function getMeta(&$Model, $options = array()) {
		if (is_string($options) || empty($options)) {
			$options = array(
				'name' => $options
			);
		}
		$options = array_merge(array(
			'name'      => null,
			'model'     => $Model->name,
			'foreign_id'=> $Model->id
		), $options);
		if(empty($options['foreign_id'])) {
			return false;
		}
		if (empty($options['name'])) {
			$Model->Metadatum->setScope($Model->name, $Model->id);
			$all = $Model->Metadatum->find('threaded', array(
				'fields'    => array('id','parent_id', 'name','value'),
				'conditions'=> array(
					'model'     => $options['model'],
					'foreign_id'=> $options['foreign_id']
				)
			));
			if (empty($all)) {
				return null;
			}
			return $this->_parseThreaded($all);
		}
		return $Model->Metadatum->getKey($options);
	}

	/**
	 * Mapped method to find invalid metadata for an attached model.
	 *
	 * @param   Model   $Model
	 * @param   array   $data
	 * @return  mixed
	 */
	public function invalidMeta(&$Model, $data = array()) {
		extract($this->settings[$Model->alias]);
		$this->_validationErrors[$Model->name] = $errors = array();
		if(isset($validate) && !empty($validate)) {
			App::uses('Validation', 'Utility');
            $methods = array_map('strtolower', get_class_methods($Model));
			foreach(Set::flatten($data, '/') as $k=>$v) {
				$rules = Set::extract("/{$k}/.", $validate);
				if(!empty($rules)) {
					foreach($rules as $ruleSet) {
                        if(!Set::numeric(array_keys($ruleSet))) {
                            $ruleSet = array($ruleSet);
                        }
                        foreach($ruleSet as $rule) {
                            if (
                                isset($rule['allowEmpty']) &&
                                $rule['allowEmpty'] === true &&
                                $v == ''
                            ) {
                                break 2;
                            }
                            if(is_array($rule['rule'])) {
                                $ruleName	= array_shift($rule['rule']);
                                $ruleParams	= $rule['rule'];
                                array_unshift($ruleParams, $v);
                            } else {
                                $ruleName	= $rule['rule'];
                                $ruleParams	= array($v);
                            }
                            $valid = true;
                            if (in_array(strtolower($ruleName), $methods)) {
                                $valid = $Model->dispatchMethod($ruleName, $ruleParams);
                            } elseif (method_exists('Validation', $ruleName)) {
								$valid = call_user_func_array("Validation::$ruleName", $ruleParams);
                            }
                            if(!$valid) {
                                $ruleMessage = (isset($rule['message']) && !empty($rule['message']))
                                    ? $rule['message']
                                    : sprintf('%s %s', 'Not', $rule);
                                $errors[] = $this->_unflatten($k, $ruleMessage, '/');
                                if (isset($rule['last']) && $rule['last'] === true) {
                                    break 3;
                                }
                            }
                        }
					}
				}
			}
		}
		if(empty($errors)) {
			return false;
		}
        $tmp_errors = array();
        foreach($errors as $error) {
            foreach($error as $field=>$message) {
                $tmp_errors[$field] = $message;
            }
        }
		$this->_validationErrors[$Model->name] = $tmp_errors;
		$Model->validationErrors = array_merge($Model->validationErrors, array(
			'Metadatum' => $tmp_errors
		));
		return $errors;
	}

	/**
	 * Mapped method to recover a scoped corrupted tree
	 *
	 * @param	Model	$Model Model instance
	 * @param	string	$mode parent or tree
	 * @param	mixed	$missingParentAction
	 *  - 'return' to do nothing and return
	 *	- 'delete' to delete
	 *  - the id of the parent to set as the parent_id
	 * @return	boolean true on success, false on failure
	 */
	public function recoverMeta(&$Model, $mode = 'parent', $missingParentAction = null) {
		$Model->Metadatum->setScope($Model->name, $Model->id);
		return $Model->Metadatum->recover($mode, $missingParentAction);
	}

	/**
	 * Mapped method to set metadata for an attached model.
	 *
	 * @param   Model   $Model
	 * @param   mixed   $key
	 * @param   mixed   $val
	 * @return  boolean
	 */
	public function setMeta(&$Model, $key = null, $val = null) {
		if(empty($Model->id) || empty($Model->name) || is_null($key)) {
			return false;
		}
		$extra = array(
			'model'     => $Model->name,
			'foreign_id'=> $Model->id
		);
		if(is_array($key)) {
			$invalid = $this->invalidMeta($Model, $key);
			return (empty($invalid))
				? $Model->Metadatum->setKey($key, $extra)
				: false;
		}
		$invalid = $this->invalidMeta($Model, $this->_unflatten($key, $val, '.'));
		return (empty($invalid))
			? $Model->Metadatum->setKey($key, $val, $extra)
			: false;
	}

	/**
	 * Initiate behavior for the model using specified settings.
	 *
	 * @param   Model   $Model      Model using the behaviour
	 * @param   array   $settings   Settings to override for model.
	 * @return  void
	 */
	public function setup(&$Model, $settings = array()) {
		$this->settings[$Model->alias] = Set::merge(array(
			'validate' => array()
		), $settings);
		$Model->Metadatum = ClassRegistry::init('Metadata.Metadatum');
	}

	/**
	 * Mapped method to get validationErrors for a model
	 *
	 * @param   Model   $Model
	 * @return  mixed
	 */
	public function validationErrorsMeta(&$Model) {
		return $this->_validationErrors[$Model->name];
	}

	/**
	 * Mapped method to set verify scoped tree for a model
	 *
	 * @param   Model   $Model
	 * @return  mixed
	 */
	public function verifyMeta(&$Model) {
		$Model->Metadatum->setScope($Model->name, $Model->id);
		return $Model->Metadatum->verify();
	}
	
}