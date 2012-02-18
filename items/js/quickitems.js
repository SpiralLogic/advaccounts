/**
 * User: Eli Sklar
 * Date: 17/07/11 - 10:58 PM
 */
Adv.extend({
						 revertState:function () {
							 $("#Items").empty();
							 $.tmpl('items', Items.getInit()).appendTo("#Items");
						 },
						 resetState:function () {
							 $("#tabs0 input, #tabs0 textarea").empty();
							 Items.fetch(0);
						 },
						 stateModified:function (feild) {
							 if (feild.prop('disabled')) {
								 return;
							 }
							 if (!feild.attr('name')) {
								 feild.attr('name', feild.attr('id'));
							 }
							 Adv.btnCancel.button('option', 'label', 'Cancel Changes').show();
							 var fieldname = feild.addClass("ui-state-highlight").attr('name');
							 $("[name='" + fieldname + "']").each(function () {
								 $(this).val(feild.val()).addClass("ui-state-highlight");
							 });
							 if (Items.get().id === null || Items.get().id === 0) {
								      Adv.btnSave.button("option", "label", "Save New Item").show();
							 } else {
								    Adv.btnSave.button("option", "label", "Save Changes").show();
							 }
							 Items.set(fieldname, feild.val());
							 window.onbeforeunload = function () {
								 return "Continue without saving changes?";
							 };
						 }
					 });
var Items = function () {
	var btn = $("#btnItems").button(), item, initItem,
	 $buyFrame = $('#buyFrame'),
	 $sellFrame = $('#sellFrame'),
	 $locationFrame = $('#locationFrame'),
	 $webFrame = $('#webFrame'),
	 $selects = $('select'),
	 urlregex = /[\w\-\.:/=ƒ&!~\*\'"(),]+/g;
	var $buyFrameSrc = $('#buyFrame').data('src').match(urlregex)[0] + '?frame=1',
	 $sellFrameSrc = $('#sellFrame').data('src').match(urlregex)[0] + '?frame=1',
	 $locationFrameSrc = $('#locationFrame').data('src').match(urlregex)[0] + '?frame=1',
	 $Items = $("#Items").show(), $Accounts = $("#Accounts"), $stockRow = $("#stockRow"), $stockLevels = $("#stockLevels");
	$Items.template('items');
	$Accounts.template('accounts');
	$stockRow.template('stockrow');
	return {
		fetch:function (id) {
			if (id.id !== undefined) {
				id = id.id;
			}
			this.getFrames(id);
			$.post("#", {"id":id}, function (data) {
				Items.onload(data,true);
			}, 'json');
		},
		getFrames:function (id) {
			if (!id){
			Adv.o.tabs.tabs0.tabs('option','disabled',[2,3,4,5]);
				return;
			}
			Adv.o.tabs.tabs0.tabs('option','disabled',[]);
			$buyFrame.attr('src', $buyFrameSrc + '&stock_id=' + id);
			$sellFrame.attr('src', $sellFrameSrc + '&stock_id=' + id);
			$locationFrame.attr('src', $locationFrameSrc + '&stock_id=' + id);
	/*		$webFrame.attr('src', $webFrame.data('srcpre')+ id+$webFrame.data('srcpost'));*/
		},
		set:function (feildname, val) {
			item[feildname] = val;

		},
		onload:function (data,noframes) {
			if (!noframes){this.getFrames(data.item.stock_id);}
			$Items.empty();
			$Accounts.empty();
			item = data.item;
			initItem = $.extend(true, {}, item);
			$.tmpl('items', data.item).appendTo("#Items");
			$.tmpl('accounts', data.item).appendTo("#Accounts");
			if (data.stockLevels){
			$stockLevels.show().find('tbody').html($.tmpl('stockrow', data.stockLevels));}
			$('select').each(function () {
				this.value = data.item[this.name];
			});
			 $('input:checkbox').each(function() {
				 this.checked = !!data.item[this.name];
			 })
		},
		get:function () {
			return item;
		},
		getInit:function () {
			return initItem;
		},
		save:function () {
			$.post('#', item, function (data) {
				Items.onload(data);
			}, 'json');
		}
	};
}();
$(function () {
	Adv.extend({btnCancel:$("#btnCancel").button().click(function () {
		(	!Adv.fieldsChanged > 0) ? Adv.resetState() : Adv.revertState();
		return false;
	}),btnSave:$("#btnSave").button().click(function () {
		Items.save();
			return false;
		}),
							 tabs:$("#tabs0")});
	Adv.tabs.delegate(":input", "change", function (event) {
		event.stopImmediatePropagation();
		Adv.fieldsChanged++;
		if (Items.getInit()[$(this).attr('id')] == $(this).val()) {
			$(this).removeClass("ui-state-highlight");
			Adv.fieldsChanged--;
			if (Adv.fieldsChanged === 0) {
				Adv.resetHighlights();
			}
			return;
		}
		Adv.stateModified($(this));
	})
});

