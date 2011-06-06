/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
(function(window, undefined) {
	var $current,
			ajaxRequest,
			Searchboxtimeout,
			Adv = window.Adv,
			sidemenu = {},
			searchInput = $('<input/>').attr({type: 'text',value:'',size:14,maxlength:18}).data({'id':'',url:''}),
			$search = $("#search"),
			$quickMenu = $('#quickCustomer');
	(function() {
		var $this = this,
				$wrapper = $("#wrapper");
		this.menu = $("#sidemenu").draggable().accordion({autoHeight: false,event: "mouseover"}).fadeTo("slow", .75).hover(function() {
			                                                                                                                   $(this).fadeTo("fast", 1);
		                                                                                                                   }, function() {
			                                                                                                                   $(this).fadeTo("fast", .75);
		                                                                                                                   });
		this.sidemenuOn = function() {
			$this.menu.accordion("enable");
		};
		this.sidemenuOff = function() {
			$this.menu.accordion("disable");
			$this.menu.find("h3").one("click", function() {
				$("#results").detach();
				$wrapper.show();
			})
		};
		this.doSearch = function () {
			var term = searchInput.val();
			Adv.loader.show();
			ajaxRequest = $.post(searchInput.data("url"), { ajaxsearch: term, limit: true }, $this.showSearch);
		};
		this.showSearch = function (data) {
			var content = $('#wrapper', data).attr("id", "results"), $results = $("#results");
			($results.length > 0) ? $results.replaceWith(content) : $wrapper.after(content);
			$wrapper.hide();
			Adv.loader.hide();
		}
		$search.delegate("a", "click", function(event) {
			searchInput.trigger('blur');
			$current = $(this).hide();
			$this.sidemenuOff();
			searchInput.data({'id':$current.attr('href'),url:$current.attr('href')}).insertBefore($current).focus();
			return false;
		});
		$search.delegate('input', "change blur keyup", function(event) {

			if (ajaxRequest && event.type == 'keyup') {
				if (event.keyCode == 13) {
					window.clearTimeout(Searchboxtimeout);
					$this.doSearch();
					return false;
				}
				ajaxRequest.abort();

			}
			if (event.type != "blur" && searchInput.val().length > 1 && event.which < 123) {
				window.clearTimeout(Searchboxtimeout);
				Searchboxtimeout = window.setTimeout($this.doSearch, 1000);
			}
			if (event.type != 'keyup') {
				searchInput.detach().val('');
				$current.show();
				$this.sidemenuOn();
			}
		});
		$quickMenu.autocomplete({
			                        source: function(request, response) {
				                        ajaxRequest = $.getJSON('/contacts/customers.php', request, function(data, status, xhr) {
					                        if (xhr === ajaxRequest) {
						                        response(data);
					                        }
				                        })
			                        },
			                        minLength: 2,
			                        select: function(event, ui) {

				                        $this.showSearch(ui.item.id);

			                        }
		                        });
	}).apply(sidemenu);
	Adv.sidemenu = sidemenu;
})(window);
