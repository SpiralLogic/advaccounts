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
  /**
   * @method Status getStatus()
   */
  abstract class Base {
    use \ADV\Core\Traits\SetFromArray;
    use \ADV\Core\Traits\Status;

    /** @var DB */
    static $DB;
    /**
     * @var int
     */
    public $id = 0;
    protected $_table;
    protected $_id_column;
    protected $_classname;
    abstract protected function canProcess();
    /**
     * @param int             $id    Id to read from database, or an array of changes which can include the id to load before applying changes or 0 for a new object
     * @param array           $extra
     *
     * @internal param \ADV\Core\DB\DB $db
     */
    public function __construct($id = 0, $extra = []) {
      static::$DB = DB::i();
      $this->load($id, $extra);
      $this->_classname = $this->_classname ? : end(explode('\\', ltrim(get_called_class(), '\\')));
      $_id_column       = $this->_id_column;
      if ($_id_column && $_id_column != 'id') {
        $this->id = &$this->$_id_column;
      }
    }
    /**
     * @param int   $id
     * @param array $extra
     *
     * @return \ADV\Core\Traits\Status|bool
     */
    public function load($id = 0, $extra = []) {
      $_id_column = $this->_id_column;
      if ((is_numeric($id) && $id > 0) || (is_string($id) && strlen($id) > 0)) {
        $this->$_id_column = $id;
        $this->read($id, $extra);

        return $this->status(Status::INFO, $this->_classname . " details loaded from DB!");
      } elseif (is_array($id)) {
        if (isset($id['id']) && !isset($id[$_id_column])) {
          $id[$_id_column] = $id['id'];
        }
        if (isset($id[$_id_column]) && $id[$_id_column]) {
          $this->read($id[$_id_column], $extra);
        } else {
          $this->init();
        }
        $this->setFromArray($id);

        return $this->status(Status::INFO, $this->_classname . " details constructed!");
      }

      return $this->init();
    }
    /**
     * @param array|null $changes can take an array of  changes  where key->value pairs match properties->values and applies them before save
     *
     * @return array|bool|int|null
     * @return \ADV\Core\Traits\Status|array|bool|int|string
     */
    public function save($changes = null) {
      if ($changes !== null) {
        $this->setFromArray($changes);
      }
      if (!$this->canProcess()) {
        return $this->getStatus();
      }
      if ($this->id == 0) {
        return $this->saveNew();
      }
      $data = (array) $this;

      static::$DB->begin();
      try {
        $updated = static::$DB->update($this->_table)->values($data)->where($this->_id_column . '=', $this->id)->exec();
      } catch (DBUpdateException $e) {
        static::$DB->cancel();

        return $this->status(Status::ERROR, "Could not update " . $this->_classname);
      }
      if (property_exists($this, 'inactive')) {
        try {
          /** @noinspection PhpUndefinedFieldInspection */
          static::$DB->updateRecordStatus($this->id, $this->inactive, $this->_table, $this->_id_column);
        } catch (DBUpdateException $e) {
          static::$DB->cancel();

          return $this->status(Status::ERROR, "Could not update active status of " . $this->_classname);
        }
      }
      static::$DB->commit();
      if (!$updated) {
        $this->id = 0;

        return $this->status(Status::WARNING, 'Could not find ' . $this->_classname . '  to update, save again to try and add as new.');
      }

      return $this->status(Status::SUCCESS, $this->_classname . ' changes saved to database.');
    }
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    public function delete() {

      try {
        $id_column = $this->_id_column;
        static::$DB->delete($this->_table)->where($id_column . '=', $this->$id_column)->exec();
      } catch (\DBDeleteException $e) {
        return $this->status(false, 'Could not delete' . $this->_classname);
      }
      $this->defaults();

      return $this->status(true, $this->_classname . ' deleted!');
    }
    public function getIDColumn() {
      return $this->_id_column;
    }
    protected function defaults() {
      $values = get_class_vars(get_called_class());
      unset($values['DB'], $values['_id_column'], $values['_table'], $values['_classname']);
      $this->setFromArray($values);
    }
    /**
     * @return bool
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function init() {
      $this->defaults();

      return $this->status(Status::INFO, 'Now working with new ' . $this->_classname);
    }
    /***
     * @param int   $id    Id of row to read from database
     * @param array $extra
     *
     * @throws \DBException
     * @return bool
     */
    protected function read($id, $extra = []) {
      $this->defaults();
      if (!$this->_table || !$this->_id_column) {
        throw new DBException('No table name or id column for class: ' . get_called_class() . '(' . $this->_classname . ')');
      }
      try {
        $query = static::$DB->select()->from($this->_table);
        $query = $this->getSelectModifiers($query)->where($this->_id_column . '=', $id);
        foreach ($extra as $field => $value) {
          $query->andWhere($field . '=', $value);
        }
        static::$DB->fetch()->intoClass($this);
      } catch (DBSelectException $e) {
        return $this->status(false, 'Could not read ' . $this->_classname, (string) $id);
      }

      return $this->status(Status::INFO, 'Successfully read ' . $this->_classname, $id);
    }
    /**
     * @param \ADV\Core\DB\Query\Select $query
     *
     * @return \ADV\Core\DB\Query\Select
     */
    protected function getSelectModifiers(\ADV\Core\DB\Query\Select $query) {
      return $query;
    }
    /**
     * @return int|bool Id assigned to new database row or false if entry failed
     */
    protected function saveNew() {
      try {
        $this->id = static::$DB->insert($this->_table)->values((array) $this)->exec();
      } catch (DBInsertException $e) {
        return $this->status(false, 'Could not add ' . $this->_classname . 'to database');
      } catch (DBDuplicateException $e) {
        return $this->status(false, 'You have tried to enter a duplicate ' . $this->_classname . '. Please modify the existing record or use different values.');
      }

      return $this->status(true, 'Added ' . $this->_classname . ' to database');
    }
  }

