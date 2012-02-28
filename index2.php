<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 25/02/12
	 * Time: 1:52 PM
	 * To change this template use File | Settings | File Templates.
	 */
	echo 'PHP_ROUND_HALF_DOWN<BR>';
	echo round(544.35 / 10, 2, PHP_ROUND_HALF_DOWN);
	echo '<BR>PHP_ROUND_HALF_EVEN<BR>';
	echo round(544.35 / 10, 2, PHP_ROUND_HALF_EVEN);
	echo '<BR>PHP_ROUND_HALF_ODD<BR>';
	echo round(544.35 / 10, 2, PHP_ROUND_HALF_ODD);
	echo '<BR>PHP_ROUND_HALF_UP<BR>';
	echo round(544.35 / 10, 2, PHP_ROUND_HALF_UP);
