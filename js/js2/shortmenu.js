/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
;
(function(window, $, undefined) {
	var $current, Searchboxtimeout, Adv = window.Adv, sidemenu = {}, searchInput = $('<input/>').attr({type:'text', value:'', size:14, maxlength:18}).data({'id':'', url:''}), $search = $("#search"), $quickMenu = $('#quickCustomer');
	(function() {
		var $this = this, $wrapper = $("#_page_body"), $results = $wrapper.clone();
		this.menu = $("#sidemenu").accordion({autoHeight:false, active:false, event:"mouseenter"}).fadeTo("fast", .75).draggable();
		this.sidemenuOn = function() {
			$this.menu.accordion("enable").hover(function() {
				$(this).fadeTo("fast", 1).accordion({collapsible:false, active:false});
			}, function() {
				$(this).accordion({collapsible:false, active:false}).fadeTo("fast", .75);
			});
		};
		this.sidemenuOn();
		this.sidemenuOff = function() {
			$this.menu.unbind('mouseenter mouseleave').accordion("disable");
			$this.menu.find("h3").one("click", function() {
				$results.detach();
				$wrapper.show();
			})
		};
		this.doSearch = function() {
			var term = searchInput.val();
			Adv.lastXhr = $.post(searchInput.data("url"), { 'ajaxsearch':term, limit:true }, $this.showSearch);
		};
		this.showSearch = function(data) {
			$results.empty().append(data).insertBefore($wrapper);
			$wrapper.hide();
		}
		$search.delegate("a", "click", function(event) {
			searchInput.trigger('blur');
			$current = $(this).hide();
			$this.sidemenuOff();
			searchInput.data({'id':$current.attr('href'), url:$current.attr('href')}).insertBefore($current).focus();
			return false;
		});
		$search.delegate('input', "change blur keyup", function(event) {
			if (Adv.lastXhr && event.type == 'keyup') {
				if (event.keyCode == 13) {
					window.clearTimeout(Searchboxtimeout);
					$this.doSearch();
					return false;
				}
				Adv.lastXhr.abort();
			}
			if (event.type != "blur" && searchInput.val().length > 1 && event.which < 123) {
				window.clearTimeout(Searchboxtimeout);
				Searchboxtimeout = window.setTimeout($this.doSearch, 1000);
			}
			if (event.type != 'keyup') {
				searchInput.val('').detach();
				$current.show();
				$this.sidemenuOn();
			}
		});
		$quickMenu.autocomplete({
															source:function(request, response) {
																Adv.lastXhr = $.getJSON('/contacts/customers.php', request, function(data, status, xhr) {
																	if (xhr === Adv.lastXhr) {
																		response(data);
																	}
																})
															},
															minLength:2,
															select:function(event, ui) {
																window.location.href = '/contacts/customers.php?id=' + ui.item.id;
															}
														});
	}).apply(sidemenu);
	Adv.sidemenu = sidemenu;
})(window, jQuery);
