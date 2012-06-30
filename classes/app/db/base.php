<?php
  use ADV\Core\DB\DBDuplicateException;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  abstract class DB_Base
  {
    use \ADV\Core\Traits\SetFromArray;
    use \ADV\Core\Traits\Status;

    /**
     * @var int
     */
    public $id = 0;
    /**
     * @var
     */
    protected $_table;
    /**
     * @var
     */
    protected $_id_column;
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
      $data = (array) $this;
      DB::begin();
      try {
        DB::update($this->_table)->values($data)->where($this->_id_column . '=', $this->id)->exec();
      }
      catch (DBUpdateException $e) {
        DB::cancel();

        return $this->_status(Status::ERROR, 'write', "Could not update " . get_class($this));
      }
      if (property_exists($this, 'inactive')) {
        try {
          /** @noinspection PhpUndefinedFieldInspection */
          DB::update_record_status($this->id, $this->inactive, $this->_table, $this->_id_column);
        }
        catch (DBUpdateException $e) {
          DB::cancel();

          return $this->_status(Status::ERROR, 'write', "Could not update active status of " . get_class($this));
        }
      }
      DB::commit();

      return $this->_status(Status::SUCCESS, 'write', get_class($this) . ' changes saved to database.');
    }
    /**
     * @param int   $id    Id to read from database, or an array of changes which can include the id to load before applying changes or 0 for a new object
     * @param array $extra
     */
    protected function __construct($id = 0, $extra = array())
    {
      $_id_column = $this->_id_column;
      if ($_id_column && $_id_column != 'id') {
        $this->id = &$this->$_id_column;
      }
      if (is_numeric($id) && $id > 0) {
        $this->id = $id;
        $this->_read($id, $extra);

        return $this->_status(true, 'initalise', get_class($this) . " details loaded from DB!");
      } elseif (is_array($id)) {
        $this->_defaults();
        if (isset($id['id']) && $id['id']) {
          $this->_read($id['id'], $extra);
        } else {
          $this->_new();
        }
        $this->setFromArray($id);

        return $this->_status(true, 'initalise', get_class($this) . " details constructed!");
      }

      return $this->_new();
    }
    /***
     * @param int   $id    Id of row to read from database
     * @param array $extra
     *
     * @return bool
     */
    protected function _read($id = null, $extra = array())
    {
      if ($id === null) {
        return $this->_status(false, 'read', 'No ' . get_class($this), ' ID to read');
      }
      $this->_defaults();
      try {
        $query = DB::select()->from($this->_table)->where($this->_id_column . '=', $id);
        foreach ($extra as $field => $value) {
          $query->and_where($field . '=', $value);
        }
        DB::fetch()->intoClass($this);
      }
      catch (DBSelectException $e) {
        return $this->_status(false, 'read', 'Could not read ' . get_class($this), (string) $id);
      }

      return $this->_status(true, 'read', 'Successfully read ' . get_class($this), $id);
    }
    /**
     * @return int|bool Id assigned to new database row or false if entry failed
     */
    protected function _saveNew()
    {
      try {
        $this->id = DB::insert($this->_table)->values((array) $this)->exec();
      }
      catch (DBInsertException $e) {
        return $this->_status(false, 'write', 'Could not add to databse: ' . get_class($this));
      }
      catch (DBDuplicateException $e) {
        return $this->_status(false, 'write', $e->getMessage() . '. The entered information is a duplicate. Please modify the existing record or use different values.');
      }

      return $this->_status(true, 'write', 'Added to databse: ' . get_class($this));
    }
  }

