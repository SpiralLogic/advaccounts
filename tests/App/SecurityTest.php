<?php
  /**
   * Generated by PHPUnit_SkeletonGenerator on 2012-07-01 at 22:11:32.
   */
  class SecurityTest extends PHPUnit_Framework_TestCase {
    /** @var Security **/
    protected $security;
    protected $config;
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
      $this->config  = $this->getMockBuilder('ADV\\Core\\Config')->disableOriginalConstructor()->getMock();
      $access_levels = include(ROOT_DOC . 'config' . DS . 'access_levels.php');
      $map           = [
        ['access_levels.areas', false, $access_levels['areas']],
        ['access_levels.sections', false, $access_levels['sections']]
      ];
      $this->config->expects($this->any())->method('get')->will($this->returnValueMap($map));
      $this->security = new \ADV\App\Security($this->config);
    }
    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
    }
    public function testConstruct() {
      $access_levels = include(ROOT_DOC . 'config' . DS . 'access_levels.php');
      $expected      = $access_levels['areas'];
      $this->assertAttributeEquals($expected, 'areas', $this->security);
      $expected = $access_levels['sections'];
      $this->assertAttributeEquals($expected, 'sections', $this->security);
    }
    /**
     * @covers Security::check_page
     * @todo   Implement testCheck_page().
     */
    public function testCheck_page() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::set_page
     * @todo   Implement testSet_page().
     */
    public function testSet_page() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::get_role
     * @todo   Implement testGet_role().
     */
    public function testGet_role() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::add_role
     * @todo   Implement testAdd_role().
     */
    public function testAdd_role() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::update_role
     * @todo   Implement testUpdate_role().
     */
    public function testUpdate_role() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::delete
     * @todo   Implement testDelete().
     */
    public function testDelete() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::check_role_used
     * @todo   Implement testCheck_role_used().
     */
    public function testCheck_role_used() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::roles
     * @todo   Implement testRoles().
     */
    public function testRoles() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::roles_cells
     * @todo   Implement testRoles_cells().
     */
    public function testRoles_cells() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * @covers Security::roles_row
     * @todo   Implement testRoles_row().
     */
    public function testRoles_row() {
      // Remove the following lines when you implement this test.
      $this->markTestIncomplete('This test has not been implemented yet.');
    }
    /**
     * Tests Security::htmlentities()
     * @test
     */
    public function test_htmlentities_doublequote_and_ampersand() {
      $output   = Security::htmlentities('"H&M"');
      $expected = '&quot;H&amp;M&quot;';
      $this->assertEquals($expected, $output);
    }
    /**
     * Tests Security::htmlentities()
     * @test
     */
    public function test_htmlentities_singlequote() {
      $output   = Security::htmlentities("'");
      $expected = '\'';
      $this->assertEquals($expected, $output);
    }
    /**
     * Tests Security::htmlentities()
     * @test
     */
    public function test_htmlentities_charactor_references_no_double_encode() {
      $output   = \ADV\Core\Security::htmlentities('You must write & as &amp;');
      $expected = 'You must write &amp; as &amp;';
      $this->assertEquals($expected, $output);
    }
    /**
     * Tests Security::htmlentities()
     * @test
     */
    public function test_htmlentities_charactor_references_double_encode() {
      $config = $this->config->get('security.htmlentities_double_encode');
      $this->config->set('security.htmlentities_double_encode', true);
      $output   = \ADV\Core\Security::htmlentities('You must write &amp; as &amp;amp;');
      $expected = 'You must write &amp; as &amp;amp;';
      $this->assertEquals($expected, $output);
      $this->config->set('security.htmlentities_double_encode', $config);
    }
    /**
     * Tests Security::htmlentities()
     * @test
     */
    public function test_htmlentities_double_encode() {
      $output   = \ADV\Core\Security::htmlentities('"H&M"');
      $output   = \ADV\Core\Security::htmlentities($output);
      $expected = '&quot;H&amp;M&quot;';
      $this->assertEquals($expected, $output);
    }
  }
