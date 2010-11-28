<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 22/11/10
 * Time: 1:25 PM
 * To change this template use File | Settings | File Templates.
 */

class Accounts extends Branch {

	public $accounts_id = 0;
	public $br_name = 'Accounts Department';
	public $branch_ref = 'accounts';

	public function __construct($debtor_no = false) {

		if ($debtor_no === false) {
			$this->_status(false, 'Initialise Account Information', 'Debtor not passed and not a new debtor.');
			return false;
		} elseif ($debtor_no == 0) {
			$this->_new(array('debtor_no' => 0));
			return true;
		} elseif ($this->_getAccounts($debtor_no) === false) {
			$this->_status(true, 'Retrieving customer accounts/ info', 'No accounts information for this Customer found');
			$this->_new(array('debtor_no'=>$debtor_no));
		    return false;
		} elseif ($this->branch_code > 0) {
			$this->accounts_id = &$this->branch_code;
			return true;
		}
		return false;

	}

	public function save($changes = null) {
     
		if (is_array($changes)) {
			$tochange = array();
			foreach ($changes as $key => $value) {
                if (substr($key,0,4)=='acc_')
				$tochange[str_ireplace('acc_', '', $key)] = $value;
			}
		}

        $result = parent::save($tochange);
		$this->accounts_id = &$this->branch_code;
	    return $result;
	}
	protected function _getAccounts($debtor_no = null) {
		if ($debtor_no==null) {
			$this->_status(false,'Retrieving Accounts','No Customer ID to retrieve accounts for');
			return false;
		}
		return $this->_read(array('branch_ref' => 'accounts', 'debtor_no' => $debtor_no));

	}
}
