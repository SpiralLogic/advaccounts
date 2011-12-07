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

		public function __construct($id = null) {
			if (is_numeric($id)) {
				$this->id = $id;
			}
			parent::__construct($id);
		}
		public function delete() {
			// TODO: Implement delete() method.
		}

		public function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ((int)$this->id == 0) {
				$this->_saveNew();
			}
			DB::begin_transaction();
			$sql = "UPDATE contacts SET
					name=" . DB::escape($this->name) . ",
					phone1=" . DB::escape($this->phone1) . ",
					phone2=" . DB::escape($this->phone2) . ",
					email=" . DB::escape($this->email) . ",
					department=" . DB::escape($this->department) . " WHERE parent_id =" . DB::escape($this->parent_id) . "
		 	 AND id=" . DB::escape($this->id);
			DB::query($sql, "The customer could not be updated");
			DB::commit_transaction();
			return $this->_status(true, 'Processing', "Contact has been updated.");
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
			return false;
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
			if (!$id) {
				return false;
			}
			DB::select()->from('contacts')->where('id=', $id);
			DB::fetch()
			 ->intoClass($this);
			return true;
		}

		protected function _saveNew() {
			$this->id = DB::insert('contacts')->exec($this);
			$this->_status(true, 'Saving', "New contact has been added");
		}

	}