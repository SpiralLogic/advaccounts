<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	/**
	 * @global
	 */
	class Ajax extends JsHttpRequest
	{
		public $aCommands = array();
		public $triggers = array();
		protected static $_instance = null;

		/***
		 * @static
		 * @return Ajax
		 *
		 * Returns Ajax Instance
		 */
		public static function i() {
			if (static::$_instance === null) {
				static::$_instance = new static;
			}
			return static::$_instance;
		}

		/**
		 *
		 */
		function __construct() {
			parent::__construct($_SESSION['Language']->encoding);
		}
		/**
		*
		* 	This function is used in ctrl routines to activate
		* 	update of ajaxified html element selected by given name/id.
		*

		 * @param $trigname
		 */
		function activate($trigname) {
			if (Ajax::in_ajax()) {
				$this->triggers[$trigname] = true;
			}
		}
		/**
		*
		* 	Javascript clientside redirection.
		* 	This is the last command added to reponse (if any).
		*

		 * @param $url
		 */
		function redirect($url) {
			if (Ajax::in_ajax()) {
				$this->_addCommand(true, array('n' => 'rd'), $this->absolute_url($url));
				$this->run();
			}
		}
		/**
		*
		*  Popup window (target=_blank)
		*

		 * @param $url
		 */
		function popup($url) {
			$this->_addCommand(true, array('n' => 'pu'), $this->absolute_url($url));
		}
		/**
		*
		*  Adds an executable Javascript code.
		*

		 * @param $trigger
		 * @param $sJS
		 *
		 * @return Ajax
		 */
		function addScript($trigger, $sJS) {
			$this->_addCommand($trigger, array('n' => 'js'), $sJS);
			return $this;
		}
		/**
		*
		*  Assign target attribute with data.
		*

		 * @param $trigger
		 * @param $sTarget
		 * @param $sAttribute
		 * @param $sData
		 *
		 * @return Ajax
		 */
		function addAssign($trigger, $sTarget, $sAttribute, $sData) {
			$this->_addCommand($trigger, array(
																				'n' => 'as', 't' => $sTarget, 'p' => $sAttribute), $sData);
			return $this;
		}
		/**
		*
		*  Updates input element or label with data.
		*

		 * @param $trigger
		 * @param $sTarget
		 * @param $sData
		 *
		 * @return Ajax
		 */
		function addUpdate($trigger, $sTarget, $sData) {
			$this->_addCommand($trigger, array(
																				'n' => 'up', 't' => $sTarget), $sData);
			return $this;
		}
		/**
		*
		*  Set disable state of element.
		*

		 * @param			$trigger
		 * @param			$sTarget
		 * @param bool $sData
		 *
		 * @return Ajax
		 */
		function addDisable($trigger, $sTarget, $sData = true) {
			$this->_addCommand($trigger, array(
																				'n' => 'di', 't' => $sTarget), $sData);
			return $this;
		}
		/**
		*
		*  Set state of element to enabled.
		*

		 * @param			$trigger
		 * @param			$sTarget
		 * @param bool $sData
		 *
		 * @return Ajax
		 */
		function addEnable($trigger, $sTarget, $sData = true) {
			$this->_addCommand($trigger, array(
																				'n' => 'di', 't' => $sTarget), !$sData);
			return $this;
		}
		/**
		*
		*  Set current focus.
		*

		 * @param $trigger
		 * @param $sTarget
		 *
		 * @return Ajax
		 */
		function addFocus($trigger, $sTarget) {
			$this->_addCommand($trigger, array('n' => 'fc'), $sTarget);
			return $this;
		}
		/**
		*
		*  Internal procedure adding command to response.
		*

		 * @param $trigger
		 * @param $aAttributes
		 * @param $mData
		 */
		function _addCommand($trigger, $aAttributes, $mData) {
			if ($this->isActive() && ($trigger !== false)) {
				//		Errors::error('adding '.$trigger.':'.htmlentities($mData));
				$aAttributes['why'] = $trigger;
				$aAttributes['data'] = $mData;
				$this->aCommands[] = $aAttributes;
			}
		}

		/**
		 * @return mixed
		 */
		function run() {
			if (!$this->isActive()) {
				return;
			}
			// remove not active commands
			foreach ($this->aCommands as $idx => $com) {
				// If we should reload whole page content ignore all commands but the update.
				// This is page repost equivalent, although header and footer are not reloaded.
				if ($com['why'] !== true && !isset($this->triggers[$com['why']])) {
					unset($this->aCommands[$idx]);
					//			Errors::error('unset '.$com['t']);
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
			//		Errors::error('Activate:'.htmlentities(print_r($this->triggers, true)));
			//		Errors::error('Commands :'.htmlentities(print_r($this->aCommands, true)));
			$GLOBALS['_RESULT'] = $this->aCommands;
			//	    exit();
		}

		/**
		 * @static
		 * @return bool
		 */
		static function in_ajax() {
			return static::i()->isActive();
		}
		/**
		*  Returns absolute path of relative $url. To be used in ajax calls
		*  for proper redirection from any referer page.
		*

		 * @param $url
		 *
		 * @return string
		 */
		public function absolute_url($url) {
			return strpos($url, '..') === 0 ? dirname($_SERVER['PHP_SELF']) . '/' . $url : $url;
		}
	}


