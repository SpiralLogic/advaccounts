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
		protected $_table = 'contacts';
		protected $_id_column = 'id';

		public function __construct($id = null) {
			if (is_numeric($id)) {
				$this->id = $id;
			}
			parent::__construct($id);
		}
		public function delete() {
			// TODO: Implement delete() method.
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




	}
