<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 6/12/10
	 * Time: 5:47 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class UI extends HTML
	{
		static function button($id = false, $content = false, $attr = array())
		{
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

		static function select($id = false, $options = array(), $params = array())
		{
			HTML::setReturn(true)->select($id, $params);
			foreach ((array)$options as $label => $option) {
				if (is_array($option)) {
					HTML::optgroup(array('label' => $label));
					foreach ($option as $data) {
						HTML::option(null, $data[0] . ' (' . $data[1] . ')', array('value' => $data[1]), false);
					}
					HTML::optgroup();
				} else {
					HTML::option(null, $option, array('value' => $label), false);
				}
			}
			echo HTML::_select()->setReturn(false);
			return static::$_instance;
		}

		static function search($id = false, $attr = array(), $options = array())
		{
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
			if ($id && isset($attr['set'])) {
				$input_attr['data-set'] = $attr['set'];
			}
			HTML::input($id, $input_attr);
			if (!isset($attr['nodiv'])) {
				HTML::div();
			}
			$callback = (isset($attr['callback'])) ? $attr['callback'] : strtoupper($id[0]) . strtolower(substr($id, 1));
			JS::autocomplete($id, $callback, $url, $options);
			return static::$_instance;
		}

		static function searchLine($id, $url = '#', $options = array())
		{
			$defaults = array(
				'description' => false, 'disabled' => false, 'editable' => true, 'selected' => '', 'cells' => false, 'inactive' => false, 'purchase' => false, 'sale' => false, 'js' => '', 'submitonselect' => '', 'sales_type' => 1, 'no_sale' => false, 'select' => false, 'type' => 'local', 'where' => '');
			$o = array_merge($defaults, $options);
			$_SESSION['options'][$id] = $o;
			$desc_js = $o['js'];
			HTML::setReturn(true);
			if ($o['cells']) {
				HTML::td(true);
			}
			HTML::input($id, array(
				'value' => $o['selected'], 'name' => $id));
			if ($o['editable']) {
				HTML::label('lineedit', 'edit', array(
					'for' => 'stock_id', 'class' => 'stock button', 'style' => 'display:none'));
				$desc_js .= '$("#lineedit").data("stock_id",value.stock_id).show().parent().css("white-space","nowrap"); ';
			}
			if ($o['cells']) {
				HTML::td()->td(true);
			}
			$selectjs = '';
			if ($o['description'] !== false) {
				HTML::textarea('description', $o['description'], array(
					'name' => 'description', 'rows' => 1, 'cols' => 45), false);
				$desc_js .= "$('#description').css('height','auto').attr('rows',4);";
			} elseif ($o['submitonselect']) {
				$selectjs = <<<JS
				$(this).val(value.stock_id);
				$('form').trigger('submit'); return false;
JS;
			} else {
				$selectjs = <<<JS
				$(this).val(value.stock_id);return false;
JS;
			}
			if ($o['cells']) {
				HTML::td();
			}
			$js = <<<JS
	Adv.o.stock_id = \$$id = $("#$id").catcomplete({
				delay: 0,
				autoFocus: true,
				minLength: 0,
				source: function( request, response ) {
						if (Adv.lastXhr)  Adv.lastXhr.abort();
						Adv.loader.off();
						Adv.lastXhr = $.ajax({
								url: "$url",
								dataType: "json",
								data: {id: '$id',term: request.term},
								success: function( data,status,xhr ) {

								if ( xhr === Adv.lastXhr ) {
								if (!Adv.o.stock_id.data('active')) {
								var value = data[0];
								value.description = value.long_description;
								Adv.Events.onFocus("#stock_id",[0,Adv.o.stock_id.position().top]);
								$.each(value,function(k,v) {Adv.Forms.setFormValue(k,v);});
									$desc_js
								return false;
								}
										response($.map( data, function( item ) {
												return {
														label: item.stock_id+": "+item.description,
														value: item,
														category: item.category
												}
										}));
										Adv.loader.on();
								}}})
						},
            select: function( event, ui ) {
            var value = ui.item.value;
            $selectjs
											 value.description = value.long_description;
								Adv.Events.onFocus("#stock_id",[0,$(this).position().top]);
								$.each(value,function(k,v) {Adv.Forms.setFormValue(k,v);});
									$desc_js
								return false;
						},
						focus: function(){return false;},
						open: function() { $('.ui-autocomplete').unbind('mouseover');}
						}
				).blur(function() { $(this).data('active',false)}).focus(function() { $(this).data('active',true)});
JS;
			$clean = "\$$id.catcomplete('destroy');";
			JS::addLive($js, $clean);
			return HTML::setReturn(false);
		}

		public static function emailDialogue($contactType, $emailid)
		{
			static $generated = false;
			HTML::setReturn(true);
			UI::button(false, 'Email', array(
				'class' => 'button email-button', 'data-emailid' => $emailid));
			$content = HTML::setReturn(false);
			if ($generated) {
				return $content;
			}
			$emailBox = new Dialog('Select Email Address:', 'emailBox', '');
			$emailBox->addButtons(array('Close' => '$(this).dialog("close");'));
			$emailBox->setOptions(array(
				'autoopen' => false, 'modal' => true, 'width' => 500, 'height' => 350, 'resizeable' => false));
			$emailBox->show();
			$action = <<<JS
	      var emailID= $(this).data('emailid');
	      $.post('/contacts/emails.php',{type: '$contactType', id: emailID}, function(data) {
	         \$emailBox.html(data).dialog('open');

	   },'html');
	   return false;
JS;
			JS::addLiveEvent('.email-button', 'click', $action, 'wrapper', true);
			$generated = true;
			return $content;
		}
	}