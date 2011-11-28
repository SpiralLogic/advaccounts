<?php
	class HTML
	{
		protected static $_instance = null;
		protected static $_return = false;

		function __call($func, $args)
			{
				static::__callStatic($func, $args);
				return static::$_instance;
			}

		function __get($func)
			{
				static::__callStatic($func);
				return static::$_instance;
			}

		static function setReturn($state = null)
			{
				if (static::$_instance === null) {
					static::$_instance = new static;
				}
				static::$_return = ($state === null) ? !(static::$_return) : $state;
				if (!static::$_return) {
					return ob_get_clean();
				} else {
					ob_start();
				}
				return static::$_instance;
			}

		static function __callStatic($func, $args = array())
			{
				if (static::$_instance === null) {
					static::$_instance = new static;
				}
				(count($args) == 0) ? static::$_instance->_closeTag(($func[0] == '_') ? substr($func, 1) : $func)
				 : static::$_instance->_Builder($func, $args);
				return static::$_instance;
			}

		protected function _openTag($type, $attr = array(), $content = '')
			{
				$attrs = '';
				foreach ($attr as $key => $value) {
					$attrs .= (empty($value) || $key == 'content') ? '' : " $key='$value'";
				}
				echo	'<' . $type . ' ' . $attrs . '>' . (isset($attr['content']) ? $attr['content'] : $content);
			}

		protected function _closeTag($type)
			{
				echo '</' . $type . '>';
			}

		protected function _Builder($func, $args, $attr = array(), $content = '')
			{
				$open = (is_bool(end($args))) ? array_pop($args) : true;
				foreach ($args as $key => $val) {
					if ($key == 0 && is_string($val)) {
						$attr['id'] = $val;
					} elseif (!isset($attr['content']) && is_string($val)) {
						$content = $attr['content'] = $val;
					} elseif (is_array($val)) {
						$attr = array_merge($attr, $val);
					}
				}
				if (!$open) {
					if ($open === false) {
						$this->_openTag($func, $attr, $content);
					}
					$this->_closeTag($func);
				} else {
					$this->_openTag($func, $attr);
				}
			}
	}
