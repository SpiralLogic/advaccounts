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
  use ADV\App\Page;
  use ADV\App\User;
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
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /*** @var JS */
    protected $JS;
    /** @var Input */
    protected $Input;
    protected $action;
    protected $actionID;
    public $help_context;
    /**

     */
    function __construct() {
      $this->Ajax    = Ajax::i();
      $this->JS      = JS::i();
      $this->Session = Session::i();
      $this->User    = User::i();
      $this->Input   = Input::i();
      static::$DB    = \ADV\Core\DB\DB::i();
      $this->action  = $this->Input->post('_action');
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
      if (isset($this->actionID)) {
        return $this->actionID;
      }
      $prefix = (array) $prefix;
      foreach ($prefix as $action) {
        if (strpos($this->action, $action) === 0) {
          $result = str_replace($action, '', $this->action);
          if (strlen($result)) {
            $this->action   = $action;
            $this->actionID = $result;
            return $result;
          }
        }
      }
      return -1;
    }
    protected function runAction() {
      if ($this->action && is_callable(array($this, $this->action))) {
        call_user_func(array($this, $this->action));
      }
    }
  }
