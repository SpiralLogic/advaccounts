<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Access;

  use ADV\App\Form\Form;
  use ADV\Core\View;

  /**

   */
  class Login extends \ADV\App\Controller\Action
  {
    public $view;
    /** @var \ADV\Core\Config */
    protected $Config;
    /** @var \ADV\App\Dates */
    protected $Dates;
    public function run() {
      $this->Config = \ADV\Core\DIC::get('Config');
      $this->Dates = \ADV\Core\DIC::get('Dates');
      parent::run();
    }
    protected function before() {
      $this->Session->setFlash('uri', $_SERVER['DOCUMENT_URI']);
    }
    protected function index() {
      $this->setTitle($this->User->last_action ? 'Authorization timeout' : APP_TITLE . " " . VERSION . " - " . "Login");
      $view = new View('access/login');
      if ($this->Config->get('demo_mode') == true) {
        $view['login_text'] = _("Login as user: demouser and password: password");
      } else {
        $view['login_text'] = _("Please login here");
      }
      $view['theme'] = "default";
      $view['timeout'] = $timeout = $this->User->last_action;
      $view['encoding'] = isset($_SESSION['language']->encoding) ? $_SESSION['language']->encoding : "utf-8";
      $view['rtl'] = isset($_SESSION['language']->dir) ? $_SESSION['language']->dir : "ltr";
      $view['idletime'] = $this->User->last_action + $this->User->timeout - time();
      $view['usernamevalue'] = $this->User->last_action ? $this->User->loginname :
        ($this->Config->get('demo_mode') ? "demouser" : "");
      $view['company'] = $this->User->company;
      if (!headers_sent()) {
        header("Content-type: text/html; charset=UTF-8");
      }
      $form = new Form();
      $view->set('form', $form);
      $form->start('loginform', REQUEST_POST ? $_SESSION['timeout']['uri'] : '#', false, ['class' => 'formbox']);
      $form->text('user_name')->label('User name');
      $form->password('password')->label('Password')->value($this->Config->get('demo_mode') ? "password" : null);
      if ($timeout) {
        $form->group('hidden');
        $form->hidden('login_company')->value($this->User->company);
      } else {
        $companies = $this->Config->getAll('db');
        $logins = [];
        foreach ($companies as $k => $v) {
          if ($v['company']) {
            $logins[$k] = $v['company'];
          }
        }
        $form->arraySelect('login_company', $logins, $this->User->company)->label('Company');
        $form->group('hidden');
      }
      $password_iv = base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_CAST_256, MCRYPT_MODE_CFB), MCRYPT_DEV_URANDOM));
      $form->hidden('password_iv')->value($this->Session->setFlash('password_iv', $password_iv));
      unset($_POST['user_name'], $_POST['password'], $_POST['SubmitUser'], $_POST['login_company']);
      $form->group('buttons');
      $form->submit('SubmitUser', "Login -->")->type('small')->type('inverse');
      $form->end();
      if ($this->User->logged) {
        $view['date'] = $this->Dates->today() . " | " . $this->Dates->now();
      } else {
        $view['date'] = date("m/d/Y") . " | " . date("h.i a");
      }
      $view->render();
    }
  }

