<?php
  namespace ADV\Core;
  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-04-30 at 12:17:30.
   */
  class DatesTest extends \PHPUnit_Framework_TestCase
  {
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
      $company = $this->getMockBuilder('DB_Company')->disableOriginalConstructor()->getMock();
      $company->expects($this->any())->method('_get_current_fiscalyear')->will($this->returnValue([
                                                                                                  'begin' => '01/07/2012',
                                                                                                  'end'   => '30/06/2013',
                                                                                                  'closed'=> false
                                                                                                  ]));
      $session = $this->getMockBuilder('ADV\\Core\\Session')->disableOriginalConstructor()->getMock();
      $session->expects($this->any())->method('_setGlobal')->will($this->returnArgument(1));
      $session->expects($this->any())->method('_getGlobal')->will($this->returnArgument(1));

      $config = $this->getMock('ADV\\Core\\Config');
      $map    = array(
        ['date.separators', false, ['-', '/', '.', ' ']], //
        ['date.formats', false, ["m/d/Y", "d/m/Y", "Y/m/d"]], //
        ['date.ui_separator', false, 1], ['use_fiscalyear', false, true],
      );
      $config->expects($this->any())->method('_get')->will($this->returnValueMap($map));

      $user = $this->getMockBuilder('\\User')->disableOriginalConstructor()->getMock();
      $user->expects($this->any())->method('_date_format')->will($this->returnValue(1));
      $user->expects($this->any())->method('_date_display')->will($this->returnValue('d/m/Y'));
      $user->expects($this->any())->method('_date_sep')->will($this->returnValue(1));
      $user->expects($this->any())->method('_sticky_doc_date')->will($this->returnValue(false));

      $this->dates = new Dates($config, $user, $session, $company);
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    /**
     * @covers ADV\Core\Dates::date
     */
    public function testdate()
    {
      $class  = new \ReflectionClass('ADV\\Core\\Dates');
      $method = $class->getMethod('date');
      $method->setAccessible(true);
      $expected = '01/13/2011';
      $actual   = $method->invokeArgs($this->dates, [2011, 1, 13, 0]);
      $this->assertEquals($expected, $actual);
      $expected = '13/01/2011';
      $actual   = $method->invokeArgs($this->dates, [2011, 1, 13, 1]);
      $this->assertEquals($expected, $actual);
      $expected = '2011/01/13';
      $actual   = $method->invokeArgs($this->dates, [2011, 1, 13, 2]);
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::_isDate
     * @todo   Implement testisDate().
     */
    public function testisDate()
    {
      $date   = 'this is not a date';
      $result = $this->dates->_isDate($date, 0);
      $this->assertFalse($result, 'this is not a date');
      $date   = 'this is not a date';
      $result = $this->dates->_isDate($date, 0);
      $this->assertFalse($result, 'this is not a date');
      $date   = '12/20/2011';
      $result = $this->dates->_isDate($date, 0);
      $this->assertTrue($result, '12/20/2011 0');
      $result = $this->dates->_isDate($date, 1);
      $this->assertFalse($result, '12/20/2011 1');
      $date   = '20-12-2011';
      $result = $this->dates->_isDate($date, 1);
      $this->assertTrue($result, '20-12-2011 1');
      $result = $this->dates->_isDate($date, 0);
      $this->assertFalse($result, '20-12-2011 0');
      $date   = '2011.12.20';
      $result = $this->dates->_isDate($date, 2);
      $this->assertTrue($result, '2011.12.20 2');
      $result = $this->dates->_isDate($date, 0);
      $this->assertFalse($result, '2011.12.20 0');

      $date   = '130/13/11';
      $result = $this->dates->_isDate($date, 0);
      $this->assertFalse($result, '130/13/11 0');
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
     * @covers  ADV\Core\Dates::_newDocDate
     */
    public function test_newDocDate()
    {
      $today = date('d/m/Y');

      $date = $this->dates->_newDocDate();
      $this->assertEquals($today, $date);
    }
    /**
     * @covers ADV\Core\Dates::isDateInFiscalYear
     */
    public function test_isDateInFiscalYear()
    {
      $expected = true;
      $actual   = $this->dates->_isDateInFiscalYear('01/02/2013');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::beginFiscalYear
     * @todo   Implement testbeginFiscalYear().
     */
    public function testbeginFiscalYear()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::endFiscalYear
     * @todo   Implement testendFiscalYear().
     */
    public function testendFiscalYear()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::beginMonth
     * @todo   Implement testbeginMonth().
     */
    public function testbeginMonth()
    {
      // Remove the following lines when you implement this test.
      $date     = $this->dates->_beginMonth('04/03/2011');
      $expected = '01/03/2011';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_beginMonth('13/12/2011');
      $expected = '01/12/2011';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::endMonth
     * @todo   Implement testendMonth().
     */
    public function testendMonth()
    {
      $date     = $this->dates->_endMonth('04/04/2012');
      $expected = '30/04/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_endMonth('13/12/2012');
      $expected = '31/12/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_endMonth('2/2/2012');
      $expected = '29/02/2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::addDays
     * @todo   Implement testaddDays().
     */
    public function testaddDays()
    {
      $date     = $this->dates->_addDays('04/04/2012', 7);
      $expected = '11/04/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_addDays('25/04/2012', 7);
      $expected = '02/05/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_addDays('28/2/2012', 7);
      $expected = '06/03/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_addDays('28/2/2012', -7);
      $expected = '21/02/2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::addMonths
     * @todo   Implement testaddMonths().
     */
    public function testaddMonths()
    {
      // Remove the following lines when you implement this test.
      $date     = $this->dates->_addMonths('04/04/2012', 4);
      $expected = '04/08/2012';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_addMonths('25/09/2012', 4);
      $expected = '25/01/2013';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_addMonths('25/09/2012', -4);
      $expected = '25/05/2012';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\Dates::addYears
     * @todo   Implement testaddYears().
     */
    public function testaddYears()
    {
      $date     = $this->dates->_addYears('04/04/2012', 4);
      $expected = '04/04/2016';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_addYears('25/09/2012', 4);
      $expected = '25/09/2016';
      $this->assertEquals($expected, $date);
      $date     = $this->dates->_addYears('25/09/2012', -4);
      $expected = '25/09/2008';
      $this->assertEquals($expected, $date);
    }
    /**
     * @covers ADV\Core\$this->dates->_sqlToDate
     * @todo   Implement testsqlToDate().
     */
    public function testsqlToDate()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::dateToSql
     * @todo   Implement testdateToSql().
     */
    public function testdateToSql()
    {
      $expected = '2012-03-04';
      $actual   = $this->dates->_dateToSql('04/03/2012');
      $this->assertEquals($expected, $actual);
    }
    /**
     * @covers ADV\Core\Dates::isGreaterThan
     * @todo   Implement testisGreaterThan().
     */
    public function testisGreaterThan()
    {
      $date1  = '01/01/2012';
      $date2  = '01/10/2012';
      $actual = $this->dates->_isGreaterThan($date1, $date2);
      $this->assertEquals(false, $actual);
      $date1  = '01/01/2013';
      $date2  = '01/10/2010';
      $actual = $this->dates->_isGreaterThan($date1, $date2);
      $this->assertEquals(true, $actual);
    }
    /**
     * @covers ADV\Core\Dates::differenceBetween
     * @todo   Implement testdifferenceBetween().
     */
    public function testdifferenceBetween()
    {
      $date1  = '01/01/2012';
      $date2  = '15/01/2012';
      $actual = $this->dates->_differenceBetween($date1, $date2, 'w');
      $this->assertEquals(-2, $actual);
      $date1  = '15/01/2012';
      $date2  = '01/01/2012';
      $actual = $this->dates->_differenceBetween($date1, $date2, 'w');
      $this->assertEquals(2, $actual);
      $date1  = '02/01/2012';
      $date2  = '01/01/2012';
      $actual = $this->dates->_differenceBetween($date1, $date2, 'd');
      $this->assertEquals(1, $actual);
    }
    /**
     * @covers ADV\Core\Dates::explode
     * @todo   Implement testexplode().
     */
    public function testexplode()
    {
      $class  = new \ReflectionClass('ADV\\Core\\Dates');
      $method = $class->getMethod('_explode');
      $method->setAccessible(true);

      $actual = $method->invokeArgs($this->dates, ['03/04/2012']);
      $this->assertEquals([ '2012','04','03' ], $actual);
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
     * @covers ADV\Core\Dates::gregorianToJalai
     * @todo   Implement testgregorianToJalai().
     */
    public function testgregorianToJalai()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::jalaiToGregorian
     * @todo   Implement testjalaiToGregorian().
     */
    public function testjalaiToGregorian()
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
     * @covers ADV\Core\Dates::gregorianToIslamic
     * @todo   Implement testgregorianToIslamic().
     */
    public function testgregorianToIslamic()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Dates::islamicToGregorian
     * @todo   Implement testislamicToGregorian().
     */
    public function testislamicToGregorian()
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
