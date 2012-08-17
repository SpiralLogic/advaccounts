<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Contact;
  use \ADV\App\DB\Base;

  /**

   */
  class Contact extends Base
  {
    /**
     * @var int
     */
    public $id = 0;
    public $contact_id = 0;
    /**
     * @var int
     */
    public $parent_id = 0;
    /**
     * @var int
     */
    public $parent_type;
    /**
     * @var string
     */
    public $name = "New Contact";
    /**
     * @var string
     */
    public $phone1 = '';
    /**
     * @var string
     */
    public $phone2 = '';
    /**
     * @var string
     */
    public $email = '';
    /**
     * @var string
     */
    public $department = '';
    /**
     * @var string
     */
    protected $_table = 'contacts';
    /**
     * @var string
     */
    protected $_id_column = 'contact_id';
    /**
     * @param int       $type
     * @param array|int $id
     */
    public function __construct($type, $id = 0)
    {
      $this->parent_type = (int) $type;
      parent::__construct($id, array('parent_type' => $type));
    }
    public function delete()
    {
      // TODO: Implement delete() method.
    }
    /**
     * @return bool
     */
    protected function _canProcess()
    {
      return true;
    }
    /**
     * @return bool|int
     */
    protected function _saveNew()
    {
      $temp = new Contact($this->parent_type);
      foreach ($this as $key => $value) {
        if ($key != 'parent_id' && $key != 'id' && $key != 'parent_type' && $key != '_status' && $temp->$key != $value) {
          return parent::_saveNew();
        }
      }

      return false;
    }
    protected function _countTransactions()
    {
      // TODO: Implement _countTransactions() method.
    }
    protected function _defaults()
    {
      $this->id = (int) $this->id;
    }
    /**
     * @return bool|\Status
     */
    protected function _new()
    {
      $this->_defaults();

      return $this->status(true, 'Initialize new Contact', 'Now working with a new Contact');
    }
  }
