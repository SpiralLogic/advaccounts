(function(window, undefined) {
	var Adv = window.Adv,
			dialog = {};
	(function() {
		var $this = this;
		this.$overlay = $("<div/>").attr("id", "overlay").addClass('ui-overlay');
		this.$supplier_details = $("#supplier_details").dialog({
			                                                       title: $(this).data("name"),
			                                                       autoOpen: false,
			                                                       buttons: {
				                                                       Close: function() {
					                                                       $(this).dialog("close");
				                                                       }
			                                                       },
			                                                       modal: true
		                                                       });
		this.init = function() {
			$("td[name='supplier_name']").addClass("pointer").click(function() {
				$this.$supplier_details.dialog("open");
				return false;
			})
		}
	}).apply(dialog);
	Adv.dialog = dialog;
})(window);

$(function() {
	Adv.dialog.init();
})