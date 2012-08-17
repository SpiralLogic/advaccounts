<?php

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 19/04/12
   * Time: 12:08 PM
   * To change this template use File | Settings | File Templates.
   */
  namespace ADV\Core\Traits;
  /**

   */
  trait Status
  {

    /** @var \ADV\Core\Status */
    protected $status = null;
    /**
     * @param bool $string return status as string if true and as array if false
     *
     * @return string|array
     */
    public function getStatus($string = false)
    {
      if ($string) {
        return $this->status;
      }

      return $this->status->get();
    }
    /***
     * @param null   $status
     * @param null   $process
     * @param string $message
     * @param null   $var
     *
     * @return Status|bool
     */
    protected function status($status = null, $process = null, $message = '', $var = null)
    {
      if (!$this->status) {
        $this->status = new \ADV\Core\Status($status, $process, $message, $var);

        return $status;
      }

      return $this->status->set($status, $process, $message, $var);
    }
  }
