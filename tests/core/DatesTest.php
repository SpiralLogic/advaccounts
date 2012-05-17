<?php

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-04-30 at 12:17:30.
   */
  class DatesTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Dates
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $this->object = new Dates;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }

    /**
     * @covers ADV\Core\Dates::__date
     * @todo   Implement test__date().
     */
    public function test__date() {
      $sep = Config::get('date.ui_separator');
      $expected = '01' . $sep . '13' . $sep . '2011';
      $actual = Dates::__date(2011, 1, 13, 0);
      $this->assertEquals($expected, $actual);

      $expected = '13' . $sep . '01' . $sep . '2011';
      $actual = Dates::__date(2011, 1, 13, 1);
      $this->assertEquals($expected, $actual);

      $expected = '2011' . $sep . '01' . $sep . '13';
      $actual = Dates::__date(2011, 1, 13, 2);
      $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ADV\Core\Dates::is_date
     * @todo   Implement testIs_date().
     */
    public function testIs_date() {
      $date = '12/20/2011';
      $result = Dates::is_date($date, 0);
      $this->assertTrue($result);
      $result = Dates::is_date($date, 1);
      $this->assertFalse($result);

      $date = '20-12-2011';
      $result = Dates::is_date($date, 1);
      $this->assertTrue($result);
      $result = Dates::is_date($date, 0);
      $this->assertFalse($result);

      $date = '2011.12.20';
      $result = Dates::is_date($date, 2);
      $this->assertTrue($result);
      $result = Dates::is_date($date, 0);
      $this->assertFalse($result);

      $date = 'this is not a date';
      $result = Dates::is_date($date, 0);
      $this->assertFalse($result);
      $date = '130/13/11';
      $result = Dates::is_date($date, 0);
      $this->assertFalse($result);
    }

    /**
     * @covers ADV\Core\Dates::today
     * @todo   Implement testToday().
     */
    public function testToday() {
      $today = Dates::today();
      $expected = date('d/m/Y');
      $this->assertEquals($expected, $today);
    }

    /**
     * @covers ADV\Core\Dates::now
     * @todo   Implement testNow().
     */
    public function testNow() {
      $date = Dates::now();
      $this->assertEquals(date("H:i"), $date);
      $this->logicalOr();
      $this->assertEquals(date("H:i"), $date);
    }

    /**
     * @covers ADV\Core\Dates::new_doc_date
     * @todo   Implement testNew_doc_date().
     */
    public function testNew_doc_date() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::is_date_in_fiscalyear
     * @todo   Implement testIs_date_in_fiscalyear().
     */
    public function testIs_date_in_fiscalyear() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::begin_fiscalyear
     * @todo   Implement testBegin_fiscalyear().
     */
    public function testBegin_fiscalyear() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::end_fiscalyear
     * @todo   Implement testEnd_fiscalyear().
     */
    public function testEnd_fiscalyear() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::begin_month
     * @todo   Implement testBegin_month().
     */
    public function testBegin_month() {
      // Remove the following lines when you implement this test.
      $date = \Dates::begin_month('04/03/2011');
      $expected = '01/04/2011';
      $this->assertEquals($expected, $date);
      $date = \Dates::begin_month('12/13/2011');
      $expected = '01/12/2011';
      $this->assertEquals($expected, $date);
    }

    /**
     * @covers ADV\Core\Dates::end_month
     * @todo   Implement testEnd_month().
     */
    public function testEnd_month() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::add_days
     * @todo   Implement testAdd_days().
     */
    public function testAdd_days() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::add_months
     * @todo   Implement testAdd_months().
     */
    public function testAdd_months() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::add_years
     * @todo   Implement testAdd_years().
     */
    public function testAdd_years() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::sql2date
     * @todo   Implement testSql2date().
     */
    public function testSql2date() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::date2sql
     * @todo   Implement testDate2sql().
     */
    public function testDate2sql() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::date1_greater_date2
     * @todo   Implement testDate1_greater_date2().
     */
    public function testDate1_greater_date2() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::date_diff2
     * @todo   Implement testDate_diff2().
     */
    public function testDate_diff2() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::explode_date_to_dmy
     * @todo   Implement testExplode_date_to_dmy().
     */
    public function testExplode_date_to_dmy() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::div
     * @todo   Implement testDiv().
     */
    public function testDiv() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::gregorian_to_jalali
     * @todo   Implement testGregorian_to_jalali().
     */
    public function testGregorian_to_jalali() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::jalali_to_gregorian
     * @todo   Implement testJalali_to_gregorian().
     */
    public function testJalali_to_gregorian() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::months
     * @todo   Implement testMonths().
     */
    public function testMonths() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::gregorian_to_islamic
     * @todo   Implement testGregorian_to_islamic().
     */
    public function testGregorian_to_islamic() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::islamic_to_gregorian
     * @todo   Implement testIslamic_to_gregorian().
     */
    public function testIslamic_to_gregorian() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers ADV\Core\Dates::getReadableTime
     * @todo   Implement testGetReadableTime().
     */
    public function testGetReadableTime() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }
  }
