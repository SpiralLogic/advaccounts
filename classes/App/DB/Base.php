<?php
  namespace ADV\App\DB;
  use ADV\Core\DB\DBDuplicateException;
  use ADV\Core\DB\DBInsertException;
  use ADV\Core\DB\DBSelectException;
  use ADV\Core\Status;
  use ADV\Core\DB\DBUpdateException;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  abstract class Base
  {
    use \ADV\Core\Traits\SetFromArray;
    use \ADV\Core\Traits\Status;

    /**
     * @var int
     */
    public $id = 0;
    /** @var */
    protected $_table;
    /** @var */
    protected $_id_column;
    protected $_classanme;
    abstract public function delete();
    abstract protected function _canProcess();
    abstract protected function _defaults();
    abstract protected function _new();
    /**
     * @param array|null $changes can take an array of  changes  where key->value pairs match properties->values and applies them before save
     *
     * @return array|bool|int|null
     */
    public function save($changes = null)
    {
      if ($changes !== null) {
        $this->setFromArray($changes);
      }
      if (!$this->_canProcess()) {
        return false;
      }
      if ($this->id == 0) {
        return $this->_saveNew();
      }
      $data      = (array) $this;

      DB::_begin();
      try {
        $updated = DB::_update($this->_table)->values($data)->where($this->_id_column . '=', $this->id)->exec();
      } catch (DBUpdateException $e) {
        DB::_cancel();
        return $this->status(Status::ERROR, 'write', "Could not update " . $this->_classname);
      }
      if (property_exists($this, 'inactive')) {
        try {
          /** @noinspection PhpUndefinedFieldInspection */
          DB::_updateRecordStatus($this->id, $this->inactive, $this->_table, $this->_id_column);
        } catch (DBUpdateException $e) {
          DB::_cancel();
          return $this->status(Status::ERROR, 'write', "Could not update active status of " . $this->_classname);
        }
      }
      DB::_commit();
      if (!$updated) {
        $this->id = 0;
        return $this->status(Status::WARNING, 'write', 'Could not find ' . $this->_classname. '  to update, save again to try and add as new.');
      }
      return $this->status(Status::SUCCESS, 'write', $this->_classname. ' changes saved to database.');
    }
    /**
     * @param int   $id    Id to read from database, or an array of changes which can include the id to load before applying changes or 0 for a new object
     * @param array $extra
     */
    protected function __construct($id = 0, $extra = [])
    {
      $_id_column = $this->_id_column;
      $this->_classname = end(explode('\\', get_class($this)));
      if ($_id_column && $_id_column != 'id') {
        $this->id = &$this->$_id_column;
      }
      if (is_numeric($id) && $id > 0) {
        $this->id = $id;
        $this->_read($id, $extra);
        return $this->status(true, 'initalise',$this->_classanme  . " details loaded from DB!");
      } elseif (is_array($id)) {
        $this->_defaults();
        if (isset($id['id']) && $id['id']) {
          $this->_read($id['id'], $extra);
        } else {
          $this->_new();
        }
        $this->setFromArray($id);
        return $this->status(true, 'initalise', $this->_classname . " details constructed!");
      }
      return $this->_new();
    }
    /***
     * @param int   $id    Id of row to read from database
     * @param array $extra
     *
     * @return bool
     */
    protected function _read($id = null, $extra = [])
    {
      if ($id === null) {
        return $this->status(false, 'read', 'No ' . $this->_classname, ' ID to read');
      }
      $this->_defaults();
      try {
        $query = DB::_select()->from($this->_table)->where($this->_id_column . '=', $id);
        foreach ($extra as $field => $value) {
          $query->andWhere($field . '=', $value);
        }
        DB::_fetch()->intoClass($this);
      } catch (DBSelectException $e) {
        return $this->status(false, 'read', 'Could not read ' . $this->_classname, (string) $id);
      }
      return $this->status(true, 'read', 'Successfully read ' . $this->_classname, $id);
    }
    /**
     * @return int|bool Id assigned to new database row or false if entry failed
     */
    protected function _saveNew()
    {
      try {
        $this->id = DB::_insert($this->_table)->values((array) $this)->exec();
      } catch (DBInsertException $e) {
        return $this->status(false, 'write', 'Could not add to databse: ' . $this->_classname);
      } catch (DBDuplicateException $e) {
        return $this->status(false, 'write', $e->getMessage() . '. The entered information is a duplicate. Please modify the existing record or use different values.');
      }
      return $this->status(true, 'write', 'Added to databse: ' . $this->_classname);
    }
  }

