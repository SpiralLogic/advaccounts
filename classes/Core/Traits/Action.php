<?php

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 22/10/12
   * Time: 5:24 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Traits;

  use ADV\Core\Input\Input;

  trait Action
  {
    protected $action = null;
    protected $actionID;
    /**
     * @param $prefix
     *
     * @return int|mixed
     */
    protected function getActionId($prefix) {
      if (!is_null($this->actionID)) {
        return $this->actionID;
      }
      if (is_null($this->action)) {
        $this->action = Input::_post('_action');
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
  }
