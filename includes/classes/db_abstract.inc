<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 8/01/11
 * Time: 2:53 AM
 * To change this template use File | Settings | File Templates.
 */
abstract class DB_abstract
{

	abstract protected function _canProcess();

	abstract function save($changes = null);

	abstract protected function _saveNew();

	abstract protected function _new();

	abstract protected function _read();

	abstract protected function delete();

	abstract protected function _defaults();

	abstract protected function _countTransactions();

	protected $_status = null;

	protected function _status($status = null, $process = null, $message = '', $var = null)
	{
		if (!$this->_status) {
			$this->_status = new Status();
		}
		$this->_status->set($status, $process, $message, $var);
	}

	public function getStatus()
	{
		return $this->_status->get();
	}

	protected function __construct($id = null)
	{
		if (is_array($id)) {
			$this->_defaults();
			$this->setFromArray($id);
			return $this->_status(true, 'initalise', get_class($this) . " details contructed!");
		}
		if ($id != null && $id != 0) {
			$this->_read($id);
			return $this->_status(true, 'initalise', get_class($this) . " details loaded from DB!");
		} else {
			$this->_new();
			return $this->_status(true, 'initalise', 'Created new ' . get_class($this) . "!");
		}
	}

	protected function setFromArray($changes = NULL)
	{
		if (!is_array($changes) || count($changes) == 0) {
			$this->_status(false, 'setFromArray', 'Variable array was either not passed, empty or is not an array');
			return false;
		}
		$remainder = array();
		foreach ($changes as $key => $value) {
			$value = (trim($value) == null) ? '' : trim($value);
			(property_exists($this, $key)) ? $this->$key = $value : $remainder[$key] = $value;
		}
		return $remainder;
	}
	protected function db_escape($value) {
		//reset default if second parameter is skipped
		//check for null/unset/empty strings
			if (is_string($value)) {
				//value is a string and should be quoted; determine best method based on available extensions
				if (function_exists('mysql_real_escape_string')) {
					$value = "'" . mysql_real_escape_string($value) . "'";
				} else {
					$value = "'" . mysql_escape_string($value) . "'";
				}
			} else if (!is_numeric($value)) {
				//value is not a string nor numeric
				display_error("ERROR: incorrect data type send to sql query");
				echo '<br><br>';
				exit();
			}
				return $value;
	}
}
