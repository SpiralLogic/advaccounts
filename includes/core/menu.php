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
		public $label;
		public $link;

		function menu_item($label, $link)
		{
			$this->label = $label;
			$this->link  = $link;
		}
	}

	class Menu
	{
		public $title;
		public $items = array();

		function __construct($title)
		{
			$this->title = $title;
			$this->items;
		}

		function add_item($label, $link)
		{
			$item = new menu_item($label, $link);
			array_push($this->items, $item);
			return $item;
		}
	}