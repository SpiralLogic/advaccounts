<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Test
	{
		public $one;
		public $two;
		public $three;
		public $four;
		public function __construct() {
		}
		public function test1($array) {
			$start = microtime(true);
			foreach ($array as $k => $v) {
				if (property_exists($this, $k)) {
					$this->$k = $v;
				}
			}
			return microtime(true) - $start;
		}
		public function test2($array) {
			$start = microtime(true);
			while($array, function($v, $k, $object) {
				if (property_exists($object, $k)) {
					$object->$k = $v;
				}
			}, $this);
			return microtime(true) - $start;
		}
	}

	echo '<pre>';
	$array = array('one' => 1, 'two' => 2, 'eight' => 8);
	$time = 0;
	for ($i = 0; $i < 10000; $i++) {
		$test1 = new Test();
		$time += $test1->test1($array);
		unset($test1);
	}
	echo getReadableTime($time);
	$time = 0;
	for ($i = 0; $i < 10000; $i++) {
		$test2 = new Test();
		$time += $test2->test2($array);
	}
	echo getReadableTime($time);
	function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if ($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if ($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret, 3, '.', '') . ' ' . $formats[$formatter];
		return $ret;
	}