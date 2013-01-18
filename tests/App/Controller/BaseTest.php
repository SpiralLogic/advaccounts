<?php
  namespace ADV\App\Controller;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-09-28 at 16:51:01.
   */
  class BaseTest extends \PHPUnit_Framework_TestCase
  {
    /** @var Base * */
    protected $object;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $session      = $this->getMockBuilder('ADV\\Core\\Session')->disableOriginalConstructor()->getMock();
      $user         = $this->getMockBuilder('ADV\\App\\User')->disableOriginalConstructor()->getMock();
      $this->object = new BaseAbstract($session, $user);
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }
    /**
     * @covers ADV\App\Controller\Base::setPage
     * @todo   Implement testSetPage().
     */
    public function testSetPage() {
      $page = $this->getMockBuilder('ADV\\App\\Page')->disableOriginalConstructor()->getMock();
      $this->object->setPage($page);
      $this->assertAttributeInstanceOf('ADV\\App\\Page', 'Page', $this->object);
    }
  }

  class BaseAbstract extends Base
  {
    protected function index() {
      // TODO: Implement index() method.
    }
    public function run($embed = false) {
      // TODO: Implement run() method.
    }
  }
