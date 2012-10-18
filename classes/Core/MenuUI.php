<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  /**

   */
  class MenuUI extends Menu {
    /** @var JS */
    protected $JS;
    /** @var bool **/
    public $tabs = [];
    public $current_tab;
    public $firstPage = 0;
    /** @var int **/
    public static $menuCount = 0;
    protected $jslinks = [];
    protected $defaultState = 'default';
    /** @var array **/
    protected $options = [];
    /**
     * @param string $defaultState
     *
     * @internal param array $options
     */
    public function __construct($defaultState = 'default') {
      $this->defaultState = $defaultState;
      $this->setJSObject();
    }
    /**
     * @param JS $js
     */
    public function setJSObject(JS $js = null) {
      $this->JS = $js ? : JS::i();
    }
    /**
     * @param        $title
     * @param string $tooltip
     * @param string $link
     *
     * @return MenuUI
     */
    protected function addTab($title, $tooltip = '', $link = '#') {
      $this->items[] = new MenuUI_item($title, $tooltip, $link);
      return $this;
    }
    /**
     * @param        $title
     * @param string $tooltip
     * @param string $link
     * @param string $param_element element id to get extra paramater from
     * @param null   $target
     *
     * @return MenuUI
     */
    public function addLink($title, $tooltip = '', $link, $param_element, $target = null) {
      $this->items[]             = new MenuUI_item($title, $tooltip, $link, $param_element, $target);
      $this->options['hasLinks'] = true;
      return $this;
    }
    /**
     * @param        $title
     * @param string $tooltip
     * @param        $name
     * @param        $onselect
     *
     * @return MenuUI
     */
    public function addJSLink($title, $tooltip = '', $name, $onselect) {
      $this->items[]             = new MenuUI_item($title, $tooltip, '#' . $name);
      $this->options['hasLinks'] = true;
      $this->jslinks[]           = $onselect;
      return $this;
    }
    /**
     * @param      $title
     * @param      $tooltip
     * @param null $state
     *
     * @internal param string $link
     * @internal param string $style
     * @return MenuUI
     */
    public function startTab($title, $tooltip, $state = null) {
      $count = count($this->items);
      if ($state == null) {
        $state = $this->defaultState;
      }
      $this->items[]                       = new MenuUI_item($title, $tooltip, '#tabs' . MenuUI::$menuCount . '-' . $count, null, null, $state);
      $this->current_tab['attrs']['id']    = 'tabs' . MenuUI::$menuCount . '-' . $count;
      $this->current_tab['attrs']['class'] = 'ui-tabs-panel ui-widget-content';
      $this->current_tab['attrs']['style'] = ($count > 0 || $this->firstPage != $count) ? ' display:none;' : '';
      ob_start();
      return $this;
    }
    /**
     * @return MenuUI
     */
    public function endTab() {
      $this->current_tab['contents'] = ob_get_clean();
      $this->tabs[]                  = $this->current_tab;
      $this->current_tab             = [];
      return $this;
    }
    /**
     * @return void
     */
    public function render() {
      $menu              = new View('ui/menu');
      $menu['menuCount'] = MenuUI::$menuCount;
      $menu->set('items', $this->items);
      $menu->set('tabs', $this->tabs);
      $menu->render();
      $this->JS->tabs(MenuUI::$menuCount, $this->options, $this->firstPage);
      foreach ($this->jslinks as $js) {
        $this->JS->onload($js);
      }
      MenuUI::$menuCount++;
    }
  }
  /**

   */
  class MenuUI_item extends menu_item {
    /** @var string **/
    public $tooltip = '';
    /** @var null|string **/
    public $param_element = '';
    /** @var null|string **/
    public $target = '';
    /**
     * @param        $label
     * @param string $tooltip
     * @param string $link
     * @param null   $param_element
     * @param null   $target
     * @param string $state
     */
    public function __construct($label, $tooltip = '', $link = '#', $param_element = null, $target = null, $state = 'default') {
      $this->label   = $label;
      $this->liattrs = [
        'class' => 'ui-state-default ui-corner-top ui-state-' . $state,
      ];
      $this->attrs   = [
        'href'         => e($link),
        'title'        => $label,
        'tooltip'      => e($tooltip),
        'data-paramel' => $param_element,
        'target'       => $target
      ];
    }
  }
