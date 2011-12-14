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
	var Adv = {
		$content:$("#content"),
		loader:$("<div/>").attr('id', 'loader'),
		fieldsChanged:0,
		debug:{ ajax:true},
		lastXhr:'',
		o:{}
	};
	(function () {
		var extender = jQuery.extend;
		this.o.wrapper = $("#wrapper");
		this.o.autocomplete = {};
		this.loader.prependTo(Adv.$content).hide()
		 .ajaxStart(function () {
			 if (!Adv.loader.disabled) $(this).show();
			 Adv.hideStatus();
			 if (Adv.debug.ajax) console.time('ajax')
		 })
		 .ajaxStop(function () {
			 if (Adv.debug.ajax) console.timeEnd('ajax');
			 Adv.loader.hide()
		 });
		this.extend = function (object) {extender(Adv, object)};
		extender(Adv.loader, {
			disabled:false,
			off:function () {
				this.disabled = true;
			},
			on:function () {
				this.disabled = false;
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
		status.class = (status.status) ? 'note_msg' : 'err_msg';
		Adv.msgbox.html('<div class="'+status.class+'">'+status.message+'</div').show();
	},
	hideStatus:function () {
		Adv.msgbox.empty();
	},
	openWindow:function (url, title, width, height) {
		var left = (screen.width - width) / 2;
		var top = (screen.height - height) / 2;
		return window.open(url, title,
		 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',screenX=' + left + ',screenY=' + top + ',status=no,scrollbars=yes');
	}
})
Adv.extend({Forms:(function () {
	return {
		setFormValue:function (id, value, disabled) {
			var els = document.getElementsByName(id);
			if (!els.length)
				{
					els = [document.getElementById(id)];
				}
			$.each(els, function (k, el) {
				 if (!el) return;
				 if (typeof disabled === 'boolean')
					 {
						 el.disabled = disabled;
					 }
				 if (el.tagName === 'select')
					 {
						 if (el.value == null || String(value).length == 0)
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
				 if (String(value).length == 0)
					 {
						 value = '';
					 }
				 el.value = value;
				 $(el).data('init', value);
			 }
			)
		},
		autocomplete:function (id, url, callback) {
			Adv.o.autocomplete[id] = $('#'+id).autocomplete({
				autoFocus:true,
				source:function (request, response) {
					var lastXhr = $.getJSON(url, request, function (data, status, xhr) {
						if (xhr === lastXhr)
							{
								response(data);
							}
					});
				},
				select:function (event, ui) {
					if (callback(ui.item, event, this) === false) return false;
				}
			}).css({'z-index':'2'}).bind('paste', function () {
				 console.log(id.val());
				 id.autocomplete('search', id.val())
			 });
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
				if (c) return;
				onload();
				if (clean !== undefined)
					{
						toClean = clean;
					}
			},
			rebind:function () {
				if (toClean) toClean();
				if (onload)	onload();
				$.each(events, function (k, v) {
					firstBind(v.s, v.t, v.a);
				});
				if (toFocus.el) $(toFocus.el).focus();
				if (toFocus.pos) scrollTo(toFocus.pos[0], toFocus.pos[1]);
				toFocus = {el:false, pos:false};
			},
			onFocus:function (el, pos) {
				toFocus = {el:el, pos:pos};
			},
			onLeave:function (msg) {
				window.onbeforeunload = (!msg) ? function () {
					return null;
				} : function () {
					return msg;
				};
			}
		}
	}())
});