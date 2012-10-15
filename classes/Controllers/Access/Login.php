<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Access;
  use ADV\App\Forms;
  use ADV\App\Form\Form;
  use ADV\Core\Cell;
  use ADV\Core\View;
  use ADV\Core\Table;

  /**
   *
   */
  class Login extends \ADV\App\Controller\Action
  {

    public $view;
    /** @var \ADV\Core\Config */
    protected $Config;
    /** @var \ADV\App\Dates*/
    protected $Dates;
    public function run() {
      $this->Config = \ADV\Core\DIC::get('Config');
      $this->Dates  = \ADV\Core\DIC::get('Dates');
      parent::run();
    }
    protected function before() {
    }
    protected function index() {
      $this->setTitle($this->User->last_action ? 'Authorization timeout' : APP_TITLE . " " . VERSION . " - " . "Login");
      $view = new View('access/login');
      if ($this->Config->get('demo_mode') == true) {
        $view['login_text'] = _("Login as user: demouser and password: password");
      } else {
        $view['login_text'] = _("Please login here");
      }
      $view['theme']         = "default";
      $view['timeout']       = $timeout = $this->User->last_action;
      $view['encoding']      = isset($_SESSION['language']->encoding) ? $_SESSION['language']->encoding : "utf-8";
      $view['rtl']           = isset($_SESSION['language']->dir) ? $_SESSION['language']->dir : "ltr";
      $view['idletime']      = $this->User->last_action + $this->User->timeout - time();
      $view['usernamevalue'] = $this->User->last_action ? $this->User->loginname : ($this->Config->get('demo_mode') ? "demouser" : "");
      $view['company']       = $this->User->company;
      if (!headers_sent()) {
        header("Content-type: text/html; charset=UTF-8");
      }
      $form = new Form();
      $view->set('form', $form);
      $form->start("loginform", REQUEST_POST ? $_SESSION['timeout']['uri'] : '#');
      $form->text('user_name')->label('User name');
      $form->password('password')->label('Password')->value($this->Config->get('demo_mode') ? "password" : null);
      if ($timeout) {
        $form->group('hidden');
        $form->hidden('login_company')->value($this->User->company);
      } else {
        $form->arraySelect('login_company', $this->User->company, $this->Config->getAll('db'))->label('Company');        $form->group('hidden');
      }

      foreach ($_POST as $p => $val) {
        // add all request variables to be resend together with login data
        if (!in_array($p, array('user_name', 'password', 'SubmitUser', 'login_company'))) {
          $form->hidden(serialize($p))->value($val);
        }
      }
      if (REQUEST_GET) {
        $form->hidden('uri', $_SESSION['timeout']['uri']);
      }
      $form->group();$form->submit('SubmitUser', "Login -->");
      $form->end();
      if ($this->User->logged) {
        $view['date'] = $this->Dates->today() . " | " . $this->Dates->now();
      } else {
        $view['date'] = date("m/d/Y") . " | " . date("h.i am");
      }
      $view->render();
    }
  }

