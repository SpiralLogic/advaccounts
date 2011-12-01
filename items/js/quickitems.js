/**
 * User: Eli Sklar
 * Date: 17/07/11 - 10:58 PM
 */
Adv.extend({
						 msgbox:$('#msgbox').ajaxError(function(event, request, settings) {
							 var status = {
								 status:false,
								 message:"Request failed: " + request + "<br>" + settings
							 };
							 Adv.showStatus(status);
						 }),

						 resetHighlights:function() {
							 $(".ui-state-highlight").removeClass("ui-state-highlight");
							 Adv.fieldsChanged = 0;
							 window.onbeforeunload = function() {
								 return null;
							 };
						 },
						 revertState:    function() {
							 $("#Items").empty();
							 $.tmpl('items', Items.getInit()).appendTo("#Items");
						 },
						 resetState:     function() {
							 $("#tabs0 input, #tabs0 textarea").empty();
							 Items.fetch(0);
						 },
						 showStatus:     function(status) {
							 Adv.msgbox.empty();
							 if (status.status) {
								 Adv.msgbox.attr('class', 'note_msg').text(status.message);
							 } else {
								 Adv.msgbox.attr('class', 'err_msg').text(status.message);
							 }
						 },
						 stateModified:  function(feild) {
							 if (feild.prop('disabled')) {
								 return;
							 }
							 if (!feild.attr('name')) {
								 feild.attr('name', feild.attr('id'));
							 }
							 Adv.btnCancel.button('option', 'label', 'Cancel Changes').show();
							 var fieldname = feild.addClass("ui-state-highlight").attr('name');
							 $("[name='" + fieldname + "']").each(function() {
								 $(this).val(feild.val()).addClass("ui-state-highlight");
							 });
							 if (Items.get().id == null || Items.get().id == 0) {
								 //      Adv.btnItem.button("option", "label", "Save New Item").show();
							 } else {
								 //    Adv.btnItem.button("option", "label", "Save Changes").show();
							 }
							 Items.set(fieldname, feild.val());
							 window.onbeforeunload = function() {
								 return "Continue without saving changes?";
							 };
						 }
					 });
var Items = function() {
	var btn = $("#btnItems").button(), item, initItem, $buyFrame = $('#buyFrame'), $sellFrame = $('#sellFrame');
	var $buyFrameSrc = $('#buyFrame').attr('src'), $sellFrameSrc = $('#sellFrame').attr('src'), $Items = $("#Items"), $stockRow = $("#stockRow"), $stockLevels = $("#stockLevels");
	$Items.template('items');
	$stockRow.template('stockrow');
	return {
		fetch:     function(id) {
			if (id.id !== undefined) {
				id = id.id;
			}
			$buyFrame.attr('src', $buyFrameSrc + '&stock_id=' + id);
			$sellFrame.attr('src', $sellFrameSrc + '&stock_id=' + id);
			$.post("#", {"id":id}, function(data) {
				Items.onload(data);
			}, 'json')

		},
		set:       function(feildname, val) {
			item[feildname] = val;

		},
		onload:    function(data) {
			$Items.empty();

			item = data.item;
			initItem = $.extend(true, {}, item);
			$.tmpl('items', data.item).appendTo("#Items");
			$stockLevels.find('tbody').html($.tmpl('stockrow', data.stockLevels));

		},
		get:       function() {
			return item;
		}, getInit:function() {
			return initItem;
		},
		save:      function() {
			$.post('#', item, function(data) {
				Items.onload(data);
			}, 'json');
		}
	};
}();
$(function() {
	Adv.extend({btnCancel:$("#btnCancel").button().click(function() {
		(	!Adv.fieldsChanged > 0) ? Adv.resetState() : Adv.revertState();
		return false;
	}),
							 tabs:    $("#tabs0")});
	Adv.tabs.delegate(":input", "change", function(event) {
		event.stopImmediatePropagation();
		Adv.fieldsChanged++;
		if (Items.getInit()[$(this).attr('id')] == $(this).val()) {
			$(this).removeClass("ui-state-highlight");
			Adv.fieldsChanged--;
			if (Adv.fieldsChanged == 0) {
				Adv.resetHighlights();
			}
			return;
		}
		Adv.stateModified($(this));
	})
});

