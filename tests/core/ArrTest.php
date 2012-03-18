<?php

	include_once('PHPUnit/Autoload.php');
	/**
	 * Test class for Arr.
	 * Generated by PHPUnit on 2012-02-07 at 20:49:20.
	 */
	class ArrTest extends PHPUnit_Framework_TestCase
	{
		/**
		 * @var Arr
		 */
		protected $object;
		/**
		 * Sets up the fixture, for example, opens a network connection.
		 * This method is called before a test is executed.
		 * @return void
		 */
		protected function setUp() {
			$this->object = new Arr;
		}
		/**
		 * Tears down the fixture, for example, closes a network connection.
		 * This method is called after a test is executed.
		 * @return void
		 */
		protected function tearDown() {
		}
		/**
		 * @covers Arr::insert
		 * @todo   Implement testInsert().
		 * @return void
		 */
		public function testInsert() {
			$people = array("Jack", "Jill");
			$expected = array("Humpty", "Jack", "Jill");
			$output = Arr::insert($people, 0, "Humpty");
			$this->assertEquals(true, $output);
			$this->assertEquals($expected, $people);
		}
		/**
		 * @covers Arr::remove
		 * @todo   Implement testRemove().
		 * @return void
		 */
		public function testRemove() {
			$people = array("Jack", "Jill");
			$expected = array("Jack");
			Arr::remove($people, 1);
			$this->assertEquals($expected, $people);
			$people = array("Humpty", "Jack", "Jill");
			Arr::remove($people, 1, 2);
			$expected = array("Humpty");
			$this->assertEquals($expected, $people);
		}
		/**
		 * @covers Arr::get
		 * @return void
		 */
		public function testGet() {
			$people = array("test1" => "Jack", "test2" => "Jill");
			$expected = "Jill";
			$ouput = Arr::get($people, 'test2');
			$this->assertEquals($expected, $ouput);
			$ouput = Arr::get($people, 'test2');
			$this->assertEquals($expected, $ouput);
			$ouput = Arr::get($people, 'test3', "Humpty");
			$expected = "Humpty";
			$this->assertEquals($expected, $ouput);
		}
		/**
		 * @covers Arr::substitute
		 * @return void
		 */
		public function testSubstitute() {
			$people = array("Humpty", "Jill");
			$expected = array("Jack", "Jill");
			$output = Arr::substitute($people, 0, 1, "Jack");
			$this->assertEquals($people, $expected);
			$this->assertTrue($output);
		}
		/**
		 * @covers Arr::append
		 * @return void
		 */
		public function testAppend() {
			// Remove the following lines when you implement this test.
			$this->markTestIncomplete('This test has not been implemented yet.');
		}
		/**
		 * @covers Arr::search_value
		 * @return void
		 */
		public function testSearch_value() {
			// Remove the following lines when you implement this test.
			$this->markTestIncomplete('This test has not been implemented yet.');
		}
		/**
		 * @covers Arr::search_key
		 * @return void
		 */
		public function testSearch_key() {
			// Remove the following lines when you implement this test.
			$this->markTestIncomplete('This test has not been implemented yet.');
		}
		/**
		 * @covers Arr::natsort
		 * @return void
		 */
		public function testNatsort() {
			// Remove the following lines when you implement this test.
			$this->markTestIncomplete('This test has not been implemented yet.');
		}
	}

?>
