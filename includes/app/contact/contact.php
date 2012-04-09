<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Contact extends DB_abstract {

    public $id = 0;
    public $parent_id = 0;
    public $type;
    public $name = "New Contact";
    public $phone1 = '';
    public $phone2 = '';
    public $email = '';
    public $department = '';
    protected $_table = 'contacts';
    protected $_id_column = 'id';
    /**
     * @param int       $type
     * @param array|int $id
     */
    public function __construct($type, $id = 0) {
      $this->type = $type;
      parent::__construct($id, array('type' => $type));
    }

    public function delete() {
      // TODO: Implement delete() method.
    }
    /**
     * @return bool
     */
    protected function _canProcess() {
      return TRUE;
    }
    /**
     * @return bool|int
     */
    protected function _saveNew() {
      $temp = new Contact($this->type);
      foreach ($this as $key => $value) {
        if ($key != 'parent_id' && $key != 'id' && $key != 'type' && $key != '_status' && $temp->$key != $value) {
          return parent::_saveNew();
        }
      }
      return FALSE;
    }

    protected function _countTransactions() {
      // TODO: Implement _countTransactions() method.
    }

    protected function _defaults() {
    }
    /**
     * @return bool|Status
     */
    protected function _new() {
      $this->_defaults();
      return $this->_status(TRUE, 'Initialize new Contact', 'Now working with a new Contact');
    }
  }
