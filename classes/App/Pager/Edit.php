<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 20/10/12
   * Time: 4:03 AM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\App\Pager;

  use ADV\App\Form\Form;
  use ADV\Core\Input\Input;
  use ADV\Core\Status;
  use ADV\Core\HTML;
  use ADV\Core\Ajax;
  use ADV\Core\View;

  /**5
   */
  class Edit extends Pager
  {
    use \ADV\Core\Traits\Action;

    /** @var \ADV\App\DB\Base */
    public $editing = null;
    /**
     * @static
     *
     * @param $name
     * @param $coldef
     *
     * @return $this
     */
    public static function newPager($name, $coldef) {
      $c = \ADV\Core\DIC::i();
      if (!isset($_SESSION['pager'])) {
        $_SESSION['pager'] = [];
      }
      if (isset($_SESSION['pager'][$name])) {
        $pager = $_SESSION['pager'][$name];
      }
      if (!isset($pager)) {
        $pager = new static($name, $coldef);
      }
      if (count($coldef) != count($pager)) {
        $pager->refresh();
      }
      static::$Input = $c->offsetGet('Input');
      static::$JS    = $c->offsetGet('JS');
      static::$Dates = $c->offsetGet('Dates');
      static::$DB    = $c->offsetGet('DB');
      /** @var \ADV\App\User $user  */
      $user                     = $c->offsetGet('User');
      $pager->page_length       = $user->prefs->query_size;
      $_SESSION['pager'][$name] = $pager;
      $pager->restoreColumnFunction($coldef);
      if (static::$Input->post('_action') == 'showInactive') {
        $pager->showInactive = (static::$Input->post('_value', Input::NUMERIC) == 1);
      }
      return $pager;
    }
    /**
     * @param $name
     * @param $coldef
     */
    public function __construct($name, $coldef) {
      $this->name = $name;
      $this->setColumns((array) $coldef);
    }
    /**
     * @param \ADV\App\DB\Base $object
     */
    public function setObject(\ADV\App\DB\Base $object) {
      $this->editing = $object;
      $this->runPost();
    }
    /**
     * @return \ADV\Core\Status|array|string
     */
    public function runPost() {
      if (REQUEST_POST) {
        $id           = $this->getActionId([$this->name . DELETE, $this->name . EDIT, $this->name . INACTIVE]);
        $this->action = str_replace($this->name, '', $this->action);
        $this->ready  = false;
        switch ($this->action) {
          case DELETE:
            $this->editing->load($id);
            $this->editing->delete();
            $this->editing->load(0);
            break;
          case EDIT:
            $this->editing->load($id);
            break;
          /** @noinspection PhpMissingBreakStatementInspection */
          case INACTIVE:
            $this->editing->load($id);
            $changes['inactive'] = Input::_post('_value', Input::NUMERIC);
          case SAVE:
            $changes = isset($changes) ? $changes : $_POST;
            $this->editing->save($changes);
            //run the sql from either of the above possibilites
            $status = $this->editing->getStatus();
            if ($status['status'] == Status::ERROR) {
              \ADV\Core\JS::_renderStatus($status);
            }
            $this->editing->load(0);
            break;
          case CANCEL:
            $this->editing->load(0);
            break;
        }
        if (isset($status)) {
          Ajax::_addStatus($status);
        }
      }
    }
    /**
     * @param $columns
     */
    protected function setColumns($columns) {
      foreach ($columns as &$col) {
        if (isset($col['edit']) && !isset($col['fun'])) {
          $col['fun'] = '';
        } elseif ($col['edit'] === true && isset($col['fun']) && is_array($col['fun'])) {
          $col['edit'] = $col['fun'];
          $col['edit'][1] .= 'Edit';
        }
      }
      parent::setColumns($columns);
    }
    /**
     * @return bool
     */
    public function display() {
      $this->selectRecords();
      Ajax::_start_div("_{$this->name}_span");
      $headers = $this->generateHeaders();
      $html    = new HTML;
      $this->formatNavigation('', $html, $this->name . '_sort_' . count($this->columns) + 1, '', true, '');
      $headers[] = (string) $html;
      $html      = new HTML;
      $this->formatNavigation('', $html, $this->name . '_sort_' . count($this->columns) + 2, '', true, '');
      $headers[] = (string) $html;
      $class     = $this->class . ' width' . rtrim($this->width, '%');
      $form      = null;
      $form      = new Form();
      $form->start($this->name);
      echo $form['_start'];
      echo "<div class='center'><table class='" . $class . "'>";
      echo  $this->displayHeaders($headers);
      $this->currentRowGroup = null;
      $this->fieldnames      = array_keys(reset($this->data));
      $columns               = $this->columns;
      $columns[]             = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineEditBtn']];
      $columns[]             = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineDeleteBtn']];
      foreach ($this->data as $row) {
        $this->displayRow($row, $columns, $form);
      }
      if (is_object($this->editing) && !$this->editing->id) {
        $row = end($this->data) ? : get_object_vars($this->editing);
        if (!$this->fieldnames) {
          $this->fieldnames = array_keys($row);
        }
        $this->editRow($form);
      }
      echo "<tfoot>";
      echo $this->displayNavigation('bottom');
      echo "</tfoot></table></div>";
      echo $form['_end'];
      Ajax::_end_div();
      return true;
    }
    /**
     * @param      $row
     * @param null $columns
     * @param Form $form
     *
     * @return mixed
     */
    protected function displayRow($row, $columns = null, Form $form = null) {
      if ($this->action == DELETE && $this->actionID == $row['id']) {
        return null;
      }
      if ($this->editing->id == $row['id'] && $form) {
        return $this->editRow($form);
      }
      return parent::displayRow($row, $columns);
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @internal param bool $setvals
     * @return mixed
     */
    protected function editRow(Form $form) {
      $view = new View('form/pager');
      $view->set('form', $form);
      $group  = 'first';
      $fields = $this->fieldnames;
      foreach ($this->columns as $key => $col) {
        $field   = '';
        $coltype = isset($col['type']) ? $col['type'] : '';
        $name    = isset($col['name']) ? $col['name'] : '';
        $name    = $name ? : $fields[$key];
        if (isset($col['edit'])) { // use data input function if defined
          $coltype = 'edit';
        }
        $form->group($group);
        $class      = isset($col['class']) ? $col['class'] : null;
        $alignclass = isset($col['align']) ? " class='$class align" . $col['align'] . "'" : ($class ? "class='$class'" : "");
        switch ($coltype) { // format columnhsdaasdg
          case 'fun': // column not displayed
          case 'edit': // column not displayed
            $fun = $col['edit'];
            if (is_callable($fun)) {
              $field = call_user_func($fun, $form);
            }
            $group = 'rest';
            break;
          case self::TYPE_SKIP: // column not displayed
          case self::TYPE_GROUP: // column not displayed
            $field = $form->group('hidden')->hidden($name);
            break;
          case 'disabled':
            $form->heading($this->editing->$name);
            break;
          case self::TYPE_AMOUNT: // column not displayed
            $field = $form->amount($name);
            $group = 'rest';
            break;
          default:
            $field = $form->text($name);
            $group = 'rest';
        }
        if ($field instanceof \ADV\App\Form\Field) {
          if (is_object($this->editing) && $name) {
            $field->initial($this->editing->$name);
          }
          $field['tdclass']   = $alignclass;
          $field['tdcolspan'] = "colspan=2";
        }
      }
      $form->group('rest');
      $field = $this->formatSaveLineBtn($form);
      if ($this->editing->id) {
        $this->formatCancelLineBtn($form);
      } else {
        $field['tdclass'] .= " colspan=2";
      }
      reset($form['first'])->focus();
      $view->render();
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatLineEditBtn($row) {
      if ($this->editing->id) {
        return '';
      }
      $button = new \ADV\App\Form\Button('_action', $this->name . EDIT . $row['id'], EDIT);
      $button->type('mini')->type('primary');
      return $button;
    }
    /**
     * @param $row
     *
     * @return \ADV\App\Form\Button
     */
    public function formatLineDeleteBtn($row) {
      if ($this->editing->id) {
        return '';
      }
      $button = new \ADV\App\Form\Button('_action', $this->name . DELETE . $row['id'], DELETE);
      $button->preIcon(ICON_DELETE);
      $button->type('mini')->type('danger');
      return $button;
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @return \ADV\App\Form\Button
     */
    protected function formatSaveLineBtn(Form $form) {
      $caption = $this->editing->id ? SAVE : 'Add';
      return $form->button('_action', $this->name . SAVE, $caption)->preIcon(ICON_SAVE)->type('mini')->type('success');
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @return \ADV\App\Form\Button
     */
    protected function formatCancelLineBtn(Form $form) {
      return $form->button('_action', $this->name . CANCEL, CANCEL)->preIcon(ICON_CANCEL)->type('mini')->type('danger');
    }
    /**
     * @param $coldef
     */
    protected function restoreColumnFunction($coldef) {
      foreach ($this->columns as &$column) {
        if (isset($column['funkey'])) {
          $column['fun']  = $coldef[$column['funkey']]['fun'];
          $column['edit'] = $coldef[$column['funkey']]['edit'];
        }
      }
    }
    /**
     * @return array
     */
    public function __sleep() {
      foreach ($this->columns as &$col) {
        if (isset($col['edit'])) {
          $col['edit'] = null;
        }
      }
      $this->action   = null;
      $this->actionID = null;
      return parent::__sleep();
    }
  }
