var Adv;
(function(window, undefined) {
	var Adv = {
		loader: $("<div/>").attr('id', 'loader'),fieldsChanged: 0
	};
	(function() {
		var extender = jQuery.extend, toInit = [];
		this.extend = function(object) { extender(Adv, object); };

	}).apply(Adv);
	window.Adv = Adv;
})(window);