var Adv;
$.widget("custom.catcomplete", $.ui.autocomplete, {
  _renderMenu: function(ul, items) {
	 var self = this,
	  currentCategory = "";
	 $.each(items, function(index, item) {
		if (item.category != currentCategory) {
		  ul.append("<li class='ui-autocomplete-category'>" + item.category + "</li>");
		  currentCategory = item.category;
		}
		self._renderItem(ul, item);
	 });
  }
});
(function(window, undefined) {

  var Adv = {

	 $content: $("#content"),
	 loader: $("<div/>").attr('id', 'loader'),
	 fieldsChanged: 0,
	 generateinfo: '',
	 lastXhr:'',
	 o:{}
  };
  (function() {
	 var extender = jQuery.extend, toInit = [];
	 this.o.wrapper = $("#wrapper");
	 this.loader.prependTo(Adv.$content).hide()
	  .ajaxStart(function() {$(this).show()})
	  .ajaxStop(function() {$(this).hide()});
	 this.extend = function(object) {extender(Adv, object)};
  }).apply(Adv);
  window.Adv = Adv;
})(window);
Adv.extend({
				 msgbox: $('#msgbox').ajaxError(function(event, request, settings) {
					if (request.statusText == "abort") return;
					var status = {
					  status: false,
					  message: "Request failed: " + settings.url + "<br>"
					};
					console.log([event,request,settings]);
					Adv.showStatus(status);

				 }),
				 showStatus:function (status) {
					Adv.msgbox.empty();
					status.class = (status.status) ? 'note_msg' : 'err_msg';
					Adv.msgbox.attr('class', status.class).html(status.message);
				 },
				 setFormValue: function (id, value, disabled) {
					var el = $('[name="' + id + '"]');
					if (!el.length) {
					  el = $('#' + id);
					}
					if (typeof disabled === 'boolean') {
					  el.prop('disabled', disabled);
					}
					if (el.is('select')) {
					  if (el.val() == null || String(value).length == 0) {
						 el.find('option:first').prop('selected', true);
						 el.data('init', el.val());
						 return;
					  }
					}
					if (el.is(':checkbox')) {
					  return el.prop('checked', !!value);
					}
					;
					if (String(value).length == 0) {
					  value = '';
					}
					el.val(value).data('init', value);

				 }
			  })
Adv.extend({
				 Events: (function() {
					var events = [],
					 onload = [],
					 toClean = [],
					 toFocus;
					var firstBind = function (s, t, a) {
					  $(s).bind(t, a);
					};
					return {
					  bind: function(selector, types, action) {
						 events[events.length] = {s:selector,t:types,a:action};
						 firstBind(selector, types, action);
					  },
					  onload: function(actions) {
						 var c = onload.length > 0;
						 $.each(actions, function(k, v) {
							onload[onload.length] = v;
							if (c) return;
							var result = v();
							if (result !== undefined) {
							  toClean[toClean.length] = result;
							}
						 });
					  },
					  rebind: function() {
						 console.log(onload.length);
						 $.each(toClean, function(k, v) {
							v();
						 });
						 $.each(onload, function(k, v) {
							v();
						 });
						 $.each(events, function(k, v) {
							firstBind(v.s, v.t, v.a);
						 });
						 if (toFocus.el) $(toFocus.el).focus();
						 if (toFocus.pos) scrollTo(toFocus.pos[0],toFocus.pos[1]);
						 toFocus = undefined;
					  },
					  onFocus: function(el,pos) {
						 toFocus = {el:el,pos:pos};
					  },
					  onLeave: function(msg) {
						 window.onbeforeunload = (!msg) ? function() {
							return null;
						 } : function () {
							return msg;
						 };
					  }
					}
				 }())
			  });
