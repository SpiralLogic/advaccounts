<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 10/10/11
	 * Time: 4:15 PM
	 * To change this template use File | Settings | File Templates.
	 */
	require_once 'bootstrap.php';

	if (AJAX_REFERRER) {
		HTML::script(array('content' => 'console.log("test");'), false);
		exit();
	}

	page("test");

	JS::footerFile('/js/signcalc.js');
	end_page(false, false, true);
