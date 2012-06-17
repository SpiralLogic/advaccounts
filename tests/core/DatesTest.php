<?php
  namespace ADV\Core;
  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-04-30 at 12:17:30.
   */
  class DatesTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Dates
     */
    protected $dates;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
      $session = $this->getMockBuilder('ADV\\Core\\Session')->disableOriginalConstructor()->getMock();
      $session->expects($this->any())->method('_setGlobal')->will($this->returnArgument(1));
      $session->expects($this->any())->method('_getGlobal')->will($this->returnArgument(1));

      $config = $this->getMock('ADV\\Core\\Config');
      $map    = array(
        ['date.separators', false, ['-', '/', '.',' ']], //
        ['date.formats', false, ["m/d/Y", "d/m/Y", "Y/m/d"]], //
        ['date.ui_separator', false, 1]
      );
      $config->expects($this->any())->method('_get')->will($this->returnValueMap($map));

      $user = $this->getMockBuilder('User')->disableOriginalConstructor()->getMock();
      $user->expects($this->any())->method('_date_format')->will($this->returnValue(1));
      $user->expects($this->any())->method('_sticky_doc_date')->will($this->returnValue(false));

      $this->dates = new Dates($config, $user, $session);
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    /**
     * @covers ADV\Core\Dates::___date
     * @todo   Implement test__date().
     */
    public function testdate()
    {
      $expected = '01/13/2011';
      $actual   = $this->dates->___date(2011, 1, 13, 0);
      $this->assertEquals($expected, $actual);
      $expected = '13/01/2011';
      $actual   = $this->dates->___date(2011, 1, 13, 1);
      $this->assertEquals($expected, $actual);
      $expected = '2011/01/13';
      $actual   = $this->dates->___date(2011, 1, 13, 2);
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::_is_date
     * @todo   Implement testIs_date().
     */
    public function testIs_date()
    {
      $date   = 'this is not a date';
      $result = $this->dates->_is_date($date, 0);
      $this->assertFalse($result, 'this is not a date');
      $date   = 'this is not a date';
      $result = $this->dates->_is_date($date, 0);
      $this->assertFalse($result, 'this is not a date');
      $date   = '12/20/2011';
      $result = $this->dates->_is_date($date, 0);
      $this->assertTrue($result, '12/20/2011 0');
      $result = $this->dates->_is_date($date, 1);
      $this->assertFalse($result, '12/20/2011 1');
      $date   = '20-12-2011';
      $result = $this->dates->_is_date($date, 1);
      $this->assertTrue($result, '20-12-2011 1');
      $result = $this->dates->_is_date($date, 0);
      $this->assertFalse($result, '20-12-2011 0');
      $date   = '2011.12.20';
      $result = $this->dates->_is_date($date, 2);
      $this->assertTrue($result, '2011.12.20 2');
      $result = $this->dates->_is_date($date, 0);
      $this->assertFalse($result, '2011.12.20 0');

      $date   = '130/13/11';
      $result = $this->dates->_is_date($date, 0);
      $this->assertFalse($result, '130/13/11 0');
    }
    /**
     * @covers ADV\Core\Dates::_today
     * @todo   Implement testToday().
     */
    public function test_Today()
    {
      $today    = $this->dates->_today();
      $expected = date('d/m/Y');
       $this->assertEquals($expected, $today);
      return $today;
    }
    /**
     * @covers ADV\Core\Dates::_now
     * @todo   Implement testNow().
     */
    public function testNow()
    {
      $this->assertEquals(date("H:i"), $this->dates->_now());
    }
    /**
     * @covers ADV\Core\Dates::_new_doc_date
     * @depends test_Today
     */
    public function testNew_doc_date($today)
    {


      $date = $this->dates->_new_doc_date();
      $this->assertEquals($today, $date);
    }
    /**
     * @covers ADV\Core\Dates::is_date_in_fiscalyear
     * @todo   Implement testIs_date_in_fiscalyear().
     */
    public function testIs_date_in_fiscalyear()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::begin_fiscalyear
     * @todo   Implement testBegin_fiscalyear().
     */
    public function testBegin_fiscalyear()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::end_fiscalyear
     * @todo   Implement testEnd_fiscalyear().
     */
    public function testEnd_fiscalyear()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::begin_month
     * @todo   Implement testBegin_month().
     */
    public function testBegin_month()
    {
      // Remove the following lines when you implement this test.
      $date     = $this->dates->_begin_month('04/03/2011');
      $expected = '01/03/2011';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_begin_month('13/12/2011');
      $expected = '01/12/2011';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::end_month
     * @todo   Implement testEnd_month().
     */
    public function testEnd_month()
    {
      $date     = $this->dates->_end_month('04/04/2012');
      $expected = '30/04/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_end_month('13/12/2012');
      $expected = '31/12/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_end_month('2/2/2012');
      $expected = '29/02/2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::add_days
     * @todo   Implement testAdd_days().
     */
    public function testAdd_days()
    {
      $date     = $this->dates->_add_days('04/04/2012', 7);
      $expected = '11/04/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_add_days('25/04/2012', 7);
      $expected = '02/05/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_add_days('28/2/2012', 7);
      $expected = '06/03/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_add_days('28/2/2012', -7);
      $expected = '21/02/2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::add_months
     * @todo   Implement testAdd_months().
     */
    public function testAdd_months()
    {
      // Remove the following lines when you implement this test.
      $date     = $this->dates->_add_months('04/04/2012', 4);
      $expected = '04/08/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_add_months('25/09/2012', 4);
      $expected = '25/01/2013';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_add_months('25/09/2012', -4);
      $expected = '25/05/2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::add_years
     * @todo   Implement testAdd_years().
     */
    public function testAdd_years()
    {
      $date     = $this->dates->_add_years('04/04/2012', 4);
      $expected = '04/04/2016';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_add_years('25/09/2012', 4);
      $expected = '25/09/2016';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_add_years('25/09/2012', -4);
      $expected = '25/09/2008';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\$this->dates->_sql2date
     * @todo   Implement testSql2date().
     */
    public function testSql2date()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::date2sql
     * @todo   Implement testDate2sql().
     */
    public function testDate2sql()
    {
      $expected = '2012-03-04';
      $actual   = $this->dates->_date2sql('04/03/2012');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::date1_greater_date2
     * @todo   Implement testDate1_greater_date2().
     */
    public function testDate1_greater_date2()
    {
      $date1  = '01/01/2012';
      $date2  = '01/10/2012';
      $actual = $this->dates->_date1_greater_date2($date1, $date2);
      $this->assertEquals(false, $actual);
      $date1  = '01/01/2013';
      $date2  = '01/10/2010';
      $actual = $this->dates->_date1_greater_date2($date1, $date2);
      $this->assertEquals(true, $actual);
    }
    /**
     * @covers ADV\Core\Dates::date_diff2
     * @todo   Implement testDate_diff2().
     */
    public function testDate_diff2()
    {
      $date2  = '01/01/2012';
      $date1  = '15/01/2012';
      $actual = $this->dates->_date_diff2($date1, $date2, 'w');
      $this->assertEquals(2, $actual);
    }
    /**
     * @covers ADV\Core\Dates::explode_date_to_dmy
     * @todo   Implement testExplode_date_to_dmy().
     */
    public function testExplode_date_to_dmy()
    {
      $actual = $this->dates->_explode_date_to_dmy('03/04/2012');
      $this->assertEquals(['03', '04', '2012'], $actual);
    }
    /**
     * @covers ADV\Core\Dates::div
     * @todo   Implement testDiv().
     */
    public function testDiv()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::gregorian_to_jalali
     * @todo   Implement testGregorian_to_jalali().
     */
    public function testGregorian_to_jalali()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::jalali_to_gregorian
     * @todo   Implement testJalali_to_gregorian().
     */
    public function testJalali_to_gregorian()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::months
     * @todo   Implement testMonths().
     */
    public function testMonths()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::gregorian_to_islamic
     * @todo   Implement testGregorian_to_islamic().
     */
    public function testGregorian_to_islamic()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::islamic_to_gregorian
     * @todo   Implement testIslamic_to_gregorian().
     */
    public function testIslamic_to_gregorian()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::getReadableTime
     * @todo   Implement testGetReadableTime().
     */
    public function testGetReadableTime()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
  }
