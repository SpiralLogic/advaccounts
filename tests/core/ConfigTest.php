<?php  namespace ADV\Core;  /**   * Generated by PHPUnit_SkeletonGenerator on 2012-06-12 at 17:09:22.   */  class ConfigTest extends \PHPUnit_Framework_TestCase  {    /**     * @var Config     */    protected $object;    /**     * Sets up the fixture, for example, opens a network connection.     * This method is called before a test is executed.     */    protected function setUp()    {      $this->object = new Config;      $cache        = $this->getMockBuilder('\\ADV\\Core\\Cache')->disableOriginalConstructor()->getMock();      $cache->expects($this->any())->method('_delete')->will($this->returnValue(true));      $cache->expects($this->any())->method('_set')->will($this->returnArgument(1));    }    /**     * Tears down the fixture, for example, closes a network connection.     * This method is called after a test is executed.     */    protected function tearDown()    {    }    /**     * @covers ADV\Core\Config::_set     * @todo   Implement test_set().     */    public function test_set()    {      $result = $this->object->_set('test', 'val');      $this->assertEquals('val', $result);      $vars = $this->readAttribute($this->object, '_vars');      $this->assertArrayHasKey('config', $vars);      $vars = $vars['config'];      $this->assertArrayHasKey('test', $vars);    }    /**     * @covers ADV\Core\Config::_get     * @depnds test_set     */    public function test_get()    {      $this->object->_set('test', 'val');      $actual = $this->object->_get('test');      $this->assertEquals('val', $actual);      $actual = $this->object->_get('test2', 'default');      $this->assertEquals('default', $actual);    }    /**     * @covers  ADV\Core\Config::_remove     * @depends test_set     */    public function test_remove()    {      $this->object->_set('test', 'val');      $vars = $this->readAttribute($this->object, '_vars');      $this->assertArrayHasKey('config', $vars);      $vars = $vars['config'];      $this->assertArrayHasKey('test', $vars);      $this->object->_remove('test');      $vars = $this->readAttribute($this->object, '_vars');      $this->assertArrayHasKey('config', $vars);      $vars = $vars['config'];      $this->assertArrayNotHasKey('test', $vars);    }    /**     * @covers ADV\Core\Config::_get_all     * @todo   Implement test_getAll().     */    public function test_getAll()    {      $vars = $this->readAttribute($this->object, '_vars');      $this->assertEquals($vars['config'], $this->object->_getAll());      $actual = $this->object->_getAll('apps');      $vars   = $this->readAttribute($this->object, '_vars');      $this->assertEquals($vars['apps'], $actual);      return $vars;    }    /**     * @covers  ADV\Core\Config::_removeAll     * @depends test_getAll     */    public function test_removeAll($vars)    {      $actual = $this->object->_getAll('apps');      $this->assertEquals($vars['apps'], $actual);      $this->object->_removeAll();      $actual = $this->readAttribute($this->object, '_vars');      $this->assertEquals([], $actual);    }    /**     * @covers ADV\Core\Config::_reset     */    public function test_reset()    {      $actual = $this->object->_getAll(); // Remove the following lines when you implement this test.      $this->assertAttributeNotEmpty('_vars', $this->object);      $vars = $this->readAttribute($this->object, '_vars');      $this->assertArrayHasKey('config', $vars);      $this->assertSame($vars['config'], $actual);      $this->object->_set('config.test', 'testing');      $vars = $this->readAttribute($this->object, '_vars');      $this->assertSame($vars['config']['test'], 'testing');      $this->object->_reset();      $this->assertAttributeNotEmpty('_vars', $this->object);      $vars = $this->readAttribute($this->object, '_vars');      $this->assertArrayNotHasKey('test', $vars['config']);    }    /**     * @covers ADV\Core\Config::_shutdown     * @todo   Implement test_shutdown().     */    public function test_shutdown()    {      $actual   = $this->object->_shutdown();      $expected = $this->readAttribute($this->object, '_vars');      $this->assertSame($expected, $actual);    }  }