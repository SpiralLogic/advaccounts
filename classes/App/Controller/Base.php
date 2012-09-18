<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 17/05/12
   * Time: 11:37 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Controller;

  use ADV\Core\Ajax;
  use ADV\App\User;
  use ADV\Core\DB\DB;
  use ADV\Core\JS;
  use ADV\Core\Input\Input;
  use ADV\Core\Config;
  use ADV\Core\Session;

  /**

   */
  abstract class Base {
    protected $title;
    /*** @var User */
    protected $User;
    /*** @var \ADV\Core\Ajax */
    protected $Ajax;
    /*** @var Session */
    protected $Session;
    /*** @var \ADV\Core\DB\DB */
    protected $DB;
    /*** @var JS */
    protected $JS;
    /** @var Input */
    protected $Input;
    protected $action;
    public $help_context;
    /**

     */
    function __construct() {
      $this->Ajax    = Ajax::i();
      $this->JS      = JS::i();
      $this->Session = Session::i();
      $this->User    = User::getCurrentUser($this->Session, Config::i());
      $this->DB      = DB::i();
      $this->Input   = Input::i();
      $this->action  = $this->Input->post('_action');
      $this->method  = $_SERVER['REQUEST_METHOD'];
      $this->before();
      $this->index();
      $this->after();
    }
    protected function before() {
    }
    abstract protected function index();
    /**
     * @param $title
     */
    protected function setTitle($title) {
      $this->title = _($this->help_context = $title);
    }
    protected function after() {
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
    }
    /**
     * @param $prefix
     *
     * @return int|mixed
     */
    protected function getActionId($prefix) {
      if (strpos($this->action, $prefix) === 0) {
        $result = str_replace($prefix, '', $this->action);
        if (strlen($result)) {
          return $result;
        }
      }

      return -1;
    }
    protected function runAction() {
      if ($this->action && is_callable(array($this, $this->action))) {
        call_user_func(array($this, $this->action));
      }
    }
    /**
     * @return array
     */
    public function __sleep() {
      $this->DB = null;
      return array_keys((array) $this);
    }
  }
