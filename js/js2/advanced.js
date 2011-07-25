var Adv;
(function(window, undefined) {

	var Adv = {
		$content: $("#content"),
		loader: $("<div/>").attr('id', 'loader'),
		fieldsChanged: 0
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
		           var events = [];
		           var onload=[];
		           var firstBind=function (s, t, a) {
			           $(s).bind(t, a);
		           }
		           return {
			           bind: function(selector, types, action) {
				           events[events.length] = {s:selector,t:types,a:action};
				           firstBind(selector,types,action);
			           },
			           onload: function(action) {
				           onload[onload.length] =action;
				           action();
			           },
			           rebind: function() {
				           $.each(onload, function(k,v) {
					           v();				           });
				           $.each(events, function(k,v) {
					           firstBind(v.s, v.t, v.a);
				           });

			           }
		           }
	           }(jQuery))
});