/**
 * User: Eli Sklar
 * Date: 24/12/11 - 4:01 PM
 */
	Adv.itemsarch = {o:{},init:function (id, o) {
	Adv.itemsearch.o=o;
	Adv.o.stock_id = ("#" + id).catcomplete({
		 delay:0,
		 autoFocus:true,
		 minLength:0,
		 source:function (request, response) {
			 if (Adv.lastXhr) Adv.lastXhr.abort();
			 Adv.loader.off();
			 Adv.lastXhr = $.ajax({
				 url:o.url,
				 dataType:"json",
				 data:{UniqueID:o.UniqueID, term:request.term},
				 success:function (data, status, xhr) {
					 if (xhr !== Adv.lastXhr) return;
					 if (!Adv.o.stock_id.data('active'))
						 {
							 var value = data[0];
							 Adv.itemsearch.setValues(value);
						 }
					 response($.map(data, function (item) {
						 return {
							 label:item.stock_id + ": " + item.description,
							 value:item,
							 category:item.category
						 }
					 }));
					 Adv.loader.on();
				 }})
		 },
		 select:function (event, ui) {
			 var value = ui.item.value;
			 $(this).val(value.stock_id);
			 if (o.descjs) return Adv.itemsearch.setValues(value);
			 if (o.selectjs) $("form").trigger("submit");
			 return false;
		 },
		 focus:function () {return false;},
		 open:function () { $('.ui-autocomplete').unbind('mouseover');}
	 }
	).blur(function () { $(this).data('active', false)}).focus(function () { $(this).data('active', true)});
},
	setValues:function (value) {
		value.description = value.long_description;
		Adv.Events.onFocus("#stock_id", [0, $(this).position().top]);
		$.each(value, function (k, v) {Adv.Forms.setFormValue(k, v);});
		if (o.descjs) $('#description').css('height','auto').attr('rows',4);
		$("#lineedit").data("stock_id",value.stock_id).show().parent().css("white-space","nowrap");
		return false;
	},
		clean: function() {Adv.o.stock_id.catcomplete('destroy')}
}
