<?php
  namespace ADV\Core;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-09-28 at 22:08:05.
   */
  class StatusTest extends \PHPUnit_Framework_TestCase
  {

    /**
     * @var Status
     */
    protected $object;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $this->object = new Status;
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }
    /**
     * @covers ADV\Core\Status::set
     */
    public function testSet() {
      //make sure errror is set
      $this->object->set(Status::ERROR, 'error set');
      $this->assertAttributeCount(1, 'errors', $this->object);
      $expected = [['status' => Status::ERROR, 'message' => 'error set']];
      $this->assertAttributeEquals($expected, 'status', $this->object);
      $this->assertAttributeEquals($expected, 'status', $this->object);
      $this->object->set(Status::INFO, 'info set', 'var2');
      //make sure errror is set in errors and statues
      $this->assertAttributeCount(1, 'errors', $this->object);
      $this->assertAttributeEquals($expected, 'errors', $this->object);
      $expected[] = ['status' => Status::INFO, 'message' => 'info set', 'var' => 'var2'];
      //make sure status has both set
      $this->assertAttributeEquals($expected, 'status', $this->object);
      $this->assertAttributeCount(2, 'status', $this->object);
      $this->assertAttributeEquals($expected, 'status', $this->object);
    }
    /**
     * @covers  ADV\Core\Status::get
     * @depends testSet
     */
    public function testGet() {
      // Remove the following lines when you implement this test.
      $status = $this->object->get();
      $this->assertSame([], $status);
      $this->object->set(Status::INFO, 'info set', 'var');
      $status = $this->object->get();
      $this->assertEquals(['status' => Status::INFO, 'message' => 'info set', 'var' => 'var'], $status);
      $this->object->set(Status::ERROR, 'error set', 'var2');
      $status = $this->object->get();
      $this->assertEquals(['status' => Status::ERROR, 'message' => 'error set', 'var' => 'var2'], $status);
      $this->object->set(Status::INFO, 'info set', 'var3');
      $status = $this->object->get();
      $this->assertEquals(['status' => Status::ERROR, 'message' => 'error set', 'var' => 'var2'], $status);
    }
    /**
     * @covers         ADV\Core\Status::append
     * @depends        testGet
     * */
    public function testAppend() {
      $this->object->set(Status::ERROR, 'error set', 'var2');
      $status = $this->object->get();
      $this->object->set(Status::INFO, 'info set', 'var');
      $this->object->append($status);
      $this->assertAttributeEquals([$status, ['status' => Status::INFO, 'message' => 'info set', 'var' => 'var'], $status], 'status', $this->object);
    }
    /**
     * @covers ADV\Core\Status::hasError
     * @todo   Implement testHasError().
     */
    public function testHasError() {
      $status = $this->object->hasError();
      $this->assertFalse($status);
      $this->object->set(Status::INFO, 'info set', 'var');
      $this->assertFalse($status);
      $this->object->set(Status::ERROR, 'error set', 'var2');
      $this->assertFalse($status);
    }
    /**
     * @covers ADV\Core\Status::getAll
     * @todo   Implement testGetAll().
     */
    public function testGetAll() {
    }
    /**
     * @covers ADV\Core\Status::__toString
     * @todo   Implement test__toString().
     */
    public function test__toString() {
      $this->object->set(Status::ERROR, 'error set', 'var2');
      $this->assertEquals(
        'Error: error set',
        (string) $this->object
      );
    }
  }
