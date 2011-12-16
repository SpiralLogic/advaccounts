<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 16/12/11
	 * Time: 12:04 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Apps_Claire extends Application
	{
		function __construct() {
			parent::__construct("Claire", _($this->help_context = "&Claire"));
		}
	}
