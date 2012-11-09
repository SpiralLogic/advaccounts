<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      9/11/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\DB;

  use InvalidArgumentException;

  /**
   *
   */
  class Collection implements \ArrayAccess, \Iterator, \Countable
  {
    protected $class;
    protected $table;
    /** @var Base[] $collection */

    protected $collection = [];
    protected $current = 0;
    protected $idColumns = [];
    /** @var \ADV\Core\DB\DB */
    static $staticDB;
    /**
     * @param Base $object
     * @param      $idColumns
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Base $object, $idColumns) {
      $this->class = get_class($object);
      $idColumns   = (array) $idColumns;
      foreach ($idColumns as $idColumn) {
        if (!property_exists($this->class, $idColumn)) {
          throw new InvalidArgumentException('Collection ID Column must be a property of the collection object');
        }
      }
      $this->idColumns    = $idColumns;
      $this->table        = $object->getTable();
      $this->collection[] = $object;
    }
    /**
     * @param $ids
     *
     * @return Collection
     * @throws \InvalidArgumentException
     */
    public function getAll($ids) {
      $ids = (array) $ids;
      $q   = static::$staticDB->_select()->from($this->table);
      foreach ($this->idColumns as $idColumn) {
        if (!array_key_exists($idColumn, $ids)) {
          throw new InvalidArgumentException('Must provide all id column references! Missing ' . $idColumn);
        }
        $q->where($idColumn . '=', $ids[$idColumn]);
      }
      /** @var Base[] $collection */
      $collection = $q->fetch()->asClassLate($this->class);

      foreach ($collection as $object) {
        $this->collection[$object->id] = $object;
      }
      return $this;
    }
    /**
     * @param array $values
     */
    public function load(Array $values) {
      foreach ($values as $id => $props) {
        if (!isset($this->collection[$id])) {
          $this->collection[(int) $id] = new $this->class;
        }
        $this->collection[$id]->load($props);
      }
    }
    /**
     * @param array $values
     */
    public function save(Array $values) {
      foreach ($values as $id => $props) {
        $id = (int) $id;
        if (!isset($this->collection[$id])) {
          $this->collection[$id] = new $this->class;
        }
        $this->collection[$id]->save($props);
        if ($this->collection[$id]->id != $id) {
          $object                        = $this->collection[$id];
          $this->collection[$object->id] = $object;
          unset ($this->collection[$id]);
        }
      }
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return \ADV\App\DB\Base mixed Can return any type.
     */
    public function current() {
      if ($this->collection) {
        return $this->collection[$this->current];
      }
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next() {

      next($this->collection);
      $this->current = key($this->collection);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key() {
      return $this->current;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid() {
      return array_key_exists($this->current, $this->collection);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind() {
      reset($this->collection);
      $this->current = key($this->collection);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
      return (array_key_exists($offset, $this->collection));
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     *
     * @return Base Can return all value types.
     */
    public function offsetGet($offset) {
      return $this->collection[(int) $this->collection];
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     *
     * @throws \InvalidArgumentException
     * @return void
     */
    public function offsetSet($offset, $value) {
      if (!is_a($value, $this->class)) {
        throw new InvalidArgumentException('Value must be of type ' . $this->class . ' for this collection');
      }
      $this->collection[(int) $offset] = $value;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     *
     * @return void
     */
    public function offsetUnset($offset) {
      if ($this->collection[$offset]->delete()) {
        unset($this->collection[$offset]);
      }
      ;
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     *       The return value is cast to an integer.
     */
    public function count() {
      return count($this->collection);
    }
  }

  Collection::$staticDB = \ADV\Core\DB\DB::i();
