<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 6:22 PM
 * To change this template use File | Settings | File Templates.
 */


class App_Items extends Application
{

	function __construct()
	{
		$this->direct = 'items/items.php';
		parent::__construct("items", _($this->help_context = "Items"));

	}
}