<?php
    /**
     * Created by JetBrains PhpStorm.
     * User: Complex
     * Date: 8/07/12
     * Time: 4:50 AM
     * To change this template use File | Settings | File Templates.
     */
    namespace ADV\App\Application;

    /**

     */
    class Module
    {
        /** @var */
        public $name;
        /**
         * @var null
         */
        public $icon;
        /**
         * @var array
         */
        public $leftAppFunctions = [];
        /**
         * @var array
         */
        public $rightAppFunctions = [];
      /**
         * @param      $name
         * @param null $icon
         */
        public function __construct($name, $icon = null)
        {
            $this->name              = $name;
            $this->icon              = $icon;
            $this->leftAppFunctions  = [];
            $this->rightAppFunctions = [];
        }
        /**
         * @param        $label
         * @param string $link
         * @param string $access
         *
         * @return Func
         */
        public function addLeftFunction($label, $link = "", $access = SA_OPEN)
        {
            $appfunction              = new Func($label, $link, $access);
            $this->leftAppFunctions[] = $appfunction;
            return $appfunction;
        }
        /**
         * @param        $label
         * @param string $link
         * @param string $access
         *
         * @return Func
         */
        public function addRightFunction($label, $link = "", $access = SA_OPEN)
        {
            $appfunction               = new Func($label, $link, $access);
            $this->rightAppFunctions[] = $appfunction;
            return $appfunction;
        }

    }
