<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 17/05/12
   * Time: 11:37 AM
   * To change this template use File | Settings | File Templates.
   */
  //namespace Controller;
  abstract class Controller_Base {
    protected $title;
    /*** @var User */
    protected $user;
    /*** @var Ajax */
    protected $ajax;
    public $help_context;
    function __construct() {
      $this->user = User::i();
      $this->ajax = Ajax::i();
      $this->before();
      $this->index();
      $this->after();
    }
    protected function before() {
    }
    abstract function index();
    protected function setTitle($title) {
      $this->title = _($this->help_context = $title);
    }
    protected function after() { }
  }
