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
		generateinfo: ''
	};
	(function() {
		var extender = jQuery.extend, toInit = [];
		Adv.loader.hide().prependTo(Adv.$content).ajaxStart(
				function() {
					$(this).show()
				}).ajaxStop(function() {
					            $(this).hide()
				            });
		this.extend = function(object) {
			extender(Adv, object);
		};

	}).apply(Adv);
	window.Adv = Adv;
})(window);

Adv.extend({
	           Events: (function($) {
		           var events = [],
				           onload = [],
				           toClean = [];
		           var firstBind = function (s, t, a) {
			           $(s).bind(t, a);
		           };
		           return {
			           bind: function(selector, types, action) {
				           events[events.length] = {s:selector,t:types,a:action};
				           firstBind(selector, types, action);
			           },
			           onload: function(actions) {
				           onload = actions;
				           $.each(actions, function(k, v) {
					           var result = v();
					           if (result !== undefined) {
						           toClean[toClean.length] = result;
					           }
				           });
			           },
			           debug: function() {
				           return toClean;
			           },
			           rebind: function() {
				           $.each(toClean, function(k, v) {
					           v();
				           });
				           $.each(onload, function(k, v) {
					           v();
				           });
				           $.each(events, function(k, v) {
					           firstBind(v.s, v.t, v.a);
				           });
			           }
		           }
	           }(jQuery))
           });