<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 6/12/10
	 * Time: 5:49 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Dialog {

		protected $buttons = array();
		protected $name;
		protected $contents = '';
		protected $events = array();
		protected $data = false;
		protected $options = array();
		protected $_template = false;

		function __construct($title, $name = false, $contents = false, $options = array()) {
			$this->name = $name;
			$this->title = ($title) ? $title : "Message Box";
			if ($contents) {
				$this->setContents($contents);
			}
			$this->options = array('autoOpen' => false,
				'modal' => false,
				'width' => 300,
				'resizable' => true,
				'closeOnEscape'=>true
			);
			$this->setOptions($options);
		}

		function setContents($contents) {
			if (empty($contents)) {
				return false;
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

		public function setTemplateData($data = '') {
			$this->data = $data;
			$this->_template = true;
		}

		function addOpenEvent($selector, $type = 'click') {
			$this->events[] = array($selector, $type, "\$({$this->name}).dialog('open');");
		}

		function addBeforeClose($js) {
			$this->options['beforeClose'] = 'function(event,ui){$js}';
		}

		function addButtons($buttons = array()) {
			foreach ($buttons as $button => $action) {
				$this->addButton($button, $action);
			}
		}

		function addButton($button, $action) {
			$this->buttons[$button] = $action;
		}

		function setOptions($options = array()) {
			if (is_array($options) && count($options) > 0) {
				$this->options = array_merge($this->options, $options);
			}
		}

		function show() {

			$buttons = '';
			if (count($this->buttons > 0)) {
				$buttons = '.dialog("option","buttons", [';
				foreach ($this->buttons as $button => $action) {
					$buttons .= '{ "text":"' . $button . '", click: function() { ' . $action . '}}, ';
				}
				$buttons .= ']);';
			}

			if ($this->_template) $this->contents = '<script id="' . $this->name . '-template" type="text/x-jquery-tmpl">' . $this->contents . '</script>';
			echo '<div id="' . $this->name . '" title="' . $this->title . '">' . $this->contents . '</div>';

			$js = '$' . $this->name . '=$("#' . $this->name . '").dialog(' . JS::arrayToOptions($this->options) . ')' . $buttons;
			if (!empty($js)) {
				JS::addEvents($this->events);
				JS::addLive($js);
				if ($this->_template) {
					$js = '$("#' . $this->name . '-template").template("' . $this->name . '"); Adv.o.' . $this->name . ' = { render: function(data) { $("#' . $this->name . '").empty().append($.tmpl("' . $this->name . '",data))} }; ';
					if (!empty($this->data)) $js .= 'Adv.o.' . $this->name . '.render(' . json_encode($this->data) . ');';
					JS::onload($js);
				}
			}
		}
	}
