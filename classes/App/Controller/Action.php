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
  use ADV\Core\DB\DB;
  use ADV\App\User;
  use ADV\Core\Session;
  use ADV\Core\JS;
  use ADV\Core\Input\Input;

  /**

   */
  abstract class Action extends Base
  {
    use \ADV\Core\Traits\Action;

    protected $title;
    /*** @var \ADV\Core\Ajax */
    protected $Ajax;
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /*** @var JS */
    protected $JS;
    /** @var Input */
    protected $Input;
    public $help_context;
    /**

     */
    public function __construct(Session $session, User $user, Ajax $ajax, JS $js, Input $input, DB $db) {
      $this->Ajax    = $ajax;
      $this->JS      = $js;
      $this->Session = $session;
      $this->User    = $user;
      $this->Input   = $input;
      static::$DB    = $db;
    }
    public function run() {
      $this->action = $this->Input->post('_action');
      $this->before();
      $this->index();
      $this->after();
    }
    protected function before() {
    }
    protected function after() {
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
    }
    protected function runAction() {
      if ($this->action && is_callable(array($this, $this->action))) {
        call_user_func(array($this, $this->action));
      }
    }
  }
