<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 24/07/11
 * Time: 1:16 PM
 * To change this template use File | Settings | File Templates.
 */

   class DatePicker
   {


      protected $options;
      protected $name;

      static function add($name, $options = array())
      {
         $js = '$("#' . $name . '").datepicker(' . JS::arrayToOptions($options) . ');';

         JS::addLive($js);
      }

   }
