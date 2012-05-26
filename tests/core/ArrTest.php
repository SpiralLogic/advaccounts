<?php

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-04-06 at 04:05:58.
   */
  class ArrTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @var Arr
     */
    protected $object;
    /**
     * @covers ADV\Core\Arr::insert
     * @return void
     */
    public function testInsert()
    {
      $part_numbers    = array('84256', '84257', '84258');
      $expected_result = array('84256', '84257', '86732', '84258');
      Arr::insert($part_numbers, 2, '86732');
      $this->assertEquals($expected_result, $part_numbers);
    }
    /**
     * @covers ADV\Core\Arr::remove
     * @todo   Implement testRemove().
     */
    public function testRemove()
    {
      $people   = array("Jack", "Jill");
      $expected = array("Jack");
      Arr::remove($people, 1);
      $this->assertEquals($expected, $people);
      $people = array("Humpty", "Jack", "Jill");
      Arr::remove($people, 1, 2);
      $expected = array("Humpty");
      $this->assertEquals($expected, $people);
    }
    /**
     * @covers ADV\Core\Arr::get
     * @todo   Implement testGet().
     */
    public function testGet()
    {
      $people   = array("test1" => "Jack", "test2" => "Jill");
      $expected = "Jill";
      $ouput    = Arr::get($people, 'test2');
      $this->assertEquals($expected, $ouput);
      $ouput = Arr::get($people, 'test2');
      $this->assertEquals($expected, $ouput);
      $ouput    = Arr::get($people, 'test3', "Humpty");
      $expected = "Humpty";
      $this->assertEquals($expected, $ouput);
    }
    /**
     * @covers ADV\Core\Arr::substitute
     * @todo   Implement testSubstitute().
     */
    public function testSubstitute()
    {
      $part_numbers    = array('84256', '84257', '84258');
      $expected_result = array('84256', '86732', '84258');
      \ADV\Core\Arr::substitute($part_numbers, 1, 1, '86732');
      $this->assertEquals($expected_result, $part_numbers);
    }
    /**
     * @covers ADV\Core\Arr::append
     * @todo   Implement testAppend().
     */
    public function testAppend()
    {
      // Remove the following lines when you implement this test.
      $initial   = ['one', 'two', 'three'];
      $to_append = ['four'];
      Arr::append($initial, $to_append);
      $this->assertInternalType('array', $initial);
      $initial   = ['one', 'two', 'three'];
      $to_append = ['four', 'five'];
      Arr::append($initial, $to_append);
      $expected = ['one', 'two', 'three', 'four', 'five'];
      $this->assertEquals($expected, $initial);
      $initial   = ['one', 'two', 'three'];
      $to_append = 'four';
      Arr::append($initial, $to_append);
      $expected = ['one', 'two', 'three', 'four'];
      $this->assertEquals($expected, $initial);
      $initial   = ['one' => 1, 'two' => 2, 'three' => 3];
      $to_append = ['four' => 4, 'five' => 5];
      $expected  = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5];
      Arr::append($initial, $to_append);
      $this->assertEquals($expected, $initial);
    }
    /**
     * @covers ADV\Core\Arr::search_value
     * @todo   Implement testSearch_value().
     */
    public function testSearch_value()
    {
      // Remove the following lines when you implement this test.
      $array  = [1=> 'one', 2=> 'two', 'three'=> 3, 'four'=> '4', 'five', 6=> array('six'=> 6, 'seven'=> 'seven')];
      $actual = Arr::search_value(3, $array);
      $this->assertSame(3, $actual);
      $expected = $array[6];
      $actual   = Arr::search_value(6, $array, 'six');
      $this->assertSame($expected, $actual);
    }
    /**
     * @covers ADV\Core\Arr::search_key
     * @todo   Implement testSearch_key().
     */
    public function testSearch_key()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Arr::natsort
     * @todo   Implement testNatsort().
     */
    public function testNatsort()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
  }
