<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 22/11/10
	 * Time: 1:25 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Debtor_Account extends Debtor_Branch
	{
		public $accounts_id = 0;
		public $br_name = 'Accounts Department';
		public $branch_ref = 'accounts';

		public function __construct($id = null) {
			$this->accounts_id = &$this->branch_code;
			return parent::__construct($id);
		}

		protected function _defaults() {
			parent::_defaults();
			$this->branch_ref = 'accounts';
			$this->br_name = 'Accounts Department';
		}
	}
