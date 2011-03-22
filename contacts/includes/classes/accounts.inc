<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 22/11/10
 * Time: 1:25 PM
 * To change this template use File | Settings | File Templates.
 */
class Accounts extends Branch
{
	public $accounts_id = 0;
	public $br_name = 'Accounts Department';
	public $branch_ref = 'accounts';

	function __construct($id = null)
	{
		parent::__construct($id);
		$this->accounts_id = $this->branch_code;
	}

	protected function _defaults()
	{
		parent::_defaults();
		$this->branch_ref = 'accounts';
		$this->br_name = 'Accounts Department';
	}

	public function save($changes = null)
	{
		if (is_array($changes)) $this->setFromArray($changes);
		parent::save();
		$this->accounts_id = $this->branch_code;
		$this->_status(true, 'save', 'Accounts Saved');
	}

}
