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
  use ADV\Core\Num;
  use ADV\Core\Arr;
  use ADV\Core\Input\Input;
  use ADV\Core\Status;
  use ADV\Core\Ajax;
  use ADV\Core\View;

  /**5
   */
  class Edit extends Pager
  {
    const TYPE_DISABLED = 'disabled';
    const TYPE_EDIT = 'edit';
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
      static::$JS = $c->offsetGet('JS');
      static::$Dates = $c->offsetGet('Dates');
      static::$DB = $c->offsetGet('DB');
      /** @var \ADV\App\User $user */
      $user = $c->offsetGet('User');
      $pager->page_length = $user->prefs->query_size;
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
      $this->setColumns((array)$coldef);
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
      if (REQUEST_POST && static::$Input->post('_form_id') == $this->name . '_form') {
        $id = $this->getActionId([DELETE, SAVE, EDIT, INACTIVE]);
        $this->ready = false;
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
            $changes['inactive'] = static::$Input->post('_value', Input::NUMERIC);
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
      $view = new View('ui/pager');
      $headers = $this->generateHeaders();
      Arr::append($headers, ['', '']);
      $form = new Form();
      $form->start($this->name);
      $view->set('form', $form);
      $view->set('headers', $headers);
      $view->set('class', $this->class . ' width' . rtrim($this->width, '%'));
      $view->set('inactive', $this->showInactive !== null);
      $this->generateNav($view);
      $this->currentRowGroup = null;
      $this->fieldnames = array_keys(reset($this->data));
      $rows = [];
      $columns = $this->columns;
      $columns[] = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineEditBtn']];
      $columns[] = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineDeleteBtn']];
      foreach ($this->data as $row) {
        if ($this->rowGroup) {
          $fields = $this->fieldnames;
          $field = $fields[$this->rowGroup[0][0] - 1];
          if ($this->currentRowGroup != $row[$field]) {
            $this->currentRowGroup = $row[$field];
            $row['group'] = $row[$field];
            $row['colspan'] = count($columns);
          }
        }
        if (is_callable($this->rowFunction)) {
          $row['attrs'] = call_user_func($this->rowFunction, $row);
        }
        if ($this->action == DELETE && $this->actionID == $row['id']) {
          continue;
        }
        if ($this->editing->id == $row['id'] && $form) {
          $row['edit'] = $this->editRow($form);
        } else {
          $row['cells'] = parent::displayRow($row, $columns);
        }
        $rows[] = $row;
      }
      if (is_object($this->editing) && !$this->editing->id) {
        $row = end($this->data) ? : get_object_vars($this->editing);
        if (!$this->fieldnames) {
          $this->fieldnames = array_keys($row);
        }
        $row['edit'] = $this->editRow($form);
        $rows[] = $row;
      }
      $view->set('rows', $rows);
      $view->render();
      Ajax::_end_div();
      return true;
    }
    /**
     * @param \ADV\App\Form\Form $form
     *
     * @internal param $row
     * @internal param bool $setvals
     * @return mixed
     */
    protected function editRow(Form $form) {
      foreach ($this->columns as $key => $col) {
        $field = '';
        $coltype = isset($col['type']) ? $col['type'] : '';
        $name = isset($col['name']) ? $col['name'] : '';
        $name = $name ? : $this->fieldnames[$key];
        if (isset($col['edit'])) { // use data input function if defined
          $coltype = 'edit';
        }
        $class = isset($col['class']) ? $col['class'] : null;
        $alignclass = isset($col['align']) ? " class='$class align" . $col['align'] . "'" : ($class ? "class='$class'" : "");
        if (is_object($this->editing) && property_exists($this->editing, $name)) {
          $value = $this->editing->$name;
        }
        $form->group('cells');
        switch ($coltype) { // format columnhsdaasdg
          case 'fun': // column not displayed
          case self::TYPE_EDIT: // column not displayed
            $fun = $col['edit'];
            if (is_callable($fun)) {
              $field = call_user_func($fun, $form);
            }
            break;
          case self::TYPE_SKIP: // column not displayed
          case self::TYPE_GROUP: // column not displayed
            $field = $form->group('hidden')->hidden($name);
            break;
          case self::TYPE_DISABLED:
            $form->heading('');
            break;
          case self::TYPE_AMOUNT: // column not displayed
            $field = $form->amount($name);
            $alignclass = 'class="alignright"';
            break;
          case self::TYPE_RATE: // column not displayed
            $field = $form->number($name, Num::i()->exrate_dec);
            break;
          case self::TYPE_DATE:
            $value = static::$Dates->sqlToDate($value);
            $field = $form->date($name);
            break;
          default:
            $field = $form->text($name);
        }
        if ($field instanceof \ADV\App\Form\Field) {
          if (is_object($this->editing) && $name) {
            $field->initial($value);
          }
          if (isset($col['readonly'])) {
            $field->readonly($col['readonly']);
          }
          if (!$field instanceof \ADV\App\Form\Custom) {
            $field['tdclass'] = $alignclass;
            $field['tdcolspan'] = "colspan=2";
          }
          $field['form'] = $this->name . '_form';
        }
      }
      $form->group('save')->button('_action', SAVE, $this->editing->id ? SAVE : ADD, ['form' => $this->name . '_form'])->preIcon(ICON_SAVE)->type('mini')->type(
        'success'
      );
      $form->group('button');
      if ($this->editing->id) {
        $form->button('_action', CANCEL, CANCEL, ['form' => $this->name . '_form'])->preIcon(ICON_CANCEL)->type('mini')->type('danger');
      } else {
        $form->heading('');
      }
      reset($form['cells'])->focus();
      return true;
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
      $button = new \ADV\App\Form\Button('_action', EDIT . $row['id'], EDIT);
      $button->type('mini')->type('primary');
      $button['form'] = $this->name . '_form';
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
      $button = new \ADV\App\Form\Button('_action', DELETE . $row['id'], DELETE);
      $button->preIcon(ICON_DELETE);
      $button->type('mini')->type('danger');
      $button['form'] = $this->name . '_form';
      return $button;
    }
    /**
     * @param $coldef
     */
    protected function restoreColumnFunction($coldef) {
      foreach ($this->columns as &$column) {
        if (isset($column['funkey'])) {
          $column['fun'] = $coldef[$column['funkey']]['fun'];
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
      $this->action = null;
      $this->actionID = null;
      $this->editing = null;
      return parent::__sleep();
    }
  }
