var Adv;
jQuery.widget("custom.catcomplete", $.ui.autocomplete, {
	_renderMenu:function (ul, items) {
		var self = this, currentCategory = "";
		$.each(items, function (index, item) {
			if (item.category != currentCategory)
				{
					ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
					currentCategory = item.category;
				}
			self._renderItem(ul, item);
		});
	}
});
jQuery.fn.quickEach = (function () {
	var jq = jQuery([1]);
	return function (c) {
		var i = -1,
		 el, len = this.length;
		try
			{
				while (++i < len && (el = jq[0] = this[i]) && c.call(jq, i, el) !== false)
					{
						;
					}
			} catch (e)
			{
				delete jq[0];
				throw e;
			}
		delete jq[0];
		return this;
	};
}());
(function (window, $, undefined) {
	//noinspection LocalVariableNamingConventionJS
	var Adv = {
		$content:$("#content"),
		loader:document.getElementById('ajaxmark'),
		fieldsChanged:0,
		debug:{ ajax:true},
		lastXhr:'',
		o:{tabs:{}}
	};
	(function () {
		var extender = jQuery.extend;
		this.o.wrapper = $("#wrapper");
		this.o.autocomplete = {};
		$(this.loader)
		 .ajaxStart(function () {
			 Adv.loader.on();
			 Adv.hideStatus();
			 if (Adv.debug.ajax) console.time('ajax')
		 })
		 .ajaxStop(function () {
			 Adv.loader.off();
			 if (Adv.debug.ajax) console.timeEnd('ajax');
		 });
		this.extend = function (object) {extender(Adv, object)};
		extender(Adv.loader, {
			tout:15000,
			off:function () {
				Adv.loader.style.visibility = 'hidden';
			},
			on:function (img) {
				Adv.loader.tout = Adv.loader.tout | 15000;	// default timeout value
				img = Adv.loader.tout > 60000 ? 'progressbar.gif' : 'ajax-loader.gif';
				if (img) Adv.loader.src = user.theme + 'images/' + img;
				Adv.loader.style.visibility = 'visible';
			}
		})
	}).apply(Adv);
	window.Adv = Adv;
})(window, jQuery);
Adv.extend({
	msgbox:$('#msgbox').ajaxError(
	 function (event, request, settings) {
		 if (request.statusText == "abort") return;
		 var status = {
			 status:false,
			 message:"Request failed: " + settings.url + "<br>"
		 };
		 Adv.showStatus(status);
	 }).ajaxComplete(function (event, request) {
		 Behaviour.apply();
		 try
			 {
				 var data = $.parseJSON(request.responseText);
				 (data && data.status) ? Adv.showStatus(data.status) : Adv.hideStatus();
			 }
		 catch (e)
			 { Adv.hideStatus()}
	 }),
	showStatus:function (status) {
		Adv.msgbox.empty();
		if (status.status === 'redirect') return window.location.href = status.message;
		status.class = (status.status) ? 'note_msg' : 'err_msg';
		Adv.msgbox.html('<div class="' + status.class + '">' + status.message + '</div>');
		window.scrollTo(0, element_pos(Adv.msgbox[0]).y - 40)
	},
	hideStatus:function () {
		Adv.msgbox.removeClass().empty();
	},
	openWindow:function (url, title, width, height) {
		var left = (screen.width - width) / 2;
		var top = (screen.height - height) / 2;
		return window.open(url, title,
		 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',screenX=' + left + ',screenY=' + top + ',status=no,scrollbars=yes');
	},
	hoverWindow:{
		_init:false, width:600, height:600, init:function (width, height) {
			Adv.hoverWindow.width = width;
			Adv.hoverWindow.height = height;
			if (Adv.hoverWindow._init)
				{return;}
			Adv.hoverWindow._init = true;
			Adv.o.wrapper.off('click.open mouseenter.open').on('click.open mouseenter.open mouseleave.open', 'div .openWindow,td .openWindow',
			 function (e) {
				 if (e.type == 'click')
					 {
						 Adv.openWindow(this.href, this.target, Adv.hoverWindow.width, Adv.hoverWindow.height);
						 return false;
					 }
				 if (e.type == 'mouseenter')
					 {

						 if (Adv.o.popupCurrent) window.clearTimeout(Adv.o.popupCurrent);
						 Adv.o.popupEl = this;
						 Adv.o.popupParent = $(this).parent();
						 Adv.o.popupCurrent = window.setTimeout(Adv.popupWindow, 750);
					 }
				 if (e.type == 'mouseleave')
					 {
						 window.clearTimeout(Adv.o.popupCurrent);
					 }

			 })
		}},
	popupWindow:function () {
		if (Adv.o.order_details)
			{Adv.o.order_details.parent().remove();}
		var my = "left center", at = "right top";
		if (Adv.o.popupParent.is('div'))
			{
				my = "center center";
				at = "center top";
			}
		Adv.o.order_details = $("<iframe>",
		 {src:Adv.o.popupEl.href + '&frame=1', width:Adv.hoverWindow.width, height:Adv.hoverWindow.height, onload:'Adv.o.order_details.show()'})
		 .css({background:'white'}).hide();
		$('<div>', {id:'iframePopup', width:Adv.hoverWindow.width, height:Adv.hoverWindow.height}).html(Adv.o.order_details)
		 .position({my:my, at:at, of:Adv.o.popupParent}).css('top', 20)
		 .on('mouseleave',
		 function () {
			 $(this).remove();
		 }).appendTo(Adv.o.wrapper);
	}


});
Adv.extend({Forms:(function () {
	if (0 < document.getElementsByClassName('datepicker').length)
		{
			Adv.o.wrapper.on('focus', ".datepicker",
			 function (event) { $(this).datepicker({numberOfMonths:3, showButtonPanel:true, showCurrentAtPos:2, dateFormat:'dd/mm/yy'}).focus(); });
		}
	var _setFormValue = function (el, value, disabled) {
		if (!el)
			{return;}
		if (typeof disabled === 'boolean')
			{
				el.disabled = disabled;
			}
		if (el.tagName === 'select')
			{
				if (el.value === null || String(value).length === 0)
					{
						$(el).find('option:first').prop('selected', true)
						 .data('init', value);
						return;
					}
			}
		if (el.type === 'checkbox')
			{
				el.checked = !!value;
			}
		if (0 === String(value).length)
			{
				value = '';
			}
		el.value = value;
		$(el).data('init', value);
	};
	return {
		setFormValue:function (id, value, disabled) {
			var els = document.getElementsByName ? document.getElementsByName(id) : $("[name='" + id + "'");
			if (!els.length)
				{
					els = document.getElementById(id);
					return _setFormValue(els, value, disabled);
				}
			$.each(els, function (k, el) {
				_setFormValue(el, value, disabled);
			})
		},
		autocomplete:function (id, url, callback) {
			Adv.o.autocomplete[id] = $('#' + id)
			 .autocomplete({
				 autoFocus:true,
				 minLength:1,
				 delay:200,
				 source:function (request, response) {
					 var $this = Adv.o.autocomplete[id];
					 $this.data('default', null);
					 if ($this.data().autocomplete.previous == $this.val())
						 {return false;}
					 Adv.loader.off();
					 Adv.lastXhr = $.getJSON(url, request, function (data, status, xhr) {
						 Adv.loader.on();
						 if (!$this.data('active'))
							 {
								 if (data.length === 0)
									 {
										 data = [
											 {id:0, value:''}
										 ]
									 }
								 callback(data[0]);
								 return false;
							 }
						 $this.data('default', data[0]);
						 response(data);
					 });
				 },
				 select:function (event, ui) {
					 Adv.o.autocomplete[id].data('default', null);
					 if (callback(ui.item, event, this) === false)
						 {return false;}
				 },
				 focus:function () {return false;}
			 })
			 .blur(function () {$(this).data('active', false); })
			 .bind('autocompleteclose',
			 function () {
				 var $this = $(this);
				 if (this.value.length > 1 && $this.data().autocomplete.selectedItem === null && $this.data()['default'] !== null)
					 {
						 $this.val($this.data()['default'].label);
						 callback($this.data()['default'])
					 }
				 $this.data('default', null)
			 })
			 .focus(
			 function () { $(this).data('active', true)}).css({'z-index':'2'})
		}
	}
})()});
Adv.extend({
	Events:(function () {
		var events = [], onload = false, toClean = false, toFocus = {}, firstBind = function (s, t, a) {
			$(s).bind(t, a);
		};
		return {
			bind:function (selector, types, action) {
				events[events.length] = {s:selector, t:types, a:action};
				firstBind(selector, types, action);
			},
			onload:function (actions, clean) {
				var c = !!onload;
				onload = actions;
				if (c)
					{return;}
				onload();
				if (clean !== undefined)
					{
						toClean = clean;
					}
			},
			rebind:function () {
				if (toClean)
					{toClean();}
				if (onload)
					{onload();}
				$.each(events, function (k, v) {
					firstBind(v.s, v.t, v.a);
				});
				if (Adv.msgbox.children().length)
					{toFocus.pos = [0, Adv.msgbox.position().top];}
				if (toFocus.el)
					{$(toFocus.el).focus();}
				if (toFocus.pos)
					{scrollTo(toFocus.pos[0], toFocus.pos[1]);}
				toFocus = {el:false, pos:false};
			},
			onFocus:function (el, pos) {
				toFocus = {el:el, pos:pos};
			},
			onLeave:function (msg) {
				if (!msg)
					{
						window.onbeforeunload = function () {
							return null;
						};
					} else
					{
						window.onbeforeunload = function () {
							return msg;
						};
					}
			}
		}
	}())
});

