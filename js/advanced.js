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
jQuery.easing['jswing'] = jQuery.easing['swing'];

jQuery.extend(jQuery.easing,
 {
	 def:'easeOutExpo',
	 easeOutExpo:function (x, t, b, c, d) {
		 return (t == d) ? b + c : c * (-Math.pow(2, -10 * t / d) + 1) + b;
	 }
 });

(function (window, $, undefined) {
	//noinspection LocalVariableNamingConventionJS
	var Adv = {

		loader:document.getElementById('ajaxmark'),
		fieldsChanged:0,
		debug:{ ajax:true},
		lastXhr:'',
		o:{$content:$("#content"), tabs:{}}
	};
	(function () {
		var extender = jQuery.extend;
		this.o.wrapper = $("#wrapper");
		this.o.autocomplete = {};
		$(this.loader)
		 .ajaxStart(function () {
			 Adv.loader.on();
			 if (Adv.debug.ajax)
				 {console.time('ajax')}
		 })
		 .ajaxStop(function () {
			 Adv.loader.off();
			 if (Adv.debug.ajax) console.timeEnd('ajax');
		 });
		this.extend = function (object) {extender(Adv, object)};
		extender(Adv.loader, {
			off:function (img) {
				if (img)
					{
						Adv.loader.src = user.theme + 'images/' + img;
						Adv.loader.style.visibility = 'visible';
					} else
					{
						Adv.loader.style.visibility = 'hidden';
					}
			},
			on:function (tout) {
				var img = tout > 60000 ? 'progressbar.gif' : 'ajax-loader.gif';
				Adv.loader.off(img);
			}
		})
	}).apply(Adv);
	window.Adv = Adv;
})(window, jQuery);
Adv.extend({
	msgbox:$('#msgbox').ajaxError(
	 function (event, request, settings) {
		 if (request.statusText == "abort")
			 {return;}
		 var status = {
			 status:false,
			 message:"Request failed: " + settings.url + "<br>"
		 };
		 Adv.showStatus(status);
	 })
	 .ajaxComplete(function (event, request) {
		 Behaviour.apply();
		 try
			 {
				 var data = $.parseJSON(request.responseText);
				 if (data && data.status)
					 {Adv.showStatus(data.status);}
			 }
		 catch (e)
			 {return false}

	 }),
	showStatus:function (status) {
		var text = '', closeTime;
		status = status || {status:null, message:''};
		if (status.status === 'redirect')
			{
				window.onunload = null;
				return window.location.href = status.message;
			}

		if (status.html)
			{
				text = status.html;
			} else
			{
				if (status.message)
					{
						switch (status.status)
						{
							case 61438:
								status.class = 'success_msg';
								break;
							case 1024:
								status.class = 'info_msg';
								break;
							case 512:
								status.class = 'warn_msg';
								break;

							case 256:
							default:
								status.class = 'err_msg';
								break;
						}
						text = '<div class="' + status.class + '">' + status.message + '</div>';
					}
			}

		if (text)
			{ Adv.msgbox.html(text);}
		window.clearTimeout(closeTime);

		Adv.msgbox.stop(true, true).animate({ height:'show', opacity:1 }, 1000, 'easeOutExpo', function () {
			closeTime = window.setTimeout(Adv.hideStatus, 15000);
		});
		try
			{
				var y = Adv.Forms.elementPos(Adv.msgbox[0]).y - 40;
			} catch (e)
			{ return;}
		if (text && $.isNumeric(y))
			{scrollTo(0, y);}
	},
	hideStatus:function () {
		Adv.msgbox.stop(true, true).animate({ height:'hide', opacity:0 }, 2000, 'easeOutExpo');
	},
	openWindow:function (url, title, width, height) {
		width = width || 900;
		height = height || 600;
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
			Adv.o.$content.off('click.open mouseenter.open').on('click.open mouseenter.open mouseleave.open',
			 'div .openWindow,td .openWindow',
			 function (e) {
				 if (e.type == 'click')
					 {
						 Adv.openWindow(this.href, this.target, Adv.hoverWindow.width, Adv.hoverWindow.height);
						 return false;
					 }
				 if (e.type == 'mouseenter')
					 {
						 if (Adv.o.popupCurrent)
							 {window.clearTimeout(Adv.o.popupCurrent);}
						 Adv.o.popupEl = this;
						 Adv.o.popupParent = $(this).parent();
						 Adv.o.popupCurrent = window.setTimeout(Adv.popupWindow, 750);
					 }
				 if (e.type == 'mouseleave')
					 { window.clearTimeout(Adv.o.popupCurrent);}
			 })
		},
		loaded:function () {
			Adv.o.popupWindow.show();
			var height = Adv.o.popupWindow[0].contentWindow.document.body.clientHeight;
			var top = ($(window).height() / 2 - (height / 2));
			if (height > Adv.hoverWindow.height)
				{
					top = 20;
					height = Adv.hoverWindow.height
				}
			var left = ($(window).width() / 2 - Adv.hoverWindow.width / 2);
			Adv.o.popupWindow.css('height', height);
			Adv.o.popupDiv.css({width:Adv.hoverWindow.width, 'height':height, 'left':left, 'top':top});
		}},
	popupWindow:function () {
		if (Adv.o.popupWindow)
			{Adv.o.popupWindow.parent().remove();}
		Adv.o.popupWindow = $("<iframe>", {
			src:Adv.o.popupEl.href + '&frame=1',
			width:Adv.hoverWindow.width,
			onload:'Adv.hoverWindow.loaded()'
		}).css({background:'white'}).hide();
		Adv.o.popupDiv = $('<div>', {
			 id:'iframePopup',
			 width:100,
			 height:100}
		).html(Adv.o.popupWindow).on('mouseleave',
		 function () { $(this).remove(); }).appendTo(Adv.o.wrapper).position({my:"center center", at:"center center", of:document.body});
	}
});
Adv.extend({Forms:(function () {
	Adv.o.wrapper.on('focus', ".datepicker",
	 function () { $(this).datepicker({numberOfMonths:3, showButtonPanel:true, showCurrentAtPos:2, dateFormat:'dd/mm/yy'}).focus(); });

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
			value =	el.checked = !!value;
			}
		if (String(value).length === 0)
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
			Adv.o.autocomplete[id] = $this = $('#' + id).autocomplete({
				minLength:1,
				delay:200,
				autoFocus:true,
				source:function (request, response) {
					var $this = Adv.o.autocomplete[id];
					$this.off('change.autocomplete');
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
					$this.data('default', null);
					if (callback(ui.item, event, this) === false)
						{return false;}
				},
				focus:function () {return false;}})
			 .blur(function () {$(this).data('active', false); })
			 .bind('autocompleteclose',
			 function () {
				 var $this = $(this);
				 if (this.value.length > 1 && $this.data().autocomplete.selectedItem === null && $this.data()['default'] !== null)
					 {
						 if (callback($this.data()['default'], event, this) !== false)
							 {
								 $this.val($this.data()['default'].label);
							 }
					 }
				 $this.data('default', null)
			 })
			 .focus(
			 function () {
				 $(this).data('active', true).on('change.autocomplete', function () {
					 $(this).autocomplete('search', $this.val());
				 })
			 }).css({'z-index':'2'});
			if (document.activeElement === $this[0]) {$this.data('active', true);}
		},
		moveFocus:function (dir, e0, neighbours) {
			var p0 = Adv.Forms.elementPos(e0), t, l = 0;
			for (var i = 0; i < neighbours.length; i++)
				{
					var e = neighbours[i], p = Adv.Forms.elementPos(e);
					if (p !== null && (e.className == 'menu_option' || e.className == 'printlink'))
						{
							if (((dir == 40) && (p.y > p0.y)) || (dir == 38 && (p.y < p0.y))
									 || ((dir == 37) && (p.x < p0.x)) || ((dir == 39 && (p.x > p0.x))))
								{
									var l1 = (p.y - p0.y) * (p.y - p0.y) + (p.x - p0.x) * (p.x - p0.x);
									if ((l1 < l) || (l === 0))
										{
											l = l1;
											t = e;
										}
								}
						}
				}
			if (t)
				{
					Adv.Forms.setFocus(t);
				}
			return t;
		},
		priceFormat:function (post, num, dec, label, color) {

			var el = label ? document.getElementById(post) : document.getElementsByName(post)[0];
			//num = num.toString().replace(/\$|\,/g,'');
			if (isNaN(num))
				{
					num = "0";
				}
			sign = (num == (num = Math.abs(num)));
			if (dec < 0) dec = 2;
			decsize = Math.pow(10, dec);
			num = Math.floor(num * decsize + 0.50000000001);
			cents = num % decsize;
			num = Math.floor(num / decsize).toString();
			for (i = cents.toString().length; i < dec; i++)
				{
					cents = "0" + cents;
				}
			for (var i = 0; i < Math.floor((num.length - (1 + i)) / 3); i++)
				{
					num = num.substring(0, num.length - (4 * i + 3)) + user.ts +
								num.substring(num.length - (4 * i + 3));
				}
			num = ((sign) ? '' : '-') + num;
			if (dec != 0) num = num + user.ds + cents;
			if (label)
				{
					el.innerHTML = num;
				}
			else
				{
					el.value = num;
				}
			if (color)
				{
					el.style.color = (sign) ? '' : '#FF0000';
				}
		},
		getAmount:function (doc, label) {
			var val;
			if (label)
				{
					val = document.getElementById(doc).innerHTML;
				}
			else
				{
					val = typeof(doc) === "string" ?
								document.getElementsByName(doc)[0].value : doc.value;
				}

			val = val.replace(new RegExp('\\' + user.ts, 'g'), '');
			val = +val.replace(new RegExp('\\' + user.ds, 'g'), '.');
			return isNaN(val) ? 0 : val;
		},
		setFocus:function (name, byId) {
			var el;
			if (typeof(name) == 'object')
				{
					el = name;
				}
			else
				{
					if (!name)
						{ // page load/ajax update
							if (_focus)
								{
									name = _focus;
								}  // last focus set in onfocus handlers
							else
								{
									if (document.forms.length)
										{  // no current focus (first page display) -  set it from from last form
											var cur = document.getElementsByName('_focus')[document.forms.length - 1];
											if (cur)
												{name = cur.value;}
										}
								}
						}
					if (byId || !(el = document.getElementsByName(name)[0]))
						{
							el = document.getElementById(name);
						}
				}
			if (el && el.focus)
				{
					// The timeout is needed to prevent unpredictable behaviour on IE & Gecko.
					// Using tmp var prevents crash on IE5

					var tmp = function () {
						el.focus();
						if (el.select)
							{el.select();}
					};
					setTimeout(tmp, 0);
				}
		},
//returns the absolute position of some element within document
		elementPos:function (e) {
			var res = new Object();
			res.x = 0;
			res.y = 0;
			if (e !== null)
				{
					res.x = e.offsetLeft;
					res.y = e.offsetTop;
					var offsetParent = e.offsetParent;
					var parentNode = e.parentNode;

					while (offsetParent !== null && offsetParent.style.display != 'none')
						{
							res.x += offsetParent.offsetLeft;
							res.y += offsetParent.offsetTop;
							// the second case is for IE6/7 in some doctypes
							if (offsetParent != document.body && offsetParent != document.documentElement)
								{
									res.x -= offsetParent.scrollLeft;
									res.y -= offsetParent.scrollTop;
								}
							//next lines are necessary to support FireFox problem with offsetParent
							if (navigator.userAgent.match(/gecko/i))
								{
									while (offsetParent != parentNode && parentNode !== null)
										{
											res.x -= parentNode.scrollLeft;
											res.y -= parentNode.scrollTop;

											parentNode = parentNode.parentNode;
										}
								}
							parentNode = offsetParent.parentNode;
							offsetParent = offsetParent.offsetParent;
						}
				}
			// parentNode has style.display set to none
			if (parentNode != document.documentElement)
				{return null;}
			return res;
		},	resetHighlights:function () {
				$(".ui-state-highlight").removeClass("ui-state-highlight");
				Adv.fieldsChanged = 0;
				Adv.Events.onLeave();
			},
			stateModified:function (feild) {
				Adv.fieldsChanged++;
				if (feild.is(':checkbox')) {
					value = feild.prop('checked');
					feild.val(value);
				}else {
					value = feild.val();
				}

				if (feild.data('init') === value)
					{
						Adv.fieldsChanged--;
						Adv.fieldsChanged === 0 ? Adv.Forms.resetHighlights() : feild.removeClass("ui-state-highlight");
						return;
					}
				if (feild.prop('disabled'))	{return;}
				var fieldname = feild.addClass("ui-state-highlight").attr('name');
				$("[name='" + fieldname + "']").addClass("ui-state-highlight");
				Adv.Events.onLeave("Continue without saving changes?");
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
				if (msg)
					{
						window.onbeforeunload = function () {
							return msg;
						};
					} else
					{
						window.onbeforeunload = function () {
							return null;
						};
					}
			}
		}
	}())
});

