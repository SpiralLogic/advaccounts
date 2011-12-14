<?php
	/**
	 * User: Sorijen
	 * Date: 15/04/11 - 4:08 PM
	 */
	class Contacts_Contact extends DB_abstract
	{
		public $id = 0;
		public $parent_id = 0;
		public $name = "New Contact";
		public $phone1 = '';
		public $phone2 = '';
		public $email = '';
		public $department = '';
		protected $_table = 'contacts';
		protected $_id_column = 'id';

		public function __construct($id = 0) {
			parent::__construct($id);
		}

		public function delete() {
			// TODO: Implement delete() method.
		}

		protected function _canProcess() {
			return true;
		}

		protected function _saveNew() {
			$temp = new Contacts_Contact();
			foreach ($this as $key => $value) {
				if ($key != 'parent_id' && $key != 'id' && $key != '_status' && $temp->$key != $value) {
					return parent::_saveNew();
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
	}
