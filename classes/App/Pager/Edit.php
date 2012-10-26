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
      /** @var \ADV\App\User $user */
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
        $id           = $this->getActionId([$this->name . DELETE, $this->name . SAVE, $this->name . EDIT, $this->name . INACTIVE]);
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
      $view      = new View('ui/pager');
      $headers   = $this->generateHeaders();
      $headers[] = $this->formatNavigation('', $this->name . '_sort_' . count($this->columns) + 1, '', true, '');
      $headers[] = $this->formatNavigation('', $this->name . '_sort_' . count($this->columns) + 2, '', true, '');
      $form      = null;
      $form      = new Form();
      $form->start($this->name);
      $view->set('form', $form);
      $view->set('headers', $headers);
      $view->set('class', $this->class . ' width' . rtrim($this->width, '%'));
      $colspan = count($this->columns);
      $view->set('inactive', $this->showInactive !== null);
      if ($this->showInactive !== null) {
        $view['checked'] = ($this->showInactive) ? 'checked' : '';
        Ajax::_activate("_{$this->name}_span");
      }
      $view['colspan'] = $colspan;
      if ($this->rec_count) {
        $navbuttons[] = $this->formatNavigation('top', $this->name . '_page_' . self::FIRST, 1, $this->first_page, "<i class='icon-fast-backward'> </i>");
        $navbuttons[] = $this->formatNavigation('top', $this->name . '_page_' . self::PREV, $this->curr_page - 1, $this->prev_page, '<i class="icon-backward"> </i>');
        $navbuttons[] = $this->formatNavigation('top', $this->name . '_page_' . self::NEXT, $this->curr_page + 1, $this->next_page, '<i class="icon-forward"> </i>');
        $navbuttons[] = $this->formatNavigation('top', $this->name . '_page_' . self::LAST, $this->max_page, $this->last_page, '<i class="icon-fast-forward"> </i>');
        $view->set('navbuttons', $navbuttons);
        $from = ($this->curr_page - 1) * $this->page_length + 1;
        $to   = $from + $this->page_length - 1;
        if ($to > $this->rec_count) {
          $to = $this->rec_count;
        }
        $all             = $this->rec_count;
        $view['records'] = "Records $from-$to of $all";
      } else {
        $view['records'] = "No Records";
      }
      $this->currentRowGroup = null;
      $this->fieldnames      = array_keys(reset($this->data));
      $rows                  = [];
      $columns               = $this->columns;
      $columns[]             = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineEditBtn']];
      $columns[]             = ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatLineDeleteBtn']];
      foreach ($this->data as $row) {
        if ($this->rowGroup) {
          $fields = $this->fieldnames;
          $field  = $fields[$this->rowGroup[0][0] - 1];
          if ($this->currentRowGroup != $row[$field]) {
            $this->currentRowGroup = $row[$field];
            $row['group']          = $row[$field];
            $row['colspan']        = count($columns);
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
        $rows[]      = $row;
      }
      $view->set('rows', $rows);
      $navbuttons   = [];
      $navbuttons[] = $this->formatNavigation('bottom', $this->name . '_page_' . self::FIRST, 1, $this->first_page, "<i class='icon-fast-backward'> </i>");
      $navbuttons[] = $this->formatNavigation('bottom', $this->name . '_page_' . self::PREV, $this->curr_page - 1, $this->prev_page, '<i class="icon-backward"> </i>');
      $navbuttons[] = $this->formatNavigation('bottom', $this->name . '_page_' . self::NEXT, $this->curr_page + 1, $this->next_page, '<i class="icon-forward"> </i>');
      $navbuttons[] = $this->formatNavigation('bottom', $this->name . '_page_' . self::LAST, $this->max_page, $this->last_page, '<i class="icon-fast-forward"> </i>');
      $view->set('navbuttonsbottom', $navbuttons);
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
            $field      = $form->amount($name);
            $alignclass = 'class="alignright"';
            $group      = 'rest';
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
      $caption          = $this->editing->id ? SAVE : ADD;
      $field            = $form->button('_action', $this->name . SAVE, $caption)->preIcon(ICON_SAVE)->type('mini')->type('success');
      $field['tdclass'] = 'class="center"';
      if ($this->editing->id) {
        $field            = $form->button('_action', $this->name . CANCEL, CANCEL)->preIcon(ICON_CANCEL)->type('mini')->type('danger');
        $field['tdclass'] = 'class="center"';
      } else {
        $form->heading('');
      }
      reset($form['first'])->focus();
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
      $this->editing  = null;
      return parent::__sleep();
    }
    /**
     * @return string
     */
    public function __tostring() {
      ob_start();
      $this->display();
      return ob_get_clean();
    }
  }
