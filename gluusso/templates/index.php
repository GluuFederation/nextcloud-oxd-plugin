<?php
    script('gluusso', 'jquery');
	style('gluusso', 'style');
	style('gluusso', 'bootstrap');
	script('gluusso', 'bootstrap');
	$base_url                   = $_['base_url'];
	$get_scopes                 = $_['gluu_scopes'];
	$gluu_config                = $_['gluu_config'];
	$gluu_acr                   = $_['gluu_acr'];
	$gluu_auth_type             = $_['gluu_auth_type'];
	$gluu_send_user_check       = $_['gluu_send_user_check'];
	$gluu_provider              = $_['gluu_provider'];
	$gluu_user_role             = $_['gluu_user_role'];
	$gluu_custom_logout         = $_['gluu_custom_logout'];
	$gluu_new_roles             = $_['gluu_new_roles'];
	$gluu_users_can_register    = $_['gluu_users_can_register'];
	$gluu_is_oxd_registered     = $_['gluu_is_oxd_registered'];
	
	$message_error              = $_['message_error'];
	$message_success            = $_['message_success'];
	$openid_error               = $_['openid_error'];
?>
<script nonce="<?php p(\OC::$server->getContentSecurityPolicyNonceManager()->getNonce()) ?>" type="application/javascript">


    jQuery(document ).ready(function() {
        $edit_cancel_function = $('#delete_it');
        var formSubmitting = false;
        $edit_cancel_function.on('click', function() { return confirm("Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP."); });
        jQuery('[data-toggle="tooltip"]').tooltip();
        jQuery('#p_role').on('click', 'a.remrole', function() {
            jQuery(this).parents('.role_p').remove();
        });
			
			<?php
			if($gluu_users_can_register == 2){
			?>
        jQuery("#p_role").children().prop('disabled',false);
        jQuery("#p_role *").prop('disabled',false);
			<?php
			}else if($gluu_users_can_register == 3){
			?>
        jQuery("#p_role").children().prop('disabled',true);
        jQuery("#p_role *").prop('disabled',true);
        jQuery("input[name='gluu_new_role[]']").each(function(){
            var striped = jQuery('#p_role');
            var value =  jQuery(this).attr("value");
            jQuery('<p><input type="hidden" name="gluu_new_role[]"  value= "'+value+'"/></p>').appendTo(striped);
        });
        jQuery("#UserType").prop('disabled',true);
			<?php
			}else{
			?>
        jQuery("#p_role").children().prop('disabled',true);
        jQuery("#p_role *").prop('disabled',true);
        jQuery("input[name='gluu_new_role[]']").each(function(){
            var striped = jQuery('#p_role');
            var value =  jQuery(this).attr("value");
            jQuery('<p><input type="hidden" name="gluu_new_role[]"  value= "'+value+'"/></p>').appendTo(striped);
        });
			<?php
			}
			?>
        jQuery('input:radio[name="gluu_users_can_register"]').change(function(){
            if(jQuery(this).is(':checked') && jQuery(this).val() == '2'){
                jQuery("#p_role").children().prop('disabled',false);
                jQuery("#p_role *").prop('disabled',false);
                jQuery("input[type='hidden'][name='gluu_new_role[]']").remove();
                jQuery("#UserType").prop('disabled',false);
            }else if(jQuery(this).is(':checked') && jQuery(this).val() == '3'){
                jQuery("#p_role").children().prop('disabled',true);
                jQuery("#p_role *").prop('disabled',true);
                jQuery("input[type='hidden'][name='gluu_new_role[]']").remove();
                jQuery("input[name='gluu_new_role[]']").each(function(){
                    var striped = jQuery('#p_role');
                    var value =  jQuery(this).attr("value");
                    jQuery('<p><input type="hidden" name="gluu_new_role[]"  value= "'+value+'"/></p>').appendTo(striped);
                });
                jQuery("#UserType").prop('disabled',true);
            }else{
                jQuery("#p_role").children().prop('disabled',true);
                jQuery("#p_role *").prop('disabled',true);
                jQuery("input[type='hidden'][name='gluu_new_role[]']").remove();
                jQuery("input[name='gluu_new_role[]']").each(function(){
                    var striped = jQuery('#p_role');
                    var value =  jQuery(this).attr("value");
                    jQuery('<p><input type="hidden" name="gluu_new_role[]"  value= "'+value+'"/></p>').appendTo(striped);
                });
                jQuery("#UserType").prop('disabled',false);
            }
        });
        jQuery("input[name='scope[]']").change(function(){
            var form=$("#scpe_update");
            if (jQuery(this).is(':checked')) {
                jQuery.ajax({
                    url: window.location,
                    type: 'POST',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }else{
                jQuery.ajax({
                    url: window.location,
                    type: 'POST',
                    data:form.serialize(),
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
            }
        });
        jQuery('#p_role').on('click', '.remrole', function() {
            jQuery(this).parents('.role_p').remove();
        });
    });
</script>
<div id="app">
    <div style="margin: 30px">
        <?php print_unescaped($this->inc('content/index')); ?>
    </div>
</div>

<?php
	script('gluusso', 'script');
?>

