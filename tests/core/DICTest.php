<?php
  namespace ADV\Core;

  /**
   * Container test case.
   */
  class DICTests extends \PHPUnit_Framework_TestCase
  {
    /**
     * @var Container
     */
    private $container;
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
      parent::setUp();
      $this->container = new DIC();
    }
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
      $this->container = null;
      parent::tearDown();
    }
    public function testSetAndHas()
    {
      $c = $this->container;

      // Explicit Call
      $c->set('test', function() { });
      $this->assertTrue($c->has('test'));
    }
    public function testSetParam()
    {
      $c = $this->container;

      // Explicit Call Only
      $c->setParam('test', 'testing');
      $this->assertEquals('testing', $c->get('test'));
    }
    public function testGet()
    {
      $c = $this->container;

      // Explicit Call
      $c->set('test', function($c, $name)
      {
        return new TestObj($name);
      });
      $obj = $c->get('test', 'testing');
      $this->assertInstanceOf('\\ADV\\Core\\TestObj', $obj);
      $this->assertAttributeEquals('testing', 'name', $obj);
    }
    public function testFresh()
    {
      $c = $this->container;

      $c->set('Obj', function($c, $name)
      {
        return new TestObj($name);
      });

      $o1 = $c->fresh('Obj', 'one');
      $o2 = $c->fresh('Obj', 'one');
      $this->assertNotSame($o1, $o2);
    }
    /*
    public function testDelete()
    {
      $c = $this->container;
      $c->setObj(function() { return new \stdClass(); });
      $c->deleteObj();
    }
    */

    public function testDependency()
    {
      $c = $this->container;

      $c->set('Parent', function()
      {
        return new \stdClass();
      });

      $c->set('Child', function($c)
      {
        $child         = new \stdClass();
        $child->parent = $c->get('Parent');
        return $child;
      });

      $parent = $c->get('Parent');
      $child  = $c->get('Child');

      $this->assertSame($parent, $child->parent);
    }
    public function testConstructorArguments()
    {
      $c = $this->container;

      $c->set('TestObj', function($c, $name)
      {
        return new TestObj($name);
      });

      $o1 = $c->get('TestObj', 'A');
      $o2 = $c->get('TestObj');
      $o3 = $c->fresh('TestObj', 'B');
      $o4 = $c->get('TestObj', 'A');

      $this->assertAttributeEquals('A', 'name', $o1);
      $this->assertAttributeEquals('A', 'name', $o2);
      $this->assertAttributeEquals('B', 'name', $o3);
      $this->assertSame($o1, $o4);
    }
    public function testAlternateMethodFormat()
    {
      $c = $this->container;

      $c['arrayaccess'] = function($c, $name)
      {
        return new TestObj($name);
      };

      $obj = $c->get('arrayaccess', 'wawa');
      $this->assertInstanceOf('ADV\\Core\\TestObj', $obj);
      $this->assertAttributeEquals('wawa', 'name', $obj);
    }
    public function testMixedMethodFormat()
    {
      $c = $this->container;

      $c->set('ObjectOne', function()
      {
        return new TestObj('object one');
      });

      $obj = $c['ObjectOne'];

      $this->assertInstanceOf('\\ADV\\Core\\TestObj', $obj);
      $this->assertAttributeEquals('object one', 'name', $obj);
    }
  }

  class TestObj
  {
    public $name;
    function __construct($name)
    {
      $this->name = $name;
    }
  }
