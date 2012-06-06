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
    /*** @var Session */
    protected $session;
    /*** @var \DB */
    protected $db;
    protected $action;
    public $help_context;
    /**

     */
    function __construct()
    {
      $this->user   = User::i();
      $this->ajax   = Ajax::i();
      $this->session = Session::i();
      $this->db = \DB::i();

      $this->action = Input::post('_action');
      $this->before();
      $this->index();
      $this->after();
    }
  abstract protected function before();
    abstract protected function index();

    /**
     * @param $title
     */
    protected function setTitle($title)
    {
      $this->title = _($this->help_context = $title);
    }
    abstract protected function after();
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    abstract protected function runValidation();
    /**
     * @param $prefix
     *
     * @return int|mixed
     */
    protected function getActionId($prefix)
    {
      if (strpos($this->action, $prefix) !== false) {
        return str_replace($prefix, '', $this->action);
      }
      return -1;
    }
    protected function runAction()
    {
      if ($this->action) {
        call_user_func(array($this, $this->action));
      }
    }


  }
