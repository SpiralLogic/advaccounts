/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 11:25 PM
 * To change this template use File | Settings | File Templates.
 */
$(function() {

	$("#customers").autocomplete({
		                             source: "search.php",
		                             minLength: 2,
		                             select: function(event, ui) {
			                             $.getJSON("search.php",
			                                       {id: ui.item.id},
			                                       function(data) {
				                                       $.each(data, function(i, data) {
					                                       $("input[name=\'" + i + "\'],textarea[name=\'" + i + "\']").val(data);
				                                       }
						                                       );

			                                       });
		                             }
	                             }).css("z-index", "2");
});