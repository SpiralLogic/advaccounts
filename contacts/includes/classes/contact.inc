<?php
/**
 * User: Sorijen
 * Date: 15/04/11 - 4:08 PM
 */
	class Contact extends DB_abstract {

		public $id = 0;
		public $parent_id=0;
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
			$temp = new Contact();
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

		protected function _read($id=false) {
			if (!$id) return;
			DB::select()->from('contacts')->where('id=',$id);
			DB::fetch()->intoClass($this);
			return true;
		}

		protected function _saveNew() {

			DB::insert('contacts')->exec($this);
			DB::fetch();
			$this->id = db_insert_id();

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
			begin_transaction();
			$sql = "UPDATE contacts SET
			name=" . db_escape($this->name) . ",
			phone1=" . db_escape($this->phone1) . ",
			phone2=" . db_escape($this->phone2) . ",
			email=" . db_escape($this->email) . ",
			department=" . db_escape($this->department) . " WHERE parent_id =" . db_escape($this->parent_id) . "
    	    AND id=" . db_escape($this->id);
			db_query($sql, "The customer could not be updated");
			commit_transaction();
			return $this->_status(true, 'Processing', "Contact has been updated.");
		}

	}
