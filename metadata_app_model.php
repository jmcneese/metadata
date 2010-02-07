<?php

/**
 * Metadatum AppModel
 *
 * @package     metadata
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, HouseParty Inc.
 */
class MetadataAppModel extends AppModel {

	/**
	 * Called after each successful save operation.
	 *
	 * This function has been modified to return true instead of returning nothing
	 *
	 * @param	boolean	$created True if this save created a new record
	 * @return	boolean True if the callback was successful, false if not
	 */
	public function afterSave($created) {

		return true;

	}

	/**
	 * Saves model data (based on white-list, if supplied) to the database. By
	 * default, validation occurs before save.
	 *
	 * This function has been modified to return false if the result of afterSave returns false
	 *
	 * @param	array	$data		Data to save.
	 * @param	mixed	$validate	Either a boolean, or an array.
	 *  - If a boolean, indicates whether or not to validate before saving.
	 *  - If an array, allows control of validate, callbacks, and fieldList
	 * @param	array	$fieldList	List of fields to allow to be written
	 * @return	mixed On success Model::$data if its not empty or true, false on failure
	 */
	public function save($data = null, $validate = true, $fieldList = array()) {

		$_whitelist = $this->whitelist;
		$fields		= array();
		$defaults	= array(
			'validate' => true,
			'fieldList' => array(),
			'callbacks' => true
		);

		$options = (!is_array($validate))
			? array_merge($defaults, compact('validate', 'fieldList', 'callbacks'))
			: array_merge($defaults, $validate);

		$this->whitelist = (!empty($options['fieldList']))
			? $options['fieldList']
			: array();

		$this->set($data);

		if (
			empty($this->data) &&
			!$this->hasField(array('created', 'updated', 'modified'))
		) {
			return false;
		}

		foreach (array('created', 'updated', 'modified') as $field) {

			if (
				isset($this->data[$this->alias]) &&
				array_key_exists($field, $this->data[$this->alias]) &&
				$this->data[$this->alias][$field] === null
			) {

				unset($this->data[$this->alias][$field]);

			}

		}

		$exists		= $this->exists();
		$dateFields = array('modified', 'updated');

		if (!$exists) {

			$dateFields[] = 'created';

		}

		if (isset($this->data[$this->alias])) {

			$fields = array_keys($this->data[$this->alias]);

		}

		if ($options['validate'] && !$this->validates($options)) {

			$this->whitelist = $_whitelist;

			return false;

		}

		$db =& ConnectionManager::getDataSource($this->useDbConfig);

		foreach ($dateFields as $updateCol) {

			if ($this->hasField($updateCol) && !in_array($updateCol, $fields)) {

				$default = array('formatter' => 'date');
				$colType = array_merge(
					$default,
					$db->columns[$this->getColumnType($updateCol)]
				);

				$time = (!array_key_exists('format', $colType))
					? strtotime('now')
					: $colType['formatter']($colType['format']);

				if (!empty($this->whitelist)) {

					$this->whitelist[] = $updateCol;

				}

				$this->set($updateCol, $time);

			}

		}

		if ($options['callbacks'] === true || $options['callbacks'] === 'before') {

			$result = $this->Behaviors->trigger(
				$this,
				'beforeSave',
				array($options),
				array(
					'break' => true,
					'breakOn' => false
				)
			);

			if (!$result || !$this->beforeSave($options)) {

				$this->whitelist = $_whitelist;

				return false;

			} else if (!$exists && !empty($this->id)) {

				$exists = $this->exists();

			}

		}

		if (
			isset($this->data[$this->alias][$this->primaryKey]) &&
			empty($this->data[$this->alias][$this->primaryKey])
		) {

			unset($this->data[$this->alias][$this->primaryKey]);

		}

		$fields = $values = array();

		foreach ($this->data as $n => $v) {

			if (isset($this->hasAndBelongsToMany[$n])) {

				if (isset($v[$n])) {

					$v = $v[$n];

				}

				$joined[$n] = $v;

			} else {

				if ($n === $this->alias) {

					foreach (array('created', 'updated', 'modified') as $field) {

						if (array_key_exists($field, $v) && empty($v[$field])) {

							unset($v[$field]);

						}

					}

					foreach ($v as $x => $y) {

						if (
							$this->hasField($x) && (
								empty($this->whitelist) ||
								in_array($x, $this->whitelist)
							)
						) {

							list($fields[], $values[]) = array($x, $y);

						}

					}

				}

			}

		}

		$count = count($fields);

		if (!$exists && $count > 0) {

			$this->id = false;

		}

		$success = true;
		$created = false;

		if ($count > 0) {

			$cache = $this->_prepareUpdateFields(array_combine($fields, $values));

			if (!empty($this->id)) {

				$success = (bool)$db->update($this, $fields, $values);

			} else {

				foreach ($this->_schema as $field => $properties) {

					if ($this->primaryKey === $field) {

						$fInfo	= $this->_schema[$field];
						$isUUID = (
							$fInfo['length'] == 36 && (
								$fInfo['type'] === 'string' ||
								$fInfo['type'] === 'binary'
							)
						);

						if (
							empty($this->data[$this->alias][$this->primaryKey]) &&
							$isUUID
						) {

							list($fields[], $values[]) = array($this->primaryKey, String::uuid());

						}

						break;

					}

				}

				if (!$db->create($this, $fields, $values)) {

					$success = $created = false;

				} else {

					$created = true;

				}

			}

			if ($success && !empty($this->belongsTo)) {

				$this->updateCounterCache($cache, $created);

			}

		}

		if (!empty($joined) && $success === true) {

			$this->__saveMulti($joined, $this->id, $db);

		}

		if ($success && $count > 0) {

			if (!empty($this->data)) {

				$success = $this->data;

			}

			if (
				$options['callbacks'] === true ||
				$options['callbacks'] === 'after'
			) {

				$result = $this->Behaviors->trigger(
					$this,
					'afterSave',
					array($created, $options),
					array(
						'break'		=> true,
						'breakOn'	=> false
					)
				);

				$parent_result = $this->afterSave($created);

				if (
					(is_bool($result) && !$result) ||
					(is_bool($parent_result) && !$parent_result)
				) {

					return false;

				}

			}

			if (!empty($this->data)) {

				$success = Set::merge($success, $this->data);

			}

			$this->data = false;
			$this->_clearCache();
			$this->validationErrors = array();

		}

		$this->whitelist = $_whitelist;

		return $success;
		
	}

}

?>