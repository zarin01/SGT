jQuery(document).ready(function() {

    var usElement = jQuery("#usa-info");
    usElement.detach();

    var caElement = jQuery("#canada-info");
    caElement.detach();

    jQuery("#billing_country").change(function() {
        var selectedCountry = jQuery(this).val();
        var infoWrapper = jQuery("#country-specific-info-wrapper");

        if (selectedCountry == "US") {
            caElement.detach();
            infoWrapper.append(usElement);
        }else if (selectedCountry == "CA") {
            usElement.detach();
            infoWrapper.append(caElement);
        }else{
            usElement.detach();
            caElement.detach();
        }
    });

    jQuery("#place_order").click(function(e) {
        e.preventDefault();

        var billing_first_name = jQuery("#billing_first_name").val();
        var billing_last_name = jQuery("#billing_last_name").val();
        var billing_country = jQuery("#billing_country").val();
        var billing_address_1 = jQuery("#billing_address_1").val();
        var billing_address_2 = jQuery("#billing_address_2").val();
        var billing_city = jQuery("#billing_city").val();
        var billing_state = jQuery("#billing_state").val();
        var billing_postcode = jQuery("#billing_postcode").val();
        var billing_phone = jQuery("#billing_phone").val();
        var billing_email = jQuery("#billing_email").val();
        var account_username = jQuery("#account_username").val();
        var account_password = jQuery("#account_password").val();
        var accepted_terms = jQuery("#terms").attr("checked");

        if (billing_first_name && billing_last_name && billing_country && billing_address_1 && billing_city &&
            billing_state && billing_postcode && billing_phone && billing_email && account_username && account_password && accepted_terms) {
            
            jQuery("#error-message").hide();
            
            jQuery.ajax({
                url: the_ajax_script.ajaxurl,
                type: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'iframe_new_user_form_submit',
    
                    billing_first_name: billing_first_name,
                    billing_last_name: billing_last_name,
                    billing_country: billing_country,
                    billing_address_1: billing_address_1,
                    billing_address_2: billing_address_2,
                    billing_city: billing_city,
                    billing_state: billing_state,
                    billing_postcode: billing_postcode,
                    billing_phone: billing_phone,
                    billing_email: billing_email,
                    account_username: account_username,
                    account_password: account_password,
                    accepted_terms: accepted_terms
                },
                beforeSend: function(){
                    console.log("Submitting Form");
                    jQuery("#place_order").attr("disabled", "disabled");
                },
                success: function (resp) {
                    if (resp.success) {
                        window.location=document.location.href;
                        jQuery("#place_order").removeAttr("disabled", "disabled");
                    }else{
                        jQuery("#error-message").show();
                        jQuery("#error-message").html(resp.data.error);
                        jQuery("#place_order").removeAttr("disabled", "disabled");
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert ('Request failed: ' + thrownError.message);
                    jQuery("#place_order").removeAttr("disabled", "disabled");
                },
            });
        }else{
            jQuery("#error-message").show();
            jQuery("#error-message").html("Please fill out all required fields to continue.");
            jQuery("#place_order").removeAttr("disabled", "disabled");
        }
    })
});