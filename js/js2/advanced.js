var Adv;
(function(window, undefined) {
	var Adv = {
		$content: $("#content"),
		loader: $("<div/>").attr('id', 'loader'),fieldsChanged: 0
	};
	(function() {
		var extender = jQuery.extend, toInit = [];
		Adv.loader.hide().prependTo(Adv.$content);
		this.extend = function(object) { extender(Adv, object); };

	}).apply(Adv);
	window.Adv = Adv;
})(window);