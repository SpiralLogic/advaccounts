<?php
	/**
	 * User: Sorijen
	 * Date: 15/04/11 - 4:08 PM
	 */
	class Contacts_Contact extends DB_abstract {

		public $id = 0;
		public $parent_id = 0;
		public $name = "New Contact";
		public $phone1 = '';
		public $phone2 = '';
		public $email = '';
		public $department = '';

		function __construct($id = null) {
			if (is_numeric($id)) $this->id = $id;
			parent::__construct($id);
		}

		protected function _canProcess() {
			$temp = new Contacts_Contact();
			if ($this->id > 0) {
				return true;
			}
			foreach ($this as $key => $value) {
				if ($key != 'parent_id' && $key != 'id' && $key != '_status' && $temp->$key !== $value) {
					return true;
				}
			}
		}

		protected function _countTransactions() {
			// TODO: Implement _countTransactions() method.
		}

		protected function _defaults() {
		}

		protected function _new() {
			$this->_defaults();
			return $this->_status(true, 'Initialize new Contact', 'Now working with a new Contact');
		}

		protected function _read($id = false) {
			if (!$id) return;
			DB::select()->from('contacts')->where('id=', $id);
			DB::fetch()->intoClass($this);
			return true;
		}

		protected function _saveNew() {

			$this->id = DB::insert('contacts')->exec($this);

			$this->_status(true, 'Saving', "New contact has been added");
		}

		protected function delete() {
			// TODO: Implement delete() method.
		}

		function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ((int)$this->id == 0) {
				$this->_saveNew();
			}
			DBOld::begin_transaction();
			$sql = "UPDATE contacts SET
			name=" . DBOld::escape($this->name) . ",
			phone1=" . DBOld::escape($this->phone1) . ",
			phone2=" . DBOld::escape($this->phone2) . ",
			email=" . DBOld::escape($this->email) . ",
			department=" . DBOld::escape($this->department) . " WHERE parent_id =" . DBOld::escape($this->parent_id) . "
    	    AND id=" . DBOld::escape($this->id);
			DBOld::query($sql, "The customer could not be updated");
			DBOld::commit_transaction();
			return $this->_status(true, 'Processing', "Contact has been updated.");
		}
	}
