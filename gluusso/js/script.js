/*
 * Copyright (c) 2014
 * @copyright Copyright (c) 2016, Björn Schießle <bjoern@schiessle.org>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global FileActions, Files, FileList */
/* global dragOptions, folderDropOptions */


$(document).ready(function () {
    jQuery('[data-toggle="tooltip"]').tooltip();
    jQuery('#p_role').on('click', 'a.remrole', function() {
        jQuery(this).parents('.role_p').remove();
    });
    $p_role = $('#p_role');
    $delete_scopes = $('td');
    $add_scope_for_delete = $('#add_new_scope');
    $confirm = $('#confirm');
    $scope = $("input[name='scope[]']");

    $p_role.on('click', '.add_new_role', function () {
        var wrapper1 = '<p class="role_p" style="padding-top: 10px">' +
            '<input class="form-control"  type="text" name="gluu_new_role[]" placeholder="Input role name" style="display: inline; width: 200px !important; margin-right: 5px"/>' +
            '<button type="button" class="btn btn-xs add_new_role" ><span class="glyphicon glyphicon-plus"></span></button> ' +
            '<button type="button" class="btn btn-xs remrole" ><span class="glyphicon glyphicon-minus"></span></button>' +
            '</p>';
        jQuery(wrapper1).find('.remrole').on('click', function () {
            jQuery(this).parent('.role_p').remove();
        });
        jQuery(wrapper1).appendTo('#p_role');
    });
    $delete_scopes.on('click', '.delete_scopes',  function () {
        if (confirm("Are you sure that you want to delete this scope? You will no longer be able to request this user information from the OP.")) {
            $.ajax({
                url: OC.generateUrl('/apps/gluusso/gluupostdataajax'),
                type: 'post',
                data:{
                    form_key_scope_delete:'form_key_scope_delete',
                    delete_scope:$(this).attr('value')
                },
                success: function(result){
                    location.reload();
                }});
        }
        else{
            return false;
        }
    });
    $add_scope_for_delete.on('click',  function () {
        var striped = jQuery('#table-striped');
        var k = jQuery('#p_scents p').size() + 1;
        var new_scope_field = jQuery('#new_scope_field').val();
        var m = true;
        if(new_scope_field){
            jQuery("input[name='scope[]']").each(function(){
                // get name of input
                var value =  jQuery(this).attr("value");
                if(value == new_scope_field){
                    m = false;
                }
            });
            if(m){
                jQuery('<tr >' +
                    '<td style="padding: 0px !important;">' +
                    '   <p  id="'+new_scope_field+'">' +
                    '     <input type="checkbox" name="scope[]" id="new_'+new_scope_field+'" value="'+new_scope_field+'"  />'+
                    '   </p>' +
                    '</td>' +
                    '<td style="padding: 0px !important;">' +
                    '   <p  id="'+new_scope_field+'">' +
                    new_scope_field+
                    '   </p>' +
                    '</td>' +
                    '<td style="padding: 0px !important; ">' +
                    '   <a href="#scop_section" class="btn btn-danger btn-xs" style="margin: 5px; float: right" onclick="delete_scopes(\''+new_scope_field+'\')" >' +
                    '<span class="glyphicon glyphicon-trash"></span>' +
                    '</a>' +
                    '</td>' +
                    '</tr>').appendTo(striped);
                jQuery('#new_scope_field').val('');

                $.ajax({
                    url: OC.generateUrl('/apps/gluusso/gluupostdataajax'),
                    type: 'post',
                    data:{form_key_scope:'oxd_openid_config_new_scope', new_value_scope:new_scope_field},
                    success: function(result){
                        if(result){
                            return false;
                        }
                    }});
                jQuery("#new_"+new_scope_field).change(
                    function(){
                        var form=$("#scpe_update");
                        if (jQuery(this).is(':checked')) {
                            $.ajax({
                                url: OC.generateUrl('/apps/gluusso/gluupostdataajax'),
                                type: 'post',
                                data:form.serialize(),
                                success: function(result){
                                    if(result){
                                        return false;
                                    }
                                }});
                        }else{
                            $.ajax({
                                url: OC.generateUrl('/apps/gluusso/gluupostdataajax'),
                                type: 'post',
                                data:form.serialize(),
                                success: function(result){
                                    if(result){
                                        return false;
                                    }
                                }});
                        }
                    });

                return false;
            }
            else{
                alert('The scope named '+new_scope_field+' is exist!');
                jQuery('#new_scope_field').val('');
                return false;
            }
        }else{
            alert('Please input scope name!');
            jQuery('#new_scope_field').val('');
            return false;
        }
    });
    $scope.on('change',function(){
        var form=$("#scpe_update");
        if (jQuery(this).is(':checked')) {
            $.ajax({
                url: OC.generateUrl('/apps/gluusso/gluupostdataajax'),
                type: 'post',
                data:form.serialize(),
                success: function(result){
                    if(result){
                        return false;
                    }
                }});
        }else{
            $.ajax({
                url: OC.generateUrl('/apps/gluusso/gluupostdataajax'),
                type: 'post',
                data:form.serialize(),
                success: function(result){
                    if(result){
                        return false;
                    }
                }});
        }
    });

    $confirm.on('click',  function (){
        return confirm('Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.')
    });

});

