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

		var $label;
		var $link;

		function menu_item($label, $link)
		{
			$this->label = $label;
			$this->link = $link;
		}

	}

	class Menu
	{

		var $title;
		var $items = array();

		function menu($title)
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