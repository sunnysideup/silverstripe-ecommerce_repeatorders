(function($){
    $(document).ready(
        function() {
            RepeatOrders.init();
        }
    );
})(jQuery);


var RepeatOrders = {

    formID: "#RepeatOrderModifierForm_RepeatOrderModifier",

    loadingClass: "loading",

    actionsClass: ".Actions",

    nextStepLink: ".checkoutStepPrevNextHolder.next a",

    ajaxSubmissionLink: jQuery('#RepeatOrderModifierForm_RepeatOrderModifier_AjaxSubmissionLink').val(),

    delayForAutoSubmit: 1000,

    availableCountries: new Array(),

    EcomCart: {},

    init: function() {
        let form = jQuery(RepeatOrders.formID);
        let options = {
            beforeSubmit:  RepeatOrders.showRequest,  // pre-submit callback
            success: RepeatOrders.showResponse,  // post-submit callback
            dataType: "json"
        };
        form.ajaxForm(options);
        if(form.length){
            jQuery(RepeatOrders.nextStepLink).hide();
        }
    },

    // pre-submit callback
    showRequest: function (formData, jqForm, options) {
        jQuery(RepeatOrders.actionsClass).hide();
        jQuery(RepeatOrders.formID).addClass(RepeatOrders.loadingClass);

        jQuery.ajax(
            {
                type: options.type,
                url: RepeatOrders.ajaxSubmissionLink,
                data: formData,
                error: function(jqXHR, textStatus, errorThrown){
                    console.log(errorThrown);
                    alert('Error: ' + xhr.responseText);
                },
                success: function(data, textStatus, jqXHR){
                    console.log(jQuery(RepeatOrders.nextStepLink));
                    jQuery(RepeatOrders.nextStepLink).get(0).click();
                }
            }
        );
        return false;
    },

    // post-submit callback
    showResponse: function (responseText, statusText)  {
        jQuery(RepeatOrders.formID).removeClass(RepeatOrders.loadingClass);
    }


}
