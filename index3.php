<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	include ('bootstrap.php');
		$data = Barcode::create(array('code' =>
		""));
		$image = base64_encode($data);
		echo '<IMG SRC="data:image/gif;base64,
		' . $image . '">' ;



?>