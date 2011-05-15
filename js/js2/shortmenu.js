/**
 * Created by JetBrains PhpStorm.
 * User: complex
 * Date: 11/22/10
 * Time: 3:30 AM
 * To change this template use File | Settings | File Templates.
 */
(function(window, undefined) {
	var previous,
			ajaxRequest,
			Searchboxtimeout,
			Adv = window.Adv,
			sidemenu = {},
			searchInput = $('<input/>').attr({type: 'text',value:'',size:14,maxlength:18}).data({'id':'',url:''}),
			$search = $("#search");
	(function() {
		var $this = this,
				$results = $("#results"),
				$wrapper = $("#wrapper");
		this.menu = $("#sidemenu").draggable().accordion({autoHeight: false,event: "mouseover"}).fadeTo("slow", .75).hover(function() {
			$(this).fadeTo("fast", 1);
		}, function() {
			$(this).fadeTo("fast", .75);
		});
		this.sidemenuOn = function() {
			$this.menu.accordion("enable");
			$this.menu.find("h3").undelegate("a", "click");
		};
		this.sidemenuOff = function() {
			$this.menu.accordion("disable");
			$this.menu.find("h3").delegate("a", "click", function() {
				$results.detach();
				$wrapper.show();
			})
		};
		this.doSearch = function () {
			term = searchInput.val();
			Adv.loader.show();
			ajaxRequest = $.post(searchInput.data("url"), { ajaxsearch: term, limit: true }, $this.showSearch);
		};
		this.showSearch = function (data) {
			var content = $('#wrapper', data).attr("id", "results");
			$results.remove();
			$wrapper.before(content).hide();
			Adv.loader.hide();
		}
		$search.delegate("a", "click", function(event) {
			$this.sidemenuOff();
			event.preventDefault();
			$search.find('input').trigger('blur');
			previous = $(this);
			previous.after(searchInput.data({'id':previous.attr('href'),url:previous.attr('href')})).detach();
			searchInput.focus();
			return false;
		});
		$search.delegate('input', "change blur keyup", function(event) {
			if (ajaxRequest && event.type == 'keyup') {
				ajaxRequest.abort();
			}
			if (event.type != "blur" && searchInput.val().length > 1 && event.which != 13 && event.which < 123) {
				window.clearTimeout(Searchboxtimeout);
				Searchboxtimeout = window.setTimeout($this.doSearch, 1000);
			}
			if (event.type != 'keyup') {
				searchInput.after(previous).detach().val('');
				$this.sidemenuOn();
			}
		});
		$('#quickCustomer').autocomplete({
			                                 source: function(request, response) {
				                                 lastXhr = $.getJSON('/contacts/customers.php', request, function(data, status, xhr) {
					                                 if (xhr === lastXhr) {
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
	window.Adv.extend(sidemenu);
})(window);
$(function() {

})
