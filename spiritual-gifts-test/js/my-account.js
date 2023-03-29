jQuery(document).ready(function() {
    var cur_first = jQuery("#user-name-data").attr("first");
    var cur_last = jQuery("#user-name-data").attr("last");

    if (!jQuery("#user-name-data")) return;

    if (cur_first && cur_last) {
        jQuery("#account-name").html("Hello, " + cur_first + " " + cur_last);
    }else{
        jQuery("#account-name-wrapper").show();
        jQuery("#account-name").html("Your name is missing! Please enter one below.");
    }

    jQuery("#change-name-button").click(function() {
        var first = jQuery("#first-name").val();
        var last = jQuery("#last-name").val();

        if (first && last) {
            jQuery("#change-name-button").hide();

            jQuery.ajax({
                url: the_ajax_script.ajaxurl,
                type: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'user_change_name',

                    first: first,
                    last: last,
                },
                success: function (resp) {
                    if (resp.success) {
                        jQuery("#first-name").val("");
                        jQuery("#last-name").val("");
                        jQuery("#change-name-button").show();
                        jQuery("#account-name").html("Hello, " + first + " " + last);
                        jQuery("#change-name-error").html("");
                    }else{
                        alert ('Error: ' + resp.data) ;
                    }
                },
                error: function (xhr, ajaxOptions, thrownError) {
                    alert ('Request failed: ' + thrownError.message);
                },
            });
        }else{
            jQuery("#change-name-error").html("Please enter a first and last name.");
        }
    })
});