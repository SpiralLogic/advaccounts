<?php
  namespace Modules\Volusion;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-04-06 at 05:04:11.
   */
  class CustomersTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var Customers
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $this->object = new Customers;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }

    /**
     * @covers Modules\Volusion\Customers::get
     * @todo   Implement testGet().
     */
    public function testGet() {
      $customers = $this->object;
      $result = $customers->get();
      $this->assertTrue(is_bool($result));
      if ($result === TRUE) {
        $this->assertAttributeInternalType('array', 'customers', $this->object,'Customers were found so this should be an array');
        $this->assertAttributeCount(0, 'customers', $this->object,'This should have customers in it because returned true');

      }
      elseif ($result === FALSE) {
        $this->assertAttributeEquals(FALSE, 'customers', $this->object,'This should now be empty mate');
      }
    }

    /**
     * @covers Modules\Volusion\Customers::insertCustomers
     * @todo   Implement testInsertCustomers().
     */
    public function testInsertCustomers() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers Modules\Volusion\Customers::insertCustomer
     * @todo   Implement testInsertCustomer().
     */
    public function testInsertCustomer() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers Modules\Volusion\Customers::getXML
     * @todo   Implement testGetXML().
     */
    public function testGetXML() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }

    /**
     * @covers Modules\Volusion\Customers::insert
     * @todo   Implement testInsert().
     */
    public function testInsert() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete(
        'This test has not been implemented yet.'
      );
    }
  }
