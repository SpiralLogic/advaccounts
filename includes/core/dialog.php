<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 6/12/10
	 * Time: 5:49 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Dialog
	{
		/**
		 * @var array
		 */
		protected $buttons = array();
		/**
		 * @var bool
		 */
		protected $name;
		/**
		 * @var string
		 */
		protected $contents = '';
		/**
		 * @var array
		 */
		protected $events = array();
		/**
		 * @var bool
		 */
		protected $data = false;
		/**
		 * @var array
		 */
		protected $options = array();
		/**
		 * @var bool
		 */
		protected $_template = false;

		/**
		 * @param $title
		 * @param bool $name
		 * @param bool $contents
		 * @param array $options
		 */
		function __construct($title, $name = false, $contents = false, $options = array()) {
			$this->name = $name;
			$this->title = ($title) ? $title : "Message Box";
			if ($contents) {
				$this->setContents($contents);
			}
			$this->options = array(
				'autoOpen' => false,
				'modal' => false,
				'width' => 300,
				'resizable' => true,
				'closeOnEscape' => true
			);
			$this->setOptions($options);
		}

		/**
		 * @param $contents
		 *
		 * @return mixed
		 */function setContents($contents) {
			if (empty($contents)) {
				return;
			}
			if (is_array($contents)) {
				foreach ($contents as $content) {
					$this->contents .= $content . "<hr>";
				}
				;
			} else {
				$this->contents = $contents;
			}
	}

		/**
		 * @param string $data
		 */public function setTemplateData($data = '') {
			$this->data = $data;
			$this->_template = true;
	}

		/**
		 * @param $selector
		 * @param string $type
		 */function addOpenEvent($selector, $type = 'click') {
			$this->events[] = array($selector, $type, "\$({$this->name}).dialog('open');");
	}

		/**
		 * @param $js
		 */function addBeforeClose($js) {
			$this->options['beforeClose'] = "function(event,ui){$js}";
	}

		/**
		 * @param array $buttons
		 */function addButtons($buttons = array()) {
			foreach ($buttons as $button => $action) {
				$this->addButton($button, $action);
			}
	}

		/**
		 * @param $button
		 * @param $action
		 */function addButton($button, $action) {
			$this->buttons[$button] = $action;
	}

		/**
		 * @param array $options
		 */function setOptions($options = array()) {
			if (is_array($options) && count($options) > 0) {
				$this->options = array_merge($this->options, $options);
			}
	}

		/**
		 *
		 */function show() {
			$buttons = '';
			if (count($this->buttons > 0)) {
				$buttons = '.dialog("option","buttons", [';
				foreach ($this->buttons as $button => $action) {
					$buttons .= '{ "text":"' . $button . '", click: function() { ' . $action . '}}, ';
				}
				$buttons .= ']);';
			}
			if ($this->_template) {
				$this->contents = '<script id="' . $this->name . '-template" type="text/x-jquery-tmpl">' . $this->contents . '</script>';
				echo '<div id="' . $this->name . '" title="' . $this->title . '">' . $this->contents . '</div>';
				$js = '$' . $this->name . '=$("#' . $this->name . '").dialog(' . JS::arrayToOptions($this->options) . ')' . $buttons;
			} else {
				JS::beforeload('$' . $this->name . " = $('" . '<div id="' . $this->name . '" title="' . $this->title . '">' . $this->contents
				 . '</div>)' . "');");
				$js = '$' . $this->name . '.dialog(' . JS::arrayToOptions($this->options) . ')' . $buttons;
			}
			if (!empty($js)) {
				JS::addEvents($this->events);
				JS::addLive($js);
				if ($this->_template) {
					$js = '$("#' . $this->name . '-template").template("' . $this->name . '"); Adv.o.' . $this->name . ' = { render: function(data) { $("#' . $this->name . '").empty().append($.tmpl("' . $this->name . '",data))} }; ';
					if (!empty($this->data)) {
						$js .= 'Adv.o.' . $this->name . '.render(' . json_encode($this->data) . ');';
					}
					JS::onload($js);
				}
			}
		}
	}
