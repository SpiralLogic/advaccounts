<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 8/01/11
 * Time: 2:53 AM
 * To change this template use File | Settings | File Templates.
 */
 
abstract class DB_abstract {
    abstract protected function _canProcess();

    abstract function save($changes = null);

    abstract protected function _saveNew();

    abstract protected function _new();

    abstract protected function _read();

    abstract protected function delete();

    abstract protected function _defaults();

    abstract protected function _countTransactions();

    protected function _status($status = null, $process = null, $message = '', $var = null) {
        if (!$this->_status) {
            $this->_status = new Status();
        }
        $this->_status->set($status, $process, $message, $var);
    }

    protected function __construct($id = null) {
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

    protected function setFromArray($changes = NULL) {
        if ($changes == null || empty($changes) || !is_array($changes)) {
            $this->_status(false, 'setFromArray', 'Variable array was either not passed, empty or is not an array');
            return false;
        }
        $remainder = array();
        foreach ($changes as $key => $value) {
            (property_exists($this, $key)) ? $this->$key = $value : $remainder[$key] = $value;
        }
        return $remainder;
    }
}
