<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 7/01/11
 * Time: 4:21 PM
 * To change this template use File | Settings | File Templates.
 */
   class status
   {


      protected $_status = Array('status' => true, 'process' => 'construct', 'message' => 'Initialised', 'var');

      /**
       * @param null $status
       * @param null $process
       * @param string $message
       * @param null $var
       * @return array
       */
      public function set($status = null, $process = null, $message = '', $var = null)
      {
         if ($status === null || $process === null) {
            $this->_status['status'] = false;
            $this->_status['process'] = 'status';
            $this->_status['message'] = 'Not enough parameters passed for status update.';
         } else {
            $this->_status['status'] = $status;
            $this->_status['process'] = $process;
            $this->_status['message'] = $message;
            if (!empty($var) && $var != null) {
               $this->_status['var'] = $var;
            }
         }
         return $this->_status['status'];
      }

      public function get()
      {
         return $this->_status;
      }

   }