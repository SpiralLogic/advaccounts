<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 24/07/11
 * Time: 1:16 PM
 * To change this template use File | Settings | File Templates.
 */

  class DatePicker {

	 protected $options;
	 protected $name;
	 protected static $_count = 0;

	 static function add($name, $options = array()) {
		$js = '$("#' . $name . '").one("click",function() { $(this).datepicker(' . JS::arrayToOptions($options) . ').focus() }); ';
		JS::addLive($js);
		self::$_count++;
	 }
  }