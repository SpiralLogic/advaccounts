/**
 * User: Eli Sklar
 * Date: 17/07/11 - 10:58 PM
 */
Adv.extend({
	           msgbox: $('#msgbox').ajaxError(function(event, request, settings) {
		           var status = {
			           status: false,
			           message: "Request failed: " + request + "<br>" + settings
		           };
		           Adv.showStatus(status);
	           }),
	           setFormValue: function (id, value) {
		           var el = $("#" + id + "");
		           if (el.length == 0) {
			           el = $("[name=\'" + id + "\']");
		           }
		           if (el.find('option').length > 0) {
			           if (el.val() == null || String(value).length == 0) {
				           el.find('option:first').attr('selected', true);
				           el.data('init', el.val());
			           } else {
				           el.val(value).data('init', value);
			           }
			           return;
		           }
		           (String(value).length > 0) ? el.val(value).data('init', value) : el.val(value).data('init', '');


	           },
	           resetHighlights: function() {
		           $(".ui-state-highlight").removeClass("ui-state-highlight");
		           Adv.fieldsChanged = 0;
		           window.onbeforeunload = function () {
			           return null;
		           };
	           },
	           revertState: function () {
		           $('.ui-state-highlight').each(function() {
			           $(this).val($(this).data('init'))
		           });
		           Adv.resetHighlights();
	           },
	           resetState:function() {
		           $("#tabs0 input, #tabs0 textarea").empty();
		           Items.fetch(0);
	           },
	           showStatus:function (status) {
		           Adv.msgbox.empty();
		           if (status.status) {
			           Adv.msgbox.attr('class', 'note_msg').text(status.message);
		           } else {
			           Adv.msgbox.attr('class', 'err_msg').text(status.message);
		           }
	           },
	           stateModified:function (feild) {
		           if (feild.prop('disabled')) {
			           return;
		           }
		           Adv.btnCancel.button('option', 'label', 'Cancel Changes').show();
		           var fieldname = feild.addClass("ui-state-highlight").attr('name');
		           $("[name='" + fieldname + "']").each(function() {
			           $(this).val(feild.val()).addClass("ui-state-highlight");
		           });
		           if (Items.get().id == null || Items.get().id == 0) {
			           Adv.btnItem.button("option", "label", "Save New Item").show();
		           } else {
			           Adv.btnItem.button("option", "label", "Save Changes").show();
		           }
		           Item.set(fieldname, feild.val());
		           window.onbeforeunload = function() {
			           return "Continue without saving changes?";
		           };
	           }
           });
var Items = function() {
	var btn = $("#btnItems").button(),item,$buyFrame = $('#buyFrame'), $sellFrame = $('#sellFrame');
	var $buyFrameSrc = $('#buyFrame').attr('src'), $sellFrameSrc = $('#sellFrame').attr('src');
	$("#Items").template('items');
	$("#stockRow").template('stockrow');

	return {
		fetch: function(id) {
			if (id.id !== undefined) {
				id = id.id;
			}
			$buyFrame.attr('src', $buyFrameSrc + '&stock_id=' + id);
			$sellFrame.attr('src', $sellFrameSrc + '&stock_id=' + id);

			$.post("search.php", {"id": id}, function(data) {
				Items.onload(data);

			}, 'json')

		},
		set: function(feildname, val) {

		},
		onload: function(data) {
			$("#Items").empty();
			$.tmpl('items', data.item).appendTo("#Items");
			$.tmpl('stockrow', data.stockLevels).appendTo("#stockLevels");
		},
		get:function() {
			return Items.item;
		}
	};
}();

