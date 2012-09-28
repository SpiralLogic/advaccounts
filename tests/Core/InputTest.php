<?php
  namespace ADV\Core\Input;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-05-17 at 14:37:31.
   */
  class InputTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Input
     */
    protected $object;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $_GET     = [];
      $_POST    = [];
      $_REQUEST = [];
      $_SESSION = [];

      $this->object = new Input();
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }
    /**
     * @covers ADV\Core\Input\Input::__post
     */
    public function testPost() {
      $_POST['test0'] = 'wawa';
      $expected       = 'wawa';
      $actual         = $this->object->post('test0');
      $this->assertSame($expected, $actual);
      $_POST['test0'] = '0';
      $expected       = '0';
      $actual         = $this->object->post('test0');
      $this->assertSame($expected, $actual);
      $_POST['test0'] = '0';
      $expected       = '';
      $actual         = $this->object->post('name', Input::STRING);
      $this->assertSame($expected, $actual);
      $_POST['name'] = null;
      $expected      = 'phil';
      $actual        = $this->object->post('name', Input::STRING, 'phil');
      $this->assertSame($expected, $actual);
      $_POST['test'] = 'ing';
      $this->assertSame('ing', $this->object->post('test'));
      $_POST['test'] = 'ing';
      $this->assertSame('ing', $this->object->post('test', Input::STRING));
      $_POST['test'] = 'ing';
      $this->assertSame('ing', $this->object->post('test', Input::STRING, ''));
      $_POST['test'] = 'ing';
      $this->assertSame(0, $this->object->post('test', Input::NUMERIC));
      $_POST['test'] = 'ing';
      $this->assertSame(0, $this->object->post('test', Input::NUMERIC, 0));
      $_POST['test'] = 'ing';
      $this->assertSame(1, $this->object->post('test', Input::NUMERIC, 1));
      $_POST['test'] = 'ing';
      $this->assertSame(null, $this->object->post('test2'));
      unset($_POST['test2']);
      $this->assertEquals('', $this->object->post('test2'));
      unset($_POST['test2']);
      $this->assertSame('', $this->object->post('test2', Input::STRING));
      unset($_POST['test2']);
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC));
      unset($_POST['test2']);
      $this->assertSame(5, $this->object->post('test2', Input::NUMERIC, 5));
      $_POST['test2'] = '0';
      $this->assertSame('0', $this->object->post('test2'));
      $_POST['test2'] = '0';
      $this->assertSame('0', $this->object->post('test2', Input::STRING));
      $_POST['test2'] = '0';
      $this->assertSame('0', $this->object->post('test2', Input::STRING, ''));
      $_POST['test2'] = '0';
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC));
      $_POST['test2'] = '0';
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC, 0));
      $_POST['test2'] = '0';
      $this->assertSame(0, $this->object->post('test2', Input::NUMERIC, 1));
      unset($_POST['test3']);
      $this->assertSame(null, $this->object->post('test3'));
      unset($_POST['test3']);
      $this->assertEquals(0, $this->object->post('test3'));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3'));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING, ''));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 0));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 1));
    }
    /**
     * @covers ADV\Core\Input\Input::__get
     * @todo   Implement testGet().
     */
    public function testGet() {
      $_GET['test0'] = 'wawa';
      $expected      = 'wawa';
      $actual        = $this->object->get('test0');
      $this->assertSame($expected, $actual);
      $_GET['test0'] = '0';
      $expected      = '0';
      $actual        = $this->object->get('test0');
      $this->assertSame($expected, $actual);
      $expected = '';
      $actual   = $this->object->get('name', Input::STRING);
      $this->assertSame($expected, $actual);
      $expected = 'phil';
      unset($_GET['name']);
      $actual = $this->object->get('name', Input::STRING, 'phil');
      $this->assertSame($expected, $actual);
      $_GET['test'] = 'ing';
      $this->assertSame('ing', $this->object->get('test'));
      $_GET['test'] = 'ing';
      $this->assertSame('ing', $this->object->get('test', Input::STRING));
      $_GET['test'] = 'ing';
      $this->assertSame('ing', $this->object->get('test', Input::STRING, ''));
      $_GET['test'] = 'ing';
      $this->assertSame(0, $this->object->get('test', Input::NUMERIC));
      $_GET['test'] = 'ing';
      $this->assertSame(0, $this->object->get('test', Input::NUMERIC, 0));
      $_GET['test'] = 'ing';
      $this->assertSame(1, $this->object->get('test', Input::NUMERIC, 1));
      $_GET['test'] = 'ing';
      $this->assertSame(null, $this->object->get('test2'));
      unset($_GET['test2']);
      $this->assertEquals('', $this->object->get('test2'));
      unset($_GET['test2']);
      $this->assertSame('', $this->object->get('test2', Input::STRING));
      unset($_GET['test2']);
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC));
      unset($_GET['test2']);
      $this->assertSame(5, $this->object->get('test2', Input::NUMERIC, 5));
      $_GET['test2'] = '0';
      $this->assertSame('0', $this->object->get('test2'));
      $this->assertSame('0', $this->object->get('test2', Input::STRING));
      $this->assertSame('0', $this->object->get('test2', Input::STRING, ''));
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC));
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC, 0));
      $this->assertSame(0, $this->object->get('test2', Input::NUMERIC, 1));
      $this->assertSame(null, $this->object->get('test3'));
      $this->assertEquals(0, $this->object->get('test3'));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3'));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING, ''));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 0));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 1));
    }
    /**
     * @covers ADV\Core\Input\Input::__request
     * @todo   Implement testRequest().
     */
    public function testRequest() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3'));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING));
      $_GET['test3'] = 7;
      $this->assertSame('', $this->object->get('test3', Input::STRING, ''));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 0));
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->get('test3', Input::NUMERIC, 1));
      $_GET['test3']  = 7;
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3'));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING));
      $_POST['test3'] = 7;
      $this->assertSame('', $this->object->post('test3', Input::STRING, ''));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 0));
      $_POST['test3'] = 7;
      $this->assertSame(7, $this->object->post('test3', Input::NUMERIC, 1));
      $_POST['test3'] = 7;
    }
    /**
     * @covers ADV\Core\Input\$this->object->_getPost
     * @todo   Implement testgetPost().
     */
    public function testGetPost() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->getPost('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(7, $this->object->getPost('test3'));
      unset($_GET['test3']);
      $this->assertSame(8, $this->object->getPost('test3'));
    }
    /**
     * @covers ADV\Core\Input\$this->object->_getPostGlobal
     * @todo   Implement testgetPostGlobal().
     */
    public function testgetPostGlobal() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->getPostGlobal('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(7, $this->object->getPostGlobal('test3'));
      unset($_GET['test3']);
      unset($_POST['test3']);
      $_SESSION['globals']['test3'] = 9;
      $this->assertSame(9, $this->object->getPostGlobal('test3'));
    }
    /**
     * @covers ADV\Core\Input\Input::__postGlobal
     * @todo   Implement testpostGlobal().
     */
    public function testpostGlobal() {
      $_GET['test3'] = 7;
      $this->assertSame(null, $this->object->postGlobal('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(8, $this->object->postGlobal('test3'));
      unset($_GET['test3']);
      unset($_POST['test3']);
      $_SESSION['globals']['test3'] = 9;
      $this->assertSame(9, $this->object->postGlobal('test3'));
    }
    /**
     * @covers ADV\Core\Input\Input::__postGet
     * @todo   Implement testpostGet().
     */
    public function testpostGet() {
      $_GET['test3'] = 7;
      $this->assertSame(7, $this->object->postGet('test3'));
      $_POST['test3'] = 8;
      $this->assertSame(8, $this->object->postGet('test3'));
      unset($_GET['test3']);
      $this->assertSame(8, $this->object->postGet('test3'));
    }
    /**
     * @covers ADV\Core\Input\Input::__session
     * @todo   Implement testSession().
     */
    public function testSession() {
      $_SESSION['test3'] = 7;
      $this->assertSame(7, $this->object->session('test3', Input::NUMERIC));
    }
    /**
     * @covers ADV\Core\Input\Input::__hasPost
     * @todo   Implement testhasPost().
     */
    public function testhasPost() {
      $this->assertSame(false, $this->object->hasPost('test'));
      $this->assertSame(false, $this->object->hasPost('test', 'test2'));
      $_POST['test'] = false;
      $this->assertSame(false, $this->object->hasPost('test', 'test2'), 'Should return false even if one variable is set.');
      $this->assertSame(true, $this->object->hasPost('test'), 'Should return true if post variable is set to false because it exists');
      $_POST['test2'] = null;
      $this->assertSame(false, $this->object->hasPost('test2'), 'Test2 is set but is null so it should return false!');
      $this->assertSame(false, $this->object->hasPost('test', 'test2'), 'Both are set but test2 is set but is null so it should return false!');
      $_POST['test2'] = 'something';
      $this->assertSame(true, $this->object->hasPost('test', 'test2'), 'Both are set but test2 is set but is null so it should return false!');
    }
    /**
     * @covers ADV\Core\Input\Input::__hasGet
     * @todo   Implement testhasGet().
     */
    public function testhasGet() {
      $_GET['test'] = false;
      $this->assertSame(true, $this->object->hasGet('test'));
      $this->assertSame(false, $this->object->hasGet('test', 'test2'));
    }
    /**
     * @covers ADV\Core\Input\Input::__has
     * @todo   Implement testHas().
     */
    public function testHas() {
      $_REQUEST['test'] = false;
      $this->assertSame(true, $this->object->has('test'));
      $this->assertSame(false, $this->object->has('test', 'test2'));
    }
    /**
     * @covers ADV\Core\Input\Input::__hasSession
     * @todo   Implement testhasSession().
     */
    public function testhasSession() {
      $_SESSION['test'] = false;
      $this->assertSame(true, $this->object->hasSession('test'));
      $this->assertSame(false, $this->object->hasSession('test', 'test2'));
    }
    public function testSetting() {
      Input::$post['test'] = 'wawa';
      $this->assertSame('wawa', $_POST['test']);
    }
    public function testGetting() {
      $_POST['test'] = 'wawa';
      $this->assertSame('wawa', Input::$post['test']);
    }
    public function testUnsetting() {
      Input::$post['test'] = 'wawa';
      unset(Input::$post['test']);
      $this->assertNotContains('test', $_POST);
    }
  }
