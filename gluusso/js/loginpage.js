/**
 * Created by Volodya on 1/26/2017.
 */

jQuery(document).ready(function() {
    jQuery('form').hide();
    jQuery('input:radio[name="radio"]').change(
        function(){
            if (jQuery(this).is(':checked') && jQuery(this).val() == 'Yes') {
                jQuery('#gluu_login').show();
                jQuery('form').hide();
            }else{
                jQuery('#gluu_login').hide();
                jQuery('form').show();
            }
        });
    jQuery('#OpenID').attr('checked', true);
});