<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:07 PM
 * To change this template use File | Settings | File Templates.
 */
abstract class Contact extends DB_abstract
{


	// Current status of class, status as current state, process status error occurred, message for current state and if related variable if it applies
	protected $_status = null;
	public $email = "";
	public $inactive = 0;
    public $notes = '';


}
