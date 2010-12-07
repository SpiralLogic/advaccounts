<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:49 PM
 * To change this template use File | Settings | File Templates.
 */

class Dialog {


	protected $height;
	protected $width = 300;
	protected $buttons = array();
	protected $autoopen = false;
	protected $modal = false;
	protected $name;
	protected $resizable=true;
	protected $contents = '';

	function __construct($title, $name = false, $contents = false) {
		global $js_lib;
		$this->title = $title;
		$this->name = ($name) ? $name : "Message Box";
		if ($contents) {
			$this->setContents($contents);
		}
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

	function addButtons($buttons = array()) {
		foreach ($buttons as $button => $action) {
			$this->buttons[$button] = $action;
		}

	}

	function setOptions($options = array()) {
		if (is_array($options) && count($options) > 0) {
			foreach ($options as $option => $value) {
				if (property_exists($this, $option)) {
					$this->$option = $value;
				}
			}

		}

	}

	function show() {

		if (count($this->buttons > 0)) {
			$buttons = 'buttons: { ';
			foreach ($this->buttons as $button => $action) {
				$buttons .= '"' . $button . '": function() { ' . $action . '}, ';
			}
			$buttons .= '} ';
		}
		$js = '<div id="' . $this->name . '" title="' . $this->title . '">' . $this->contents . '</div><script>$(function () {$("#' . $this->name . '").dialog({';
		$js .= 'autoOpen: ' . (($this->autoopen) ? 'true' : 'false') . ',';
		if ($this->modal) 	$js .= 'modal: true, ';
		if ($this->width) $js .= 'width: '.$this->width.', ';
		if (!$this->resizable) $js .= 'resizable: false, ';
		if (!empty($buttons)) {
			$js .= $buttons;
		}

		$js .= '})
})
;
</script >';
		if (!empty($js)) {
			echo $js;
		}
	}

}
