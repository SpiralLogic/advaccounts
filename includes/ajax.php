<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
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

		public static function instance()
		{
			if (static::$_instance === null) {
				static::$_instance = new static;
			}
			return static::$_instance;
		}

		function __construct()
		{
			$this->JsHttpRequest($_SESSION['language']->encoding);
		}

		//
		//	This function is used in ctrl routines to activate
		//	update of ajaxified html element selected by given name/id.
		//
		function activate($trigname)
		{
			if (Ajax::in_ajax()) {
				$this->triggers[$trigname] = true;
			}
		}

		//
		//	Javascript clientside redirection.
		//	This is the last command added to reponse (if any).
		//
		function redirect($url)
		{
			if (Ajax::in_ajax()) {
				$this->_addCommand(true, array('n' => 'rd'), $this->absolute_url($url));
				$this->run();
			}
		}

		//
		// Popup window (target=_blank)
		//
		function popup($url)
		{
			$this->_addCommand(true, array('n' => 'pu'), $this->absolute_url($url));
		}

		//
		// Adds an executable Javascript code.
		//
		function addScript($trigger, $sJS)
		{
			$this->_addCommand($trigger, array('n' => 'js'), $sJS);
			return $this;
		}

		//
		// Assign target attribute with data.
		//
		function addAssign($trigger, $sTarget, $sAttribute, $sData)
		{
			$this->_addCommand($trigger, array('n' => 'as',
																				't'	=> $sTarget,
																				'p'	=> $sAttribute), $sData);
			return $this;
		}

		//
		// Updates input element or label with data.
		//
		function addUpdate($trigger, $sTarget, $sData)
		{
			$this->_addCommand($trigger, array('n' => 'up',
																				't'	=> $sTarget), $sData);
			return $this;
		}

		//
		// Set disable state of element.
		//
		function addDisable($trigger, $sTarget, $sData = true)
		{
			$this->_addCommand($trigger, array('n' => 'di',
																				't'	=> $sTarget), $sData);
			return $this;
		}

		//
		// Set state of element to enabled.
		//
		function addEnable($trigger, $sTarget, $sData = true)
		{
			$this->_addCommand($trigger, array('n' => 'di',
																				't'	=> $sTarget), !$sData);
			return $this;
		}

		//
		// Set current focus.
		//
		function addFocus($trigger, $sTarget)
		{
			$this->_addCommand($trigger, array('n' => 'fc'), $sTarget);
			return $this;
		}

		//
		// Internal procedure adding command to response.
		//
		function _addCommand($trigger, $aAttributes, $mData)
		{
			if ($this->isActive() && ($trigger !== false)) {
				//		Errors::error('adding '.$trigger.':'.htmlentities($mData));
				$aAttributes['why']  = $trigger;
				$aAttributes['data'] = $mData;
				$this->aCommands[]   = $aAttributes;
			}
		}

		/*
	 * Register binds function with ajax call parameter

	 function register($trigger, $function)
	 {
		 if (isset($_REQUEST[$trigger])) {
		 $function(&$this);
		 }
	 }
	 */
		function run()
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
					//			Errors::error('unset '.$com['t']);
				}
				else if ($com['n'] == 'up' && $com['t'] == '_page_body') {
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
			//		Errors::error('Activate:'.htmlentities(print_r($this->triggers, true)));
			//		Errors::error('Commands :'.htmlentities(print_r($this->aCommands, true)));
			$GLOBALS['_RESULT'] = $this->aCommands;
			//	    exit();
		}

		static function in_ajax()
		{
			$Ajax = Ajax::instance();
			return $Ajax->isActive();
		}

		// Returns absolute path of relative $url. To be used in ajax calls
		// for proper redirection from any referer page.
		//
		public function absolute_url($url)
		{
			return strpos($url, '..') === 0 ? dirname($_SERVER['PHP_SELF']) . '/' . $url : $url;
		}
	}


