<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Maidenii
 * Date: 16/03/12
 * Time: 12:03 PM
 * To change this template use File | Settings | File Templates.
 */
	function test() {

		return $test = [1,2,3,4];
	}
function test2() {
	return 3;
}
print_r(test().'<br>');
print_r(test()[1].'<br>');
print_r(test()[test2()].'<br>');
$test=[1=>2,3=>4];


