<?php
	namespace Modules\Volusion; /**
	 *
	 */ class Orders implements \Iterator, \Countable
	{
		protected $data;
		protected $current = -1;
		public $details;
		function __construct() {
			if (isset($_SESSION['weborders']) && $_SESSION['weborders'] > 0) {
				$this->data = $_SESSION['weborders'];
			}
			else {
				$this->get();
			}
			$this->next();
		}
		function getXML() {
			$apiuser = \Config::get('webstore.apiuser');
			$apikey = \Config::get('webstore.apikey');
			$url = \Config::get('webstore.apiurl');
			$url .= "Login=" . $apiuser;
			$url .= '&EncryptedPassword=' . $apikey;
			$url .= '&EDI_Name=Generic\Orders';
			$url .= '&SELECT_Columns=*';
			return file_get_contents($url);
		}
		function store() {
			$_SESSION['weborders'] = $this->data;
		}
		function get() {
			$XML = $this->getXML();
			if (!$XML) {
				return false;
			}
			$this->data = $_SESSION['weborders'] = \XMLParser::XMLtoArray($XML);
		}
		function exists() {
			$current=  $this->current();
			$results = \DB::select('OrderID')->from('WebOrders')->where('OrderID=',$current['OrderID'])->fetch()->all();
			return (count($results)>0);
		}
		function next() {
			$this->current++;
			if (isset($this->data[$this->current]['OrderDetails'])) {
				$this->details = new OrderDetails($this->data[$this->current]['OrderDetails']);
				unset($this->data[$this->current]['OrderDetails']);
			}
		}
		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the current element
		 *
		 * @link http://php.net/manual/en/iterator.current.php
		 * @return mixed Can return any type.
		 */
		public function current() {
			return $this->data[$this->current];
		}
		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Return the key of the current element
		 *
		 * @link http://php.net/manual/en/iterator.key.php
		 * @return scalar scalar on success, integer
		 * 0 on failure.
		 */
		public function key() {
			return $this->data[$this->current]['OrderID'];
		}
		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Checks if current position is valid
		 *
		 * @link http://php.net/manual/en/iterator.valid.php
		 * @return boolean The return value will be casted to boolean and then evaluated.
		 *			 Returns true on success or false on failure.
		 */
		public function valid() {
			return $this->current < count($this->data);
		}
		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Rewind the Iterator to the first element
		 *
		 * @link http://php.net/manual/en/iterator.rewind.php
		 * @return void Any returned value is ignored.
		 */
		public function rewind() {
			$this->current = -1;
			$this->next();
		}
		/**
		 * (PHP 5 &gt;= 5.1.0)<br/>
		 * Count elements of an object
		 *
		 * @link http://php.net/manual/en/countable.count.php
		 * @return int The custom count as an integer.
		 * </p>
		 * <p>
		 *			 The return value is cast to an integer.
		 */
		public function count() {
			return count($this->data);
		}
	}

	class OrderDetails extends Orders implements \Iterator, \Countable
	{
		/**
		 * @var OrderOptions
		 */
		public $options;
		function __construct($data) {
			if (is_array($data)) {
				$this->data = (!is_array(reset($data))) ? array($data) : $data;
			}
			$this->next();
		}
		function next() {
			$this->current++;
			if (isset($this->data[$this->current]['OrderDetails_Options'])) {
				$options = $this->data[$this->current]['OrderDetails_Options'];
				$this->options = new OrderOptions($options);
				unset($this->data[$this->current]['OrderDetails_Options']);
			}
			else {
				$this->options = null;
			}
		}
		function current() {
			return $this->data[$this->current];
		}
		function key() {
			return $this->data[$this->current]['OrderDetailID'];
		}
	}

	class OrderOptions extends OrderDetails
	{
		function next() {
			$this->current++;
		}
		function current() {
			return $this->data[$this->current];
		}
		function key() {
			return $this->data[$this->current]['OrderDetailID'];
		}
	}

	;
