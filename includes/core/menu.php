<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 2/11/11
	 * Time: 10:38 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class menu_item
	{
		/**
		 * @var
		 */
		public $label;
		/**
		 * @var
		 */
		public $link;

		/**
		 * @param $label
		 * @param $link
		 */
		function menu_item($label, $link) {
			$this->label = $label;
			$this->link = $link;
		}
	}

	/**
	 *
	 */
	class Menu
	{
		/**
		 * @var
		 */
		public $title;
		/**
		 * @var array
		 */
		public $items = array();

		/**
		 * @param $title
		 */
		function __construct($title) {
			$this->title = $title;
			$this->items;
		}

		/**
		 * @param $label
		 * @param $link
		 *
		 * @return menu_item|mixed
		 */
		function add_item($label, $link) {
			$item = new menu_item($label, $link);
			array_push($this->items, $item);
			return $item;
		}
	}