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
  use \JsHttpRequest;

  /**
   * @method Ajax i()
   * @method Ajax activate($trigname)
   * @method Ajax redirect($url)
   * @method Ajax popup($url)
   * @method Ajax addAssign($trigger, $sTarget, $sAttribute, $sData)
   * @method Ajax addUpdate($trigger, $sTarget, $sData)
   * @method Ajax addEnable($trigger, $sTarget, $sData = true)
   * @method Ajax addDisable($trigger, $sTarget, $sData = true)
   * @method Ajax addFocus($trigger, $sTarget)
   * @method Ajax run()
   * @method Ajax flush()
   * @method Ajax inAjax()
   * @method Ajax absoluteURL()
   */
  class Ajax extends \JsHttpRequest
  {
    use Traits\StaticAccess;

    /**
     * @var array
     */
    protected $aCommands = array();
    /**
     * @var array
     */
    protected $triggers = array();
    /**

     */
    public function __construct()
    {
      $enc = (session_status() == PHP_SESSION_ACTIVE) ? $_SESSION['language']->encoding : 'UTF-8';
      parent::__construct($enc);
    }
    /**
     *   This function is used in ctrl routines to activate
     *   update of ajaxified html element selected by given name/id.
     *
     * @param $trigname
     *
     * @return void
     */
    public function _activate($trigname)
    {
      ($this->_inAjax()) and $this->triggers[$trigname] = true;
    }
    /**
     *   Javascript clientside redirection.
     *   This is the last command added to reponse (if any).
     *
     * @param $url
     *
     * @return void
     */
    public function _redirect($url)
    {
      if ($this->_inAjax()) {
        $this->addCommand(true, array('n' => 'rd'), $this->_absoluteURL($url));
        $this->_run();
      }
    }
    /**
     * Popup window (target=_blank)
     *
     * @param $url
     *
     * @return void
     */
    public function _popup($url)
    {
      $this->addCommand(true, array('n' => 'pu'), $this->_absoluteURL($url));
    }
    /**
     * Adds an executable Javascript code.
     *
     * @param $trigger
     * @param $sJS
     *
     * @return Ajax
     */
    public function _addScript($trigger, $sJS)
    {
      $this->addCommand($trigger, array('n' => 'js'), $sJS);
      return $this;
    }
    /**
     * Assign target attribute with data.
     *
     * @param $trigger
     * @param $sTarget
     * @param $sAttribute
     * @param $sData
     *
     * @return Ajax
     */
    public function _addAssign($trigger, $sTarget, $sAttribute, $sData)
    {
      $this->addCommand($trigger, array(
                                       'n' => 'as', 't' => $sTarget, 'p' => $sAttribute
                                  ), $sData);
      return $this;
    }
    /**
     * Updates input element or label with data.
     *
     * @param $trigger
     * @param $sTarget
     * @param $sData
     *
     * @return Ajax
     */
    public function _addUpdate($trigger, $sTarget, $sData)
    {
      $this->addCommand($trigger, array(
                                       'n' => 'up', 't' => $sTarget
                                  ), $sData);
      return $this;
    }
    /**
     * Set disable state of element.
     *
     * @param      $trigger
     * @param      $sTarget
     * @param bool $sData
     *
     * @return Ajax
     */
    public function _addDisable($trigger, $sTarget, $sData = true)
    {
      $this->addCommand($trigger, array(
                                       'n' => 'di', 't' => $sTarget
                                  ), $sData);
      return $this;
    }
    /**
     * Set state of element to enabled.
     *
     * @param      $trigger
     * @param      $sTarget
     * @param bool $sData
     *
     * @return Ajax
     */
    public function _addEnable($trigger, $sTarget, $sData = true)
    {
      $this->addCommand($trigger, array(
                                       'n' => 'di', 't' => $sTarget
                                  ), !$sData);
      return $this;
    }
    /**
     * Set current focus.
     *
     * @param $trigger
     * @param $sTarget
     *
     * @return Ajax
     */
    public function _addFocus($trigger, $sTarget)
    {
      $this->addCommand($trigger, array('n' => 'fc'), $sTarget);
      return $this;
    }
    /**
     * Internal procedure adding command to response.
     *
     * @param $trigger
     * @param $aAttributes
     * @param $mData
     *
     * @return void
     */
    protected function addCommand($trigger, $aAttributes, $mData)
    {
      if ($this->isActive() && ($trigger !== false)) {
        //		Event::error('adding '.$trigger.':'.htmlentities($mData));
        $aAttributes['why']  = $trigger;
        $aAttributes['data'] = $mData;
        $this->aCommands[]   = $aAttributes;
      }
    }
    /**
     * @return mixed
     */
    public function _run()
    {
      if (!$this->isActive()) {
        return;
      }
      // remove not active commands
      foreach ($this->aCommands as $idx => $com) {
        // If we should reload whole page content ignore all commands but the update.
        // This is page repost equivalent, although header and footer are not reloaded.
        if ($com['why'] !== true && !isset($this->triggers[$com['why']])) {
          unset($this->aCommands[$idx]);
          //			Event::error('unset '.$com['t']);
        } else {
          if ($com['n'] == 'up' && $com['t'] == '_page_body') {
            $cmds = array($com);
            foreach ($this->aCommands as $cmd) {
              if ($cmd['n'] == 'fc') { // save focus
                $cmds[] = $cmd;
                break;
              }
            }
            $this->aCommands = $cmds;
            break;
          }
        }
      }
      //		Event::error('Activate:'.htmlentities(print_r($this->triggers, true)));
      //		Event::error('Commands :'.htmlentities(print_r($this->aCommands, true)));
      $GLOBALS['_RESULT'] = $this->aCommands;
    }
    /**
     * @static
     * @return bool
     */
    public function _inAjax()
    {
      return $this->isActive();
    }
    /**
     * @static
     * @return bool
     */
    public function _flush()
    {
      $this->aCommands = [];
    }
    /**
     * Returns absolute path of relative $url. To be used in ajax calls
     * for proper redirection from any referer page.
     *
     * @param $url
     *
     * @return string
     */
    public function _absoluteURL($url)
    {
      return strpos($url, '..') === 0 ? dirname($_SERVER['DOCUMENT_URI']) . '/' . $url : str_replace(WEBROOT, '/', $url);
    }
  }
