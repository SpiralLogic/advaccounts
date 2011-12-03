<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 24/07/11
	 * Time: 1:16 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class DatePicker
	{
		protected static $options = array('rebind' => true);
		protected $name;
		protected static $_count = 0;

		static function add($name, $options = array()) {
			$options = array_merge(static::$options, $options);
			$js = '$("#' . $name . '").one("focus",function() { $(this).datepicker(' . JS::arrayToOptions($options) . ').focus() }); ';
			if ($options['rebind']) {
				JS::addLive($js);
			} else {
				JS::onload($js);
			}
			self::$_count++;
		}
	}