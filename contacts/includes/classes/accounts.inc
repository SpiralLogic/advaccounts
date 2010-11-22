<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 22/11/10
 * Time: 1:25 PM
 * To change this template use File | Settings | File Templates.
 */

class Accounts extends Branch {


	public function __construct($debtor_id = false) {
		if ($debtor_id) {
			$result = $this->_getAccounts($debtor_id);
			if ($result !== true) {
				$this->_new(array(
					'debtor_id' => $debtor_id,
					'branch_ref' => 'accounts'
				));
			}
		}
		$this->_status(false, 'Retrieving Accounts', 'No customer to retrieve accounts for.');
	}

	protected function _getAccounts($debtor_id = false) {
		if (!$debtor_id) {
			return false;
		}
		$accounts_id = $this->_read(array('branch_ref' => 'accounts', 'debtor_no' => $debtor_id));
		if (!$accounts_id) {
			return false;
		}
		$this->_status(true, 'Retrieving Accounts', 'Customer Accounts successfully retrieved.');
		return true;
	}

}
