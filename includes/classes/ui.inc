<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:47 PM
 * To change this template use File | Settings | File Templates.
 */
	HTML::load();

	class UI extends HTML {

		static function button($id = false, $content = false, $attr = array()) {
			if ($id) {
				$attr['id'] = $id;
			}
			if (empty($attr['name']) && ($id)) {
				$attr['name'] = $id;
			}
			if (!isset($attr['class'])) {
				$attr['class'] = 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only';
			}

			HTML::button($id, $content, $attr, false);
			return static::$_instance;
		}

		static function select($id = false, $options = array(), $params = array()) {
			$content = '';
			HTML::setReturn(true)->select($id, $params);
			foreach ((array)$options as $label => $option) {
				if (is_array($option)) {
					HTML::optgroup(array('label' => $label));
					foreach ($option as $data) {
						HTML::option(null, $data[0] . ' (' . $data[1] . ')', array('value' => $data[1]), false);
					}
					HTML::optgroup();
				} else {
					HTML::option(array('value' => $label), false);
				}
			}

			echo HTML::_select()->setReturn(false);
			return static::$_instance;
		}

		static function search($id = false, $attr = array(), $options = array()) {
			if ($id) {
				$attr['id'] = $id;
			}
			$url = (!empty($attr['url'])) ? $attr['url'] : false;
			if (!isset($attr['nodiv'])) {
				HTML::div(array('class' => 'ui-widget'));
			}
			if ($id && isset($attr['label'])) {
				HTML::label(array('for' => $id), $attr['label'], false);
			}

			if ($id && isset($attr['size'])) {
				$input_attr['size'] = $attr['size'];
			}
			if ($id && isset($attr['name'])) {
				$input_attr['name'] = $attr['name'];
			}

			HTML::input($id, $input_attr);
			if (!isset($attr['nodiv'])) {
				HTML::div();
			}
			$callback = (isset($attr['callback'])) ? $attr['callback'] : strtoupper($id[0]) . strtolower(substr($id, 1));
			JS::autocomplete($id, $callback, $url, $options);
			return static::$_instance;
		}

		static function searchLine($id, $url = '#', $options = array()) {
			$defaults =
					array('description' => false,
					      'disabled' => false,
					      'editable' => true,
					      'selected' => '',
					      'cell' => false,
					      'inactive' => false,
					      'purchase' => false,
					      'sale' => false,
					      'js' => '',
					      'sales_type' => 1,
					      'no_sale' => false,
					      'select' => false,
					      'type' => 'local',
					      'where' => '');

			$o = array_merge($defaults, $options);
			$_SESSION['options'][$id] = $o;
			HTML::setReturn(true);
			if ($o['cell']) {
				HTML::td(true);
			}

			HTML::input($id, $o['selected'], array('name' => $id, 'size' => 50));
			if ($o['cell']) {
				HTML::td()->td(true);
			}

			$desc_js = $o['js'];

			if ($o['description'] !== false) {
				HTML::textarea('description', $o['description'], array('style' => 'height:15px;', 'name' => 'description', 'rows' => 2, 'cols' => 45), false);
				$desc_js .= "$('#description').css('height','auto').attr('rows',4);";
			}

			if ($o['cell']) {
				HTML::td();
			}

			$js = <<<JS
	\$$id = $("#$id").catcomplete({
				delay: 0,
				autoFocus: true,
				minLength: 0,
				source: function( request, response ) {
						Adv.lastXhr = $.ajax({
								url: "$url",
								dataType: "json",
								data: {id: '$id',term: request.term},
								success: function( data,status,xhr ) {
								if ( xhr === Adv.lastXhr ) {
										response($.map( data, function( item ) {
												return {
														label: item.stock_id+": "+item.description,
														value: item,
														category: item.category
												}
										}))
								}}})
						},
            select: function( event, ui ) {
								var value = ui.item.value;
								$(this).val(value.stock_id);
								value.description = value.long_description;
								$.each(value,function(k,v) {Adv.setFormValue(k,v);});
								$desc_js
								return false;
						},
						focus: function(){return false;}}
				);
	return function () {return \$$id.catcomplete('destroy').focus() };
JS;
			JS::addLive($js);

			return HTML::setReturn(false);
		}

		public static function emailDialogue($contactType, $emailid) {
			static $generated = false;
			HTML::setReturn(true);
			UI::button(false, 'Email', array('class' => 'button email-button', 'data-emailid' => $emailid));
			$content = HTML::setReturn(false);
			if ($generated) {
				return $content;
			}
			$emailBox = new Dialog('Select Email Address:', 'emailBox', '');
			$emailBox->addButtons(array('Close' => '$("#emailBox").dialog("close");'));
			$emailBox->setOptions(array('autoopen' => false, 'modal' => true, 'width' => '"500"', 'height' => '"350"', 'resizeable' => false));
			$emailBox->show();

			$action = <<<JS
	      var emailID= $(this).data('emailid');
	      $.post('/contacts/emails.php',{type: '$contactType', id: emailID}, function(data) {
	         \$emailBox.html(data).dialog('open');

	   },'html');
	   return false;
JS;
			JS::addLiveEvent('.email-button', 'click', $action, '#wrapper');
			$generated = true;
			return $content;
		}
	}