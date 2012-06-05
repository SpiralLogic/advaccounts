<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 17/05/12
   * Time: 11:37 AM
   * To change this template use File | Settings | File Templates.
   */
  //namespace Controller;
  abstract class Controller_Base
  {
    protected $title;
    /*** @var User */
    protected $user;
    /*** @var Ajax */
    protected $ajax;
    protected $action;
    public $help_context;
    function __construct()
    {
      $this->user   = User::i();
      $this->ajax   = Ajax::i();
      $this->action = Input::post('_action');
      $this->before();
      $this->index();
      $this->after();
    }
    protected function before()
    {
    }
    abstract function index();
    /**
     * @param $title
     */
    protected function setTitle($title)
    {
      $this->title = _($this->help_context = $title);
    }
    protected function after() { }
    /**
     * @param $prefix
     *
     * @return bool|mixed
     */
    protected function getActionId($prefix)
    {
      if (strpos($this->action, $prefix) !== false) {
        return str_replace($prefix, '', $this->action);
      }
      return -1;
    }
  }
