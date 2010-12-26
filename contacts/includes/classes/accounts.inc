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

    public function __construct($accounts_id = false) {
        if ($accounts_id === false) {
            $this->_status(false, 'Initialise Account Information', 'Debtor not passed and not a new debtor.');
            return false;
		} elseif ($accounts_id == 0) {
            $this->_new(array('debtor_no' => 0));
            return true;
        }
        $this->accounts_id = $accounts_id;
        $this->_getAccounts();
        if ($this->branch_code == 0) {
            $this->_status(true, 'Retrieving customer accounts/ info', 'No accounts information for this Customer found');
            $this->_new(array('debtor_no' => $this->debtor_no));
            return false;
        }

    }

    public function save($changes = null) {
        if (is_array($changes)) {
            $tochange = array();
            foreach ($changes as $key => $value) {
                if (substr($key, 0, 4) == 'acc_') {
                    unset($changes[$key]);
                    $tochange[str_ireplace('acc_', '', $key)] = $value;
                }
            }
        }
        parent::save($tochange);
        $this->accounts_id = $this->branch_code;
        return $changes;
    }

    protected function _getAccounts() {
        $accounts_id["branch_code"] = $this->accounts_id;
        $accounts_id["branch_ref"] = "accounts";
        $this->_read($accounts_id);
    }
}
