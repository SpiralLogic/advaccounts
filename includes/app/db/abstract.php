<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/01/11
	 * Time: 2:53 AM
	 * To change this template use File | Settings | File Templates.
	 */
	abstract class DB_abstract
	{
		public $id = 0;
		protected $_table;
		protected $_id_column;
		/** @var Status */
		protected $_status = null;
		abstract public function delete();
		abstract protected function _canProcess();
		abstract protected function _defaults();
		abstract protected function _new();
		/**
		 * @param bool $string return status as string if true and as array if false
		 *
		 * @return string|array
		 */
		public function getStatus($string = false) {
			if ($string) {
				return $this->_status;
			}
			return $this->_status->get();
		}
		/**
		 * @param array|null $changes can take an array of	changes	where key->value pairs match properties->values and applies them before save
		 *
		 * @return array|bool|false|int|null
		 */
		public function save($changes = null) {
			if ($changes !== null) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ($this->id == 0) {
				return $this->_saveNew();
			}
			$data = (array)$this;
			DB::begin();
			try {
				DB::update($this->_table)->values($data)->where($this->_id_column . '=', $this->id)->exec();
			} catch (DBUpdateException $e) {
				DB::cancel();
				return $this->_status(false, 'write', "Could not update " . get_class($this));
			}
			if (property_exists($this, 'inactive')) {
				try {
					DB::update_record_status($this->id, $this->inactive, $this->_table, $this->_id_column);
				} catch (DBUpdateException $e) {
					DB::cancel();
					return $this->_status(false, 'write', "Could not update active status of " . get_class($this));
				}
			}
			DB::commit();
			return $this->_status(true, 'write', get_class($this) . ' changes saved to database.');
		}
		/**
		 * @param int $id Id to read from database, or an array of changes which can include the id to load before applying changes or 0 for a new object
		 */
		protected function __construct($id = 0) {
			if (is_numeric($id) && $id > 0) {
				$this->id = $id;
				$this->_read($id);
				return $this->_status(true, 'initalise', get_class($this) . " details loaded from DB!");
			}
			elseif (is_array($id)) {
				$this->_defaults();
				if (isset($id['id']) && $id['id']) {
					$this->_read($id['id']);
				}
				else {
					$this->_new();
				}
				$this->setFromArray($id);
				return $this->_status(true, 'initalise', get_class($this) . " details constructed!");
			}
			return $this->_new();
		}
		/***
		 * @param int $id Id of row to read from database
		 *
		 * @return bool
		 */
		protected function _read($id = null) {
			if ($id === null) {
				return $this->_status(false, 'read', 'No ' . get_class($this), ' ID to read');
			}
			$this->_defaults();
			try {
				DB::select()->from($this->_table)->where($this->_id_column . '=', $id);
				DB::fetch()->intoClass($this);
			} catch (DBSelectException $e) {
				return $this->_status(false, 'read', 'Could not read ' . get_class($this), (string)$id);
			}
			return $this->_status(true, 'read', 'Successfully read ' . get_class($this), $id);
		}
		/**
		 * @return int|false Id assigned to new database row or false if entry failed
		 */
		protected function _saveNew() {
			try {
				$this->id = DB::insert($this->_table)->values((array)$this)->exec();
			} catch (DBInsertException $e) {
				return $this->_status(false, 'write', 'Could not add to databse: ' . get_class($this));
			} catch (DBDuplicateException $e) {
				return $this->_status(false, 'write', $e->getMessage() . '. The entered information is a duplicate. Please modify the existing record or use different values.');
			}
			return $this->_status(true, 'write', 'Added to databse: ' . get_class($this));
		}
		/**
		 * @param null $changes
		 *
		 * @return array|null
		 */
		protected function setFromArray($changes = NULL) {
			if (!$changes || !is_array($changes)) {
				return;
			}
			foreach ($changes as $key => $value) {
				if (is_object($value)) {
					$value = (array)$value;
				}
				if (!is_array($value)) {
					$value = (trim($value) == null) ? '' : trim($value);
				}
				if (property_exists($this, $key)) {
					if ($this->$key == null && ($value === '' || $value === 'null')) {
						$value = null;
					}
					$this->$key = $value;
				}
			}
		}
		/***
		 * @param null	 $status
		 * @param null	 $process
		 * @param string $message
		 * @param null	 $var
		 *
		 * @return array|null
		 */
		protected function _status($status = null, $process = null, $message = '', $var = null) {
			if ($var === null) {
				$var = $this->id;
			}
			if (!$this->_status) {
				$this->_status = new Status($status, $process, $message, $var);
				return $status;
			}
			return $this->_status->set($status, $process, $message, $var);
		}
	}

