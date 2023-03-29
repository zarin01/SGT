jQuery.noConflict();
jQuery( document ).ready(function() {
    jQuery(".icon-sgt a").click(function(){
        jQuery("#sgt-results").toggleClass("active");
    });
    jQuery(".icon-personality a").click(function(){
        jQuery("#personality-results").toggleClass("active");
    });
    jQuery(".icon-combined a").click(function(){
        jQuery("#combined-results").toggleClass("active");
    });
    jQuery(".topthree-readmore").click(function(){
        jQuery(this).prev().addClass("active");
        jQuery(this).addClass("active");
    });
    jQuery(".multicolor-divider.combined-title").click(function(){
        jQuery(this).parent().toggleClass("active");
    });
    jQuery(".button.expand-results").click( function(){
        jQuery(this).toggleClass("active").siblings('.subsection').toggleClass("active");
    });
    jQuery("div#gform_wrapper_12").click(function(){
        jQuery(this).addClass("active");
    });
    jQuery("div#gform_wrapper_15").click(function(){
        jQuery(this).addClass("active");
    });
    jQuery("div#gform_wrapper_14").click(function(){
        jQuery(this).addClass("active");
    });
    jQuery("div#gform_wrapper_16").click(function(){
        jQuery(this).addClass("active");
    });
    jQuery("div#gform_wrapper_19").click(function(){
        jQuery(this).addClass("active");
    });
    jQuery("#login-opener").click(function(){
        jQuery(this).parent("#embed-login-option").toggleClass("active");

        var form = jQuery('#new-account-form');

        if (form.attr("hidden")) {
            form.removeAttr("hidden", "hidden");
            form.show();
        }else{
            form.attr("hidden", "hidden");
            form.hide();
        }
    });
    jQuery(".NewsLetterSignUpex").click(function(){
        jQuery(this).parent().parent(".NewsLetterSignUp").addClass("hidepopup");
        jQuery(this).addClass("test");
    });
    jQuery(".gform_confirmation_wrapper").parent().parent(".NewsLetterSignUp").addClass("aboutaleave");
    jQuery("html").mouseenter(function(){
        jQuery(".NewsLetterSignUp").addClass("notleavinyet");
    });
    jQuery("html").mouseleave(function(){
        jQuery(".NewsLetterSignUp").addClass("aboutaleave");
        jQuery(".NewsLetterSignUp").removeClass("notleavinyet");
    });
    jQuery('#choice_4_22_1').click(function() {
       if(jQuery('#choice_4_22_1').is(':checked')) {
         jQuery(".access-coupon-code").addClass("checked");
         jQuery("#field_4_21 label").replaceWith("<label class='gfield_label' for='gf_coupon_code_4'>Coupon Code</label>");
         jQuery("#field_4_21 div.gfield_description").replaceWith("<div class='gfield_description'></div>");
       }
    });
    jQuery('#choice_4_22_0').click(function() {
       if(jQuery('#choice_4_22_0').is(':checked')) {
         jQuery(".access-coupon-code").removeClass("checked");
         jQuery("#field_4_21 label").replaceWith("<label class='gfield_label' for='gf_coupon_code_4'>Access Code</label>");
         jQuery("#field_4_21 div.gfield_description").replaceWith("<div class='gfield_description'>This is where you can connect your profile with your church or enter a discount code.</div>");
       }
    });
    jQuery(window).scroll(function() {
        var scroll = jQuery(window).scrollTop();
        if (scroll > 15) {
            jQuery("body").addClass("scrolled");
        }else{
            jQuery("body").removeClass("scrolled");
        }
    });

});

// document.getElementById("copyButton").addEventListener("click", function() {
//     copyToClipboard(document.getElementById("copyTarget"));
// });
//
// function copyToClipboard(elem) {
// 	  // create hidden text element, if it doesn't already exist
//     var targetId = "_hiddenCopyText_";
//     var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
//     var origSelectionStart, origSelectionEnd;
//     if (isInput) {
//         // can just use the original source element for the selection and copy
//         target = elem;
//         origSelectionStart = elem.selectionStart;
//         origSelectionEnd = elem.selectionEnd;
//     } else {
//         // must use a temporary form element for the selection and copy
//         target = document.getElementById(targetId);
//         if (!target) {
//             var target = document.createElement("textarea");
//             target.style.position = "absolute";
//             target.style.left = "-9999px";
//             target.style.top = "0";
//             target.id = targetId;
//             document.body.appendChild(target);
//         }
//         target.textContent = elem.textContent;
//     }
//     // select the content
//     var currentFocus = document.activeElement;
//     target.focus();
//     target.setSelectionRange(0, target.value.length);
//
//     // copy the selection
//     var succeed;
//     try {
//     	  succeed = document.execCommand("copy");
//     } catch(e) {
//         succeed = false;
//     }
//     // restore original focus
//     if (currentFocus && typeof currentFocus.focus === "function") {
//         currentFocus.focus();
//     }
//
//     if (isInput) {
//         // restore prior selection
//         elem.setSelectionRange(origSelectionStart, origSelectionEnd);
//     } else {
//         // clear temporary content
//         target.textContent = "";
//     }
//     return succeed;
// }

function EnableApplyButton(formId) {
    jQuery('#gf_coupons_container_' + formId + ' #gf_coupon_button').prop('disabled', false);
}

function removeDuplicates(arr){
    let unique_array = []
    for(let i = 0;i < arr.length; i++){
        if(unique_array.indexOf(arr[i]) == -1){
            unique_array.push(arr[i])
        }
    }
    return unique_array
}

function jsUcfirst(string)
{
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function generate_select(select_value) {

    if(!select_value)
        return;

     var dataList = jQuery(".church-results-row").map(function() {
         if(select_value == 'top-three')
            return jQuery(this).data(select_value).trim().split(' ');
         else
             return jQuery(this).data(select_value);
     }).get();

    dataList = removeDuplicates(dataList.sort());
    var selector = '<select name="'+select_value+'">';
        selector += '<option>Select...</option>';
        for (var i = 0, len = dataList.length; i < len; i++) {
            if(dataList[i])
                if(dataList[i] == 'opennesstoexperience')

                    selector += '<option value="'+dataList[i]+'">Openness to Experience</option>';
                else
                    selector += '<option value="'+dataList[i]+'">'+jsUcfirst(dataList[i])+'</option>';
        }
        selector += '</select>';

    return selector;

}

function switch_html(html) {
    jQuery('#filter-input').html(html);
}

function filter_by_name(event) {
    var input = jQuery('input[name="name"]').val();

    //console.log(event.data.default_csv_url);

    if(typeof(input) == "undefined" || input.length < 3) {
        jQuery('.church-results-row').show();
        jQuery('#download-csv').attr('href', event.data.default_csv_url);
        return;
    }

    jQuery('.church-results-row').hide();
    jQuery("tr[data-name*='"+input+"' i]").show();
    jQuery('#download-csv').attr('href', event.data.default_csv_url+"&filter=name&mname="+encodeURI(input));
}

function filter_by_date(event) {

    //console.log(event.data.default_csv_url);

    var from_date = jQuery("input[name='from']").val();
    var to_date = jQuery("input[name='to']").val();
    if(typeof(from_date) == "undefined") {
        jQuery('.church-results-row').show();
        console.log('No from date');
        return;
    }
    var from_seconds = Math.round(new Date(from_date+" UTC").getTime()/1000);
    if(to_date)
        var to_seconds = Math.round(new Date(to_date+" UTC").getTime()/1000) + 86399;
    else
        var to_seconds = from_seconds + 86399;

    jQuery('.church-results-row').hide().each(function() {
        if(from_seconds <= jQuery(this).data('date') && jQuery(this).data('date') <= to_seconds) {
            jQuery(this).show();
        }
    })

    jQuery('#download-csv').attr('href', event.data.default_csv_url+"&filter=date&from="+from_seconds+"&to="+to_seconds);
}

function filter_by_select(event) {
    var selector = event.data.selector;
    if(typeof(selector) == "undefined") {
        jQuery('.church-results-row').show();
        return;
    }

    var value = jQuery("select[name='"+selector+"' i]").val();
    jQuery('.church-results-row').hide();
    jQuery("tr[data-"+selector+"*='"+value+"' i]").show();
    jQuery('#download-csv').attr('href', event.data.default_csv_url+"&filter="+selector+"&"+selector+"="+encodeURI(value));

}




//Church Admin Results page filters
jQuery(document).ready(function($) {

    var csv_button = $('#download-csv');
    var csv_url = csv_button.attr('href');
    //console.log(csv_url);

    var html = '<label>Filter By:</label>';
    html +=     '<select id="filter-select">';
    html +=         '<option>None</option>';
    html +=         '<option value="name">Name</option>';
    html +=         '<option value="date">Date</option>';
    html +=         '<option value="age-range">Age Range</option>';
    html +=         '<option value="gender">Gender</option>';
    html +=         '<option value="top-gift">Top Gift</option>';
    html +=         '<option value="top-three">In Top Three Gifts</option>';
    html +=         '<option value="top-personality">Top Personality</option>';
    html +=     '</select>';
    html +=     '<span id="filter-input"></span>';

    $('.page-template-church-results-page #filter').html(html);


    $('#filter-select').change(function() {

        csv_button.attr('href', csv_url);
        jQuery('.church-results-row').show();

        switch (this.value) {
            case 'name':
                switch_html('<input type="text" name="name">');
                //$('body').on('keyup', 'input[name=name]', function() {console.log(this.value)});
                $('input[name=name]').on('keyup', { default_csv_url: csv_url }, filter_by_name);
                break;
            case 'date':
                switch_html('From: <input type="text" name="from"> To: <input type="text" name="to">');
                //$('body').on('change', 'input[name=from], input[name=to]', function(){console.log(this.value)});;
                $('body').on('change', 'input[name=from], input[name=to]', { default_csv_url: csv_url }, filter_by_date);
                $('input[name=from], input[name=to]').datepicker({ dateFormat: 'mm-dd-yy' });
                break;
            case 'age-range':
            case 'gender':
            case 'top-gift':
            case 'top-three':
            case 'top-personality':
                var selector = this.value;
                switch_html(generate_select(this.value));
                //$('body').on('change', 'select[name='+this.value+']', function(){console.log(this.value)});
                $('body').on('change', 'select[name='+this.value+']', { selector: selector, default_csv_url: csv_url }, filter_by_select);
                break;
            default:
                switch_html('');
        }

    })

});

//Delete User Results
jQuery( document ).on( 'click', '#delete-results', function(e) {
    e.preventDefault();
    if (confirm("Are you sure you want to delete your results?")) {

        var user_id = jQuery(this).data('user');
        var verify = jQuery(this).data('verify');
        console.log(user_id);
        jQuery.ajax({
            url: ajax.url,
            type: 'post',
            data: {
                action: 'sgt_delete_results',
                user_id: user_id,
                verify: verify
            }
        }).done(function (response) {
            if(response.success) {
                console.log(response);
                window.location.href = ajax.myaccount;
            } else {
                console.log('An error has occurred');
            }
        });
    }

    return false;
});

//Disconnect User Results
jQuery( document ).on( 'click', '.disconnect-user', function(e) {
    e.preventDefault();
    if (confirm("Are you sure you want to remove this user (" + jQuery(this).data('name') + " ) from your church?")) {

        var user_id = jQuery(this).data('user');
        var verify = jQuery(this).data('verify');
        var type = jQuery(this).data('type');
        var parent = jQuery(this).closest('.church-results-row');
        console.log(user_id);
        jQuery.ajax({
             url: ajax.url,
             type: 'post',
             data: {
             action: 'sgt_disconnect_user',
             user_id: user_id,
             verify: verify,
             etype: type
         }
         }).done(function (response) {
            if(response.success) {
                parent.remove();
                console.log(parent);
                console.log(response);
            } else {
                console.log('An error has occurred');
                console.log(response);
            }
        });
    }

    return false;
});

//Delete Manual Entry
jQuery( document ).on( 'click', '.delete-manual-entry', function(e) {
    e.preventDefault();
    if (confirm("Are you sure you want to delete this manual entry (" + jQuery(this).data('name') + " ) from your church?")) {

        var user_id = jQuery(this).data('user');
        var verify = jQuery(this).data('verify');
        var type = jQuery(this).data('type');
        var hash = jQuery(this).data("hash");
        var parent = jQuery(this).closest('.church-results-row');
        console.log(user_id);
        jQuery.ajax({
             url: ajax.url,
             type: 'post',
             data: {
             action: 'sgt_delete_manual_entry',
             user_id: user_id,
             verify: verify,
             etype: type,
             hash: hash,
         }
         }).done(function (response) {
            if(response.success) {
                parent.remove();
                console.log(parent);
                console.log(response);
            } else {
                console.log('An error has occurred');
                console.log(response);
            }
        });
    }

    return false;
});
