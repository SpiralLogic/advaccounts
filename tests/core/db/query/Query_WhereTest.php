<?php
  namespace ADV\Core\DB\Query;

  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-06-05 at 06:09:28.
   */
  class WhereTest extends \PHPUnit_Framework_TestCase
  {
    protected $stub;
    /**
     * @var Where
     */
    protected $object;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
      $this->stub = $stub = $this->getMockForAbstractClass('\\ADV\\Core\\DB\\Query\\Where');
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    /**
     * @covers ADV\Core\DB\Query\Where::where
     * @todo   Implement testWhere().
     */
    public function testWhere()
    {
      /** @var Where $stub  */
      $stub = $this->stub;
      $this->assertEquals($stub, $stub->where('test=', 3));

      $this->assertAttributeEquals([0=> 'test= :dbcondition0'], 'where', $stub);
      $this->assertAttributeEquals([':dbcondition0'=> 3], 'wheredata', $stub);
      $this->assertEquals($stub, $stub->where('test=', 6));

      $this->assertAttributeEquals([0=> 'test= :dbcondition0', 1=> 'AND test= :dbcondition1'], 'where', $stub);
      $this->assertAttributeEquals([':dbcondition0'=> 3, ':dbcondition1'=> 6], 'wheredata', $stub);
    }
    public function testWhereWithArray()
    {
      /** @var Where $stub  */
      $stub = $this->stub;
      $this->assertEquals($stub, $stub->where([['test=', 3], ['test2=', 5]]));

      $this->assertAttributeEquals([0=> 'test= :dbcondition0', 1=> 'AND test2= :dbcondition1'], 'where', $stub);
      $this->assertAttributeEquals([':dbcondition0'=> 3, ':dbcondition1'=> 5], 'wheredata', $stub);
    }
    public function testOr()
    {
      /** @var Where $stub  */
      $stub = $this->stub;
      $this->assertEquals($stub, $stub->orWhere('test=', 3));

      $this->assertAttributeEquals([0=> 'test= :dbcondition0'], 'where', $stub);
      $this->assertAttributeEquals([':dbcondition0'=> 3], 'wheredata', $stub);
    }
    /**
     * @covers ADV\Core\DB\Query\Where::or_where
     * @todo   Implement testorWhere().
     */
    public function testOr_whereWithArray()
    {
      /** @var Where $stub  */
      $stub = $this->stub;
      $this->assertEquals($stub, $stub->orWhere([['test=', 3], ['test2=', 5]]));

      $this->assertAttributeEquals([0=> 'test= :dbcondition0', 1=> 'OR test2= :dbcondition1'], 'where', $stub);
      $this->assertAttributeEquals([':dbcondition0'=> 3, ':dbcondition1'=> 5], 'wheredata', $stub);
    }
    /**
     * @covers ADV\Core\DB\Query\Where::and_where
     * @todo   Implement testandWhere().
     */
    public function testandWhere()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\DB\Query\Where::or_open
     * @todo   Implement testorOpen().
     */
    public function testorOpen()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\DB\Query\Where::and_open
     * @todo   Implement testandOpen().
     */
    public function testandOpen()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\DB\Query\Where::close_and
     * @todo   Implement testcloseAnd().
     */
    public function testcloseAnd()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\DB\Query\Where::close_or
     * @todo   Implement testcloseOr().
     */
    public function testcloseOr()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\DB\Query\Where::open
     * @todo   Implement testOpen().
     */
    public function testOpen()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers ADV\Core\DB\Query\Where::close
     * @todo   Implement testClose().
     */
    public function testClose()
    {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
  }
