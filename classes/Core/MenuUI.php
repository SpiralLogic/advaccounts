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
  class MenuUI extends Menu
  {
    /**
     * @var array
     */
    protected $options = array();
    /**
     * @var bool
     */
    public $firstPage = 0;
    /**
     * @var int
     */
    public static $menuCount = 0;
    public $tabs = [];
    /** @var View */
    public $current_tab;
    /**
     * @param array $options
     */
    public function __construct($options = array()) {
      $this->options = $options;
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
      JS::onload($onselect);
      return $this;
    }
    /**
     * @param        $title
     * @param        $tooltip
     * @param string $link
     * @param string $style
     *
     * @return MenuUI
     */
    public function startTab($title, $tooltip, $link = '#', $style = '') {
      $count = count($this->items);
      $this->addTab($title, $tooltip, $link);
      $this->current_tab['id']    = 'tabs' . MenuUI::$menuCount . '-' . $count;
      $this->current_tab['class'] = ($count > 0 || $this->firstPage != $count) ? 'ui-tabs-hide' : '';
      $this->current_tab['style'] = $style;
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
      JS::tabs('tabs' . MenuUI::$menuCount, $this->options, $this->firstPage);
      MenuUI::$menuCount++;
    }
  }

  /**

   */
  class MenuUI_item extends menu_item
  {
    /**
     * @var string
     */
    public $tooltip = '';
    /**
     * @var null|string
     */
    public $param_element = '';
    /**
     * @var null|string
     */
    public $target = '';
    /**
     * @param        $label
     * @param string $tooltip
     * @param string $link
     * @param null   $param_element
     * @param null   $target
     */
    public function __construct($label, $tooltip = '', $link = '#', $param_element = null, $target = null) {
      $this->label         = $label;
      $this->link          = e($link);
      $this->tooltip       = e($tooltip);
      $this->param_element = $param_element;
      $this->target        = $target;
    }
  }
