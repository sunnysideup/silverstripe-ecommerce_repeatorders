/**
*@author nicolaas[at]sunnysideup . co . nz
*
**/

(function($){

	$(document).ready(
		function() {
			RepeatOrdersPage_admin.init();
		}
	);


})(jQuery);


var RepeatOrdersPage_admin = {

	detailClassSelector: ".RepeatOrderDetails",

	detailIDSelectorPrefix: "#RepeatOrderDetails-",

	RepeatOrderLinkSelector: ".RepeatOrderLink",

	RepeatOrderFirstLinkSelector: "#firstRepeatOrderLink",

	completeRepeatOrderSelector: ".completeRepeatOrder",

	newWindowName: ".completeRepeatOrder",

	RepeatOrderWithoutOutRepeatSelector: ".RepeatOrderWithoutOutRepeat",

	showHideWithoutOutRepeatSelector: ".showHideWithoutOutRepeat",

	showHideAllRepeatOrdersSelector: ".showHideAllRepeatOrders",

	showHideFutureOrdersSelector: ".showHideFutureOrders",

	futureOrdersSelector: ".TestDraftOrderList li.future, .DraftOrderList li.future",

	init: function() {
		jQuery(RepeatOrdersPage_admin.RepeatOrderWithoutOutRepeatSelector).hide();
		jQuery(RepeatOrdersPage_admin.detailClassSelector).hide();
		jQuery(RepeatOrdersPage_admin.futureOrdersSelector).hide();
		jQuery(RepeatOrdersPage_admin.completeRepeatOrderSelector).attr("target", RepeatOrdersPage_admin.newWindowName);
		jQuery(RepeatOrdersPage_admin.completeRepeatOrderSelector).click(
			function() {
				var url = jQuery(this).attr("href");
				jQuery(this).removeAttr("href");
				jQuery(this).text("COMPLETED");
				window.open(url, RepeatOrdersPage_admin.newWindowName, 'toolbar=0,scrollbars=1,location=0,statusbar=0,menubar=0,resizable=1,width=1024,height=600');
				return false;
			}
		);
		jQuery(RepeatOrdersPage_admin.RepeatOrderLinkSelector).click(
			function(){
				var rel = jQuery(this).attr("rel");
				var id = RepeatOrdersPage_admin.detailIDSelectorPrefix + rel;
				jQuery(id).slideToggle();
				return false;
			}
		);
		jQuery(RepeatOrdersPage_admin.showHideAllRepeatOrdersSelector).click(
			function() {
				var originalText = jQuery(this).text();
				var newText = RepeatOrdersPage_admin.replaceHideShow(originalText);
				jQuery(this).text(newText);
				jQuery(RepeatOrdersPage_admin.detailClassSelector).slideToggle();
				return false;
			}
		);
		jQuery(RepeatOrdersPage_admin.showHideWithoutOutRepeatSelector).click(
			function() {
				var originalText = jQuery(this).text();
				var newText = RepeatOrdersPage_admin.replaceHideShow(originalText);
				jQuery(this).text(newText);
				jQuery(RepeatOrdersPage_admin.RepeatOrderWithoutOutRepeatSelector).slideToggle();
				return false;
			}
		);
		jQuery(RepeatOrdersPage_admin.showHideFutureOrdersSelector).click(
			function() {
				var originalText = jQuery(this).text();
				var newText = RepeatOrdersPage_admin.replaceHideShow(originalText);
				jQuery(this).text(newText);
				jQuery(RepeatOrdersPage_admin.futureOrdersSelector).slideToggle();
				return false;
			}
		);

	},

	replaceHideShow: function(text) {
		var oldText = text;
		var newText = text.replace("hide", "show");
		if(oldText == newText) {
			var newText = text.replace("show", "hide");
		}
		return newText;
	}


}


