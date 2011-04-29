<?php
/**
 * User: Sorijen
 * Date: 15/04/11 - 4:08 PM
 */

class contact extends DB_abstract
{
	public $id = 0;
	public $parent_id;
	public $name="New Contact";
	public $phone1;
	public $phone2;
	public $email;
	public $department;

	function __construct($id = null)
	{
		parent::__construct($id);
	
	}
	protected function _canProcess()
	{
		// TODO: Implement _canProcess() method.
	}

	protected function _countTransactions()
	{
		// TODO: Implement _countTransactions() method.
	}

	protected function _defaults()
	{
		$this->id = 0;
	}

	protected function _new()
	{
		$this->_defaults();
		return $this->_status(true, 'Initialize new Contact', 'Now working with a new Contact');
	}

	protected function _read($params = array())
	{
		if (!is_array($params)) {
			$params = array('id' => $params);
		} elseif (empty($params)) {
			$this->_status(false, 'Retrieving contact', 'No parameters provided');
			return false;
		}
		$sql = "SELECT *
		FROM " . TB_PREF . "contacts WHERE ";
		foreach ($params as $key => $value) {
			$where[] = "$key =" . db_escape($value);
		}
		$sql .= join(" AND ",$where);
		$result = db_query($sql, 'Could not get contact.');
		if (db_num_rows($result) < 1) {
			$this->_new();
		} else {
		$result = db_fetch_assoc($result);
		$this->setFromArray($result);
		}
		return true;

	}

	protected function _saveNew()
	{
		begin_transaction();
		$sql = "INSERT INTO " . TB_PREF . "contacts (parent_id, name, phone1, phone2, email, department)
				VALUES (" . db_escape($this->parent_id) . "," . db_escape($this->name) . ", " . db_escape($this->phone1) . ", " . db_escape($this->phone2) . ", " . db_escape($this->email) . ", " . db_escape($this->department).")";
		db_query($sql, "The contact could not be added");
		$this->id = db_insert_id();
		commit_transaction();
		$this->_status(true, 'Saving', "New contact has been added");
	}

	protected function delete()
	{
		// TODO: Implement delete() method.
	}

	function save($changes = null)
	{

		if (is_array($changes)) {
			$this->setFromArray($changes);
		}
		if ((int)$this->id == 0) {
			$this->_saveNew();
		}
		if (!$this->_canProcess()) {
			return false;
		}
		begin_transaction();
		$sql = "UPDATE " . TB_PREF . "contacts SET
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
