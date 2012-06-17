<?php
  namespace ADV\Core;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-06-12 at 21:03:00.
   */
  class NumTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @var Num
     */
    protected $object;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
      $user      = $this->getMockBuilder('\\User')->disableOriginalConstructor()->getMock();
      $userprefs = $this->getMockBuilder('\\Userprefs')->disableOriginalConstructor()->getMock();
      $userprefs->expects($this->any())->method('price_dec')->will($this->returnValue(2));
      $user->expects($this->any())->method('_prefs')->will($this->returnValue($userprefs));
      $user->expects($this->any())->method('_tho_sep')->will($this->returnValue(','));
      $user->expects($this->any())->method('_dec_sep')->will($this->returnValue('.'));

      $this->object = new Num($user);
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    /**
     * @covers ADV\Core\Num::price_format
     * @todo   Implement testPrice_format().
     */
    public function testPrice_format()
    {
      $actual   = $this->object->_price_format('25.563434');
      $expected = ('25.56');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Num::round
     * @todo   Implement testRound().
     */
    public function testRound()
    {

      $actual   = $this->object->_round('25.563434', 2);
      $expected = '25.56';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_round('25.56345', 4);
      $expected = '25.5634';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_round('25.565', 2);
      $expected = '25.56';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_round('25.575', 2);
      $expected = '25.58';
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers  ADV\Core\Num::format
     * @depends testRound
     */
    public function testFormat()
    {
      $actual   = $this->object->_format(25.56, 4);
      $expected = '25.5600';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_format(25.534534, 4);
      $expected = '25.5345';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_format(25, 2);
      $expected = '25.00';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_format('25.563434', 2);
      $expected = ('25.56');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers  ADV\Core\Num::price_decimal
     * @depends testFormat
     */
    public function testPrice_decimal()
    {
      $actual   = $this->object->_price_decimal(25.56, 4);
      $expected = '25.5600';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_price_decimal(25.534534, 4);
      $expected = '25.534534';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_price_decimal(25, 2);
      $expected = '25.00';
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Num::exrate_format
     * @todo   Implement testExrate_format().
     */
    public function testExrate_format()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Num::percent_format
     * @todo   Implement testPercent_format().
     */
    public function testPercent_format()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Num::_round_to_nearest_cents
     */
    public function test_round_to_nearest_cents()
    {
      $actual   = $this->object->_round_to_nearest_cents(1111.125, 100);
      $expected = '1112';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_round_to_nearest_cents(231.56, 10);
      $expected = '231.60';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_round_to_nearest_cents(231.1112, 1);
      $expected = '231.12';
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Num::to_words
     * @todo   Implement testTo_words().
     */
    public function testTo_words()
    {
      // Remove the following lines when you implement this test.
      $actual   = $this->object->_to_words(231);
      $expected = 'Two Hundred and Thirty-One';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_to_words(4249);
      $expected = 'Four Thousand Two Hundred and Fourty-Nine';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->_to_words(4249.22);
      $expected = 'Four Thousand Two Hundred and Fourty-Nine';
      $this->assertEquals($expected, $actual);
    }
  }
