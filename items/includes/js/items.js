/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 7:49 PM
 * To change this template use File | Settings | File Templates.
 */
(function(window, undefined) {
	var Adv = window.Adv,
	 ajaxRequest,
	 Items = {};
	(function() {
		var $this = this,item,term,results,itemList = $('#itemList'),itemDetails = $("#itemDetails");
		itemList.delegate('span', 'click', function() {
			$this.getItem($(this).attr('id'));
			$(this).remove();
		});
		this.getItem = function (id) {
			$.post("items.php", {id: id}, function(data) {
				var content = $('<td><td/>').css('vertical-align', 'top');
				item = {stock_id:data.stock_id,
					description:data.description,
					long_description:data.long_description,
					actual_cost:data.actual_cost,
					last_cost:data.last_cost,
					inactive:data.inactive,
					no_sale:data.no_sale};
				$.each(item, function(i, data) {
					$this.addFeild(i, data).appendTo(content);
				});
				$('<tr></tr>').append(content).appendTo(itemDetails);
			}, 'json')
		};
		this.makeItemList = function(results, request) {
			term = request;
			this.results = results;
			itemList.empty();
			$.map(results, function(v) {
				$('<span>').attr('id', v.id).css('display', 'block').html(v.id + '<br/>').appendTo(itemList);
			})
		};
		this.addFeild = function(name, value) {

			var input = (name == 'description' || name == 'long_description') ? $('<textarea>').attr({'name':name}).text(value) : $('<input/>').attr({'name':name,value:value});
			if (name == 'inactive' || name == 'no_sale') input.attr('type', 'checkbox');
			return input;

		}
	}).apply(Items);
	Adv.Items = Items;
})(window);

$(function($) {
	var oTable = $('#itemDetails').dataTable({
																						 "bProcessing": true,
																						 "bServerSide": true,
																						 "bJQueryUI": true,
																						 "iDisplayLength": 100,
																						 "sAjaxSource": "includes/server_processing.php",
																						 "aoColumns": [
																							 {"sClass":"editable"},
																							 {"sClass":"editable_textarea"},
																							 {"sClass":"editable_textarea"},
																							 {"sClass":"editable","sType":"numeric"},
																							 {"sType":"numeric"},
																							 {"sType":"editable_check"},
																							 {"sType":"editable_check"},

																						 ],
																						 "fnDrawCallback": function () {
																							 $('#itemDetails').find('.editable')
																								.editable('../items/editable_ajax.php', {
																														"callback": function(sValue, y) {
																															/* Redraw the table from the new data on the server */
																															oTable.fnDraw();
																														},
																														"submitdata": function(value, message) {
																															return {
																																"row_id": this.parentNode.getAttribute('id'),
																																"column": oTable.fnGetPosition(this)[2]
																															}
																														},
																														"height": "14px"
																													}).end().find('.editable_textarea')
																								.editable('../items/editable_ajax.php',
																													{"submitdata": function(value, message) {
																														return {
																															"row_id": this.parentNode.getAttribute('id'),
																															"column": oTable.fnGetPosition(this)[2]


																														}
																													},
																														"callback": function(value, y) {
																															/* Redraw the table from the new data on the server */
																															oTable.fnDraw();
																														},
																														type: 'textarea',
																														cancel		: 'Cancel',
																														submit		: 'OK'
																													}).end().find('.editable_check')
																								.editable('../items/editable_ajax.php',
																													{
																														"callback": function(sValue, y) {
																															/* Redraw the table from the new data on the server */
																															oTable.fnDraw();
																														},
																														type: 'checkbox'
																													});
																						 }
																					 });
});