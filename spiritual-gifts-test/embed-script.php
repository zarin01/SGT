<?php
/*
 * Template Name: Embed Script
 */
$church_id = get_query_var('church-id');
?>

var d = new Date();
d.setTime(d.getTime() + 30*60*1000); // in milliseconds

var is_safari = navigator.userAgent.indexOf("Safari") > -1;
// Chrome has Safari in the user agent so we need to filter (https://stackoverflow.com/a/7768006/1502448)
var is_chrome = navigator.userAgent.indexOf('Chrome') > -1;
if ((is_chrome) && (is_safari)) {is_safari = false;} 
if (is_safari) {
        // See if cookie exists (https://stackoverflow.com/a/25617724/1502448)
        if (!document.cookie.match(/^(.*;)?\s*fixed\s*=\s*[^;]+(.*)?$/)) {
            // Set cookie to maximum (https://stackoverflow.com/a/33106316/1502448)
            document.cookie = 'fixed=fixed; expires='+d.toGMTString()+'; path=/';
            window.location ="<?php echo site_url('cookie');?>";
        }
}

/*var iframe = document.createElement('iframe');
iframe.src = '<?php echo site_url('/embed-2/church-id/'.$church_id); ?>';
iframe.setAttribute('id','sgt-iframe');
iframe.setAttribute('style', 'min-height: 98vh; width: 100%;');
iframe.setAttribute('frameborder', '0');

var script = document.getElementById('sgt-embed');
script.parentNode.insertBefore(iframe, script.nextSibling );
*/