<?php
  namespace ADV\Core;
  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-05-17 at 14:37:31.
   */
  class InputTest extends \PHPUnit_Framework_TestCase
  {
    /**
     * @var Input
     */
    protected $object;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
      $this->object = new Input;
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    /**
     * @covers ADV\Core\Input::post
     */
    public function testPost()
    {
      $_POST['test0'] = 'wawa';
      $expected       = 'wawa';
      $actual         = Input::post('test0');
      $this->assertSame($expected, $actual);
      $_POST['test0'] = '0';
      $expected       = '0';
      $actual         = Input::post('test0');
      $this->assertSame($expected, $actual);
      $expected = '';
      $actual   = Input::post('name', Input::STRING);
      $this->assertSame($expected, $actual);
      $expected = 'phil';
      $actual   = Input::post('name', Input::STRING, 'phil');
      $this->assertSame($expected, $actual);
      $_POST['test'] = 'ing';
      $this->assertSame('ing', Input::post('test'));
      $this->assertSame('ing', Input::post('test', Input::STRING));
      $this->assertSame('ing', Input::post('test', Input::STRING, ''));
      $this->assertSame(0, Input::post('test', Input::NUMERIC));
      $this->assertSame(0, Input::post('test', Input::NUMERIC, 0));
      $this->assertSame(1, Input::post('test', Input::NUMERIC, 1));
      $this->assertSame(NULL, Input::post('test2'));
      $this->assertEquals('', Input::post('test2'));
      $this->assertSame('', Input::post('test2', Input::STRING));
      $this->assertSame(0, Input::post('test2', Input::NUMERIC));
      $this->assertSame(5, Input::post('test2', Input::NUMERIC, 5));
      $_POST['test2'] = '0';
      $this->assertSame('0', Input::post('test2'));
      $this->assertSame('0', Input::post('test2', Input::STRING));
      $this->assertSame('0', Input::post('test2', Input::STRING, ''));
      $this->assertSame(0, Input::post('test2', Input::NUMERIC));
      $this->assertSame(0, Input::post('test2', Input::NUMERIC, 0));
      $this->assertSame(0, Input::post('test2', Input::NUMERIC, 1));
      $this->assertSame(NULL, Input::post('test3'));
      $this->assertEquals(0, Input::post('test3'));
      $_POST['test3'] = 7;
      $this->assertSame(7, Input::post('test3'));
      $this->assertSame('', Input::post('test3', Input::STRING));
      $this->assertSame('', Input::post('test3', Input::STRING, ''));
      $this->assertSame(7, Input::post('test3', Input::NUMERIC));
      $this->assertSame(7, Input::post('test3', Input::NUMERIC, 0));
      $this->assertSame(7, Input::post('test3', Input::NUMERIC, 1));
    }
    /**
     * @covers ADV\Core\Input::get
     * @todo   Implement testGet().
     */
    public function testGet()
    {
      $_GET['test0'] = 'wawa';
      $expected      = 'wawa';
      $actual        = Input::get('test0');
      $this->assertSame($expected, $actual);
      $_GET['test0'] = '0';
      $expected      = '0';
      $actual        = Input::get('test0');
      $this->assertSame($expected, $actual);
      $expected = '';
      $actual   = Input::get('name', Input::STRING);
      $this->assertSame($expected, $actual);
      $expected = 'phil';
      $actual   = Input::get('name', Input::STRING, 'phil');
      $this->assertSame($expected, $actual);
      $_GET['test'] = 'ing';
      $this->assertSame('ing', Input::get('test'));
      $this->assertSame('ing', Input::get('test', Input::STRING));
      $this->assertSame('ing', Input::get('test', Input::STRING, ''));
      $this->assertSame(0, Input::get('test', Input::NUMERIC));
      $this->assertSame(0, Input::get('test', Input::NUMERIC, 0));
      $this->assertSame(1, Input::get('test', Input::NUMERIC, 1));
      $this->assertSame(NULL, Input::get('test2'));
      $this->assertEquals('', Input::get('test2'));
      $this->assertSame('', Input::get('test2', Input::STRING));
      $this->assertSame(0, Input::get('test2', Input::NUMERIC));
      $this->assertSame(5, Input::get('test2', Input::NUMERIC, 5));
      $_GET['test2'] = '0';
      $this->assertSame('0', Input::get('test2'));
      $this->assertSame('0', Input::get('test2', Input::STRING));
      $this->assertSame('0', Input::get('test2', Input::STRING, ''));
      $this->assertSame(0, Input::get('test2', Input::NUMERIC));
      $this->assertSame(0, Input::get('test2', Input::NUMERIC, 0));
      $this->assertSame(0, Input::get('test2', Input::NUMERIC, 1));
      $this->assertSame(NULL, Input::get('test3'));
      $this->assertEquals(0, Input::get('test3'));
      $_GET['test3'] = 7;
      $this->assertSame(7, Input::get('test3'));
      $this->assertSame('', Input::get('test3', Input::STRING));
      $this->assertSame('', Input::get('test3', Input::STRING, ''));
      $this->assertSame(7, Input::get('test3', Input::NUMERIC));
      $this->assertSame(7, Input::get('test3', Input::NUMERIC, 0));
      $this->assertSame(7, Input::get('test3', Input::NUMERIC, 1));
    }
    /**
     * @covers ADV\Core\Input::request
     * @todo   Implement testRequest().
     */
    public function testRequest()
    {
      $_GET['test3'] = 7;
      $this->assertSame(7, Input::get('test3'));
      $this->assertSame('', Input::get('test3', Input::STRING));
      $this->assertSame('', Input::get('test3', Input::STRING, ''));
      $this->assertSame(7, Input::get('test3', Input::NUMERIC));
      $this->assertSame(7, Input::get('test3', Input::NUMERIC, 0));
      $this->assertSame(7, Input::get('test3', Input::NUMERIC, 1));
      $_POST['test3'] = 7;
      $this->assertSame(7, Input::post('test3'));
      $this->assertSame('', Input::post('test3', Input::STRING));
      $this->assertSame('', Input::post('test3', Input::STRING, ''));
      $this->assertSame(7, Input::post('test3', Input::NUMERIC));
      $this->assertSame(7, Input::post('test3', Input::NUMERIC, 0));
      $this->assertSame(7, Input::post('test3', Input::NUMERIC, 1));
    }
    /**
     * @covers ADV\Core\Input::get_post
     * @todo   Implement testGet_post().
     */
    public function testGet_post()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::get_post_global
     * @todo   Implement testGet_post_global().
     */
    public function testGet_post_global()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::post_global
     * @todo   Implement testPost_global().
     */
    public function testPost_global()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::post_get
     * @todo   Implement testPost_get().
     */
    public function testPost_get()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::session
     * @todo   Implement testSession().
     */
    public function testSession()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::has_post
     * @todo   Implement testHas_post().
     */
    public function testHas_post()
    {
      $this->assertSame(FALSE, Input::has_post('test'));
      $this->assertSame(FALSE, Input::has_post('test', 'test2'));
      $_POST['test'] = FALSE;
      $this->assertSame(FALSE, Input::has_post('test', 'test2'), 'Should return false even if one variable is set.');
      $this->assertSame(TRUE, Input::has_post('test'), 'Should return true if post variable is set to false because it exists');
      $_POST['test2'] = NULL;
      $this->assertSame(FALSE, Input::has_post('test2'), 'Test2 is set but is NULL so it should return false!');
      $this->assertSame(FALSE, Input::has_post('test', 'test2'), 'Both are set but test2 is set but is NULL so it should return false!');
      $_POST['test2'] = 'something';
      $this->assertSame(TRUE, Input::has_post('test', 'test2'), 'Both are set but test2 is set but is NULL so it should return false!');
    }
    /**
     * @covers ADV\Core\Input::has_get
     * @todo   Implement testHas_get().
     */
    public function testHas_get()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::has
     * @todo   Implement testHas().
     */
    public function testHas()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::has_session
     * @todo   Implement testHas_session().
     */
    public function testHas_session()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\Input::_has
     * @todo   Implement test_has().
     */
    public function test_has()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
  }
