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

    ajaxCancelLink: jQuery('#RepeatOrderModifierForm_RepeatOrderModifier_AjaxCancelLink').val(),

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

        let ajaxURL = RepeatOrders.ajaxSubmissionLink;
        let action = RepeatOrders.findSubmitAction(formData);
        if(action == 'action_doCancel'){
            ajaxURL = RepeatOrders.ajaxCancelLink;
        }

        jQuery.ajax(
            {
                type: options.type,
                url: ajaxURL,
                data: formData,
                error: function(jqXHR, textStatus, errorThrown){
                    console.log(errorThrown);
                    alert('Error: ' + jqXHR.responseText);
                },
                success: function(data, textStatus, jqXHR){
                    jQuery(RepeatOrders.formID).removeClass(RepeatOrders.loadingClass);
                    jQuery(RepeatOrders.nextStepLink).get(0).click();
                }
            }
        );
        return false;
    },

    // post-submit callback
    showResponse: function (responseText, statusText)  {
        //shoudln't happen as we should have been redirected to checkout by this point
    },

    findSubmitAction: function(data) {
        //done in reverse as submit is most likely to be the last item in the data array
        for (var i = (data.length - 1); i >= 0; i--) {
            if (data[i].type === 'submit') {
                return data[i].name;
            }
        }
        return null;
    }
}
