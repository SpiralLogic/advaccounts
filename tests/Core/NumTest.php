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
      $user->expects($this->any())->method('_price_dec')->will($this->returnValue(2));
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
     * @covers ADV\Core\Num::_price_format
     * @todo   Implement testpriceFormat().
     */
    public function testpriceFormat()
    {
      $actual   = $this->object->priceFormat('25.563434');
      $expected = ('25.56');
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->priceFormat('25');
      $expected = ('25.00');
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->priceFormat(0);
      $expected = '0.00';
      $this->assertSame($expected, $actual);
    }
    /**
     * @covers ADV\Core\Num::_round
     * @todo   Implement testRound().
     */
    public function test_Round()
    {
      $actual   = $this->object->round('25.563434', 2);
      $expected = '25.56';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->round('25.56345', 4);
      $expected = '25.5634';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->round('25.565', 2);
      $expected = '25.56';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->round('25.575', 2);
      $expected = '25.58';
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers  ADV\Core\Num::_format
     * @depends test_Round
     */
    public function test_Format()
    {
      $actual   = $this->object->format(25.56, 4);
      $expected = '25.5600';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->format(25.534534, 4);
      $expected = '25.5345';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->format(25, 2);
      $expected = '25.00';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->format('25.563434', 2);
      $expected = ('25.56');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers  ADV\Core\Num::__priceDecimal
     * @depends test_Format
     */
    public function test_priceDecimal()
    {
      $actual   = $this->object->priceDecimal(25.56, 4);
      $expected = '25.5600';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->priceDecimal(25.534534, 4);
      $expected = '25.534534';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->priceDecimal(25, 2);
      $expected = '25.00';
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Num::__exrateFormat
     */
    public function test_exrateFormat()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Num::__percentFormat
     */
    public function test_percentFormat()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Num::__round_to_nearest_cents
     */
    public function test_toNearestCents()
    {
      $actual   = $this->object->toNearestCents(1111.125, 100);
      $expected = '1112';
      //$this->assertEquals($expected, $actual);
      $actual   = $this->object->toNearestCents(231.56, 10);
      $expected = '231.60';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->toNearestCents(231.1112, 1);
      $expected = '231.12';
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Num::_to_words
     * @todo   Implement testtoWords().
     */
    public function test_toWords()
    {
      // Remove the following lines when you implement this test.
      $actual   = $this->object->toWords(231);
      $expected = 'Two Hundred and Thirty-One';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->toWords(4249);
      $expected = 'Four Thousand Two Hundred and Fourty-Nine';
      $this->assertEquals($expected, $actual);
      $actual   = $this->object->toWords(4249.22);
      $expected = 'Four Thousand Two Hundred and Fourty-Nine';
      $this->assertEquals($expected, $actual);
    }
  }
