<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 9/12/11
	 * Time: 3:29 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class testt {
		public $test = 'teeesst';

		public function test($test) {
			echo $test;
		}
		public static function test2(testt $test) {
			echo $test->test;
		}
	}


echo '<pre>';
	$test = new testt;
try {
	$test->test('testing');
	$test->test2('test');
	$test::test2($test);
} catch (Exception $e) {
	var_dump($e);
}
E_RECOVERABLE_ERROR;
?>