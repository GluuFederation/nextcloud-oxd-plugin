<?php
	/**
	 * @copyright Copyright (c) 2017, Gluu Inc. (https://gluu.org/)
	 * @license	  MIT   License            : <http://opensource.org/licenses/MIT>
	 *
	 * @package	  OpenID Connect SSO APP by Gluu
	 * @category  Application for NextCloud
	 * @version   2.4.4
	 *
	 * @author    Gluu Inc.          : <https://gluu.org>
	 * @link      Oxd site           : <https://oxd.gluu.org>
	 * @link      Documentation      : <https://oxd.gluu.org/docs/plugin/nextcloud/>
	 * @director  Mike Schwartz      : <mike@gluu.org>
	 * @support   Support email      : <support@gluu.org>
	 * @developer Volodya Karapetyan : <https://github.com/karapetyan88> <mr.karapetyan88@gmail.com>
	 *
	 *
	 * This content is released under the MIT License (MIT)
	 *
	 * Copyright (c) 2017, Gluu inc, USA, Austin
	 *
	 * Permission is hereby granted, free of charge, to any person obtaining a copy
	 * of this software and associated documentation files (the "Software"), to deal
	 * in the Software without restriction, including without limitation the rights
	 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	 * copies of the Software, and to permit persons to whom the Software is
	 * furnished to do so, subject to the following conditions:
	 *
	 * The above copyright notice and this permission notice shall be included in
	 * all copies or substantial portions of the Software.
	 *
	 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	 * THE SOFTWARE.
	 *
	 */

    /** @var \OCP\IL10N $l */
    /** @var array $_ */
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
	$groups                     = $_['groups'];
	
	$message_error              = $_['message_error'];
	$message_success            = $_['message_success'];
	$openid_error               = $_['openid_error'];

?>
<div class="mo2f_container">
    <div class="container">
        <div id="messages">
            <?php if (!empty($message_error)){ ?>
                <div class="mess_red_error">
                    <?php echo $message_error; ?>
                </div>
            <?php } ?>
            <?php if (!empty($message_success)) { ?>
                <div class="mess_green">
                    <?php echo $message_success; ?>
                </div>
            <?php } ?>
        </div>
        <br/>
        <ul class="navbar navbar-tabs">
            <li class="active" id="account_setup"><a data-method="#accountsetup">General</a></li>
            <?php if ( !$gluu_is_oxd_registered) {?>
                <li id="social-sharing-setup"><a style="pointer-events: none; cursor: default;" >OpenID Connect Configuration</a></li>
            <?php }else {?>
                <li id="social-sharing-setup"><a href="openidconfig/">OpenID Connect Configuration</a></li>
            <?php }?>
            <li id=""><a data-method="#configopenid" href="https://oxd.gluu.org/docs/plugin/nextcloud/" target="_blank">Documentation</a></li>
        </ul>
        <div class="container-page">
            <!-- General -->
            <?php if (!$gluu_is_oxd_registered) { ?>
                <!-- General tab-->
                <div class="page" id="accountsetup">
                    <div class="mo2f_table_layout">
                        <form id="register_GluuOxd" name="f" method="post" action="gluupostdata">
                            <input type="hidden" name="form_key" value="general_register_page"/>
                            <div class="login_GluuOxd">
                                <br/>
                                <div  style="padding-left: 20px;">Register your site with any standard OpenID Provider (OP). If you need an OpenID Provider you can deploy the <a target="_blank" href="https://gluu.org/docs/deployment/"> free open source Gluu Server.</a></div>
                                <hr>
                                <div style="padding-left: 20px;">This plugin relies on the oxd mediator service. For oxd deployment instructions and license information, please visit the <a target="_blank" href="https://oxd.gluu.org">oxd website.</a></div>
                                <hr>
                                <div style="padding-left: 20px;">
                                    <h3 style=" font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%; font-weight: bold ">Server Settings</h3>
                                    <table class="table">
                                        <tr>
                                            <td style="width: 250px"><b>URI of the OpenID Provider:</b></td>
                                            <td><input class="" type="url" name="gluu_provider" id="gluu_provider"
                                                       autofocus="true"  placeholder="Enter URI of the OpenID Provider."
                                                       style="width:400px;"
                                                       value="<?php echo $gluu_provider;?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 250px"><b>Custom URI after logout:</b></td>
                                            <td><input class="" type="url" name="gluu_custom_logout" id="gluu_custom_logout"
                                                       autofocus="true"  placeholder="Enter custom URI after logout"
                                                       style="width:400px;"
                                                       value="<?php echo $gluu_custom_logout;?>"/>
                                            </td>
                                        </tr>
                                        <?php if(!empty($openid_error)){?>
                                            <tr>
                                                <td style="width: 250px"><b><font color="#FF0000">*</font>Redirect URL:</b></td>
                                                <td><input class="" type="url" name="gluu_redirect_url" id="gluu_redirect_url"
                                                           autofocus="true" placeholder="Your redirect URL." disabled
                                                           style="width:400px;"
                                                           value="<?php echo $gluu_config['authorization_redirect_uri'];?>"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 250px"><b><font color="#FF0000">*</font>Client ID:</b></td>
                                                <td><input class="" type="text" name="gluu_client_id" id="gluu_client_id"
                                                           autofocus="true" placeholder="Enter OpenID Provider client ID."
                                                           style="width:400px;"
                                                           value="<?php if(!empty($gluu_config['gluu_client_id'])) echo $gluu_config['gluu_client_id']; ?>"/>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 250px"><b><font color="#FF0000">*</font>Client Secret:</b></td>
                                                <td>
                                                    <input class="" type="text" name="gluu_client_secret" id="gluu_client_secret"
                                                           autofocus="true" placeholder="Enter OpenID Provider client secret."  style="width:400px;" value="<?php if(!empty($gluu_config['gluu_client_secret'])) echo $gluu_config['gluu_client_secret']; ?>"/>
                                                </td>
                                            </tr>
                                        <?php }?>
                                        <tr>
                                            <td style="width: 250px"><b><font color="#FF0000">*</font>oxd port:</b></td>
                                            <td>
                                                <input class="" type="number" name="gluu_oxd_port" min="0" max="65535"
                                                       value="<?php echo $gluu_config['gluu_oxd_port']; ?>"
                                                       style="width:400px;" placeholder="Please enter free port (for example 8099). (Min. number 0, Max. number 65535)."/>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div style="padding-left: 20px">
                                    <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%;">Enrollment and Access Management
                                        <a data-toggle="tooltip" class="tooltipLink" data-original-title="Choose whether to register new users when they login at an external identity provider. If you disable automatic registration, new users will need to be manually created">
                                            <span class="glyphicon glyphicon-info-sign"></span>
                                        </a>
                                    </h3>
                                    <div class="radio">
                                        <p><label><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register" <?php if($gluu_users_can_register==1){ echo "checked";} ?> value="1" style="margin-right: 3px"><b> Automatically register any user with an account in the OpenID Provider</b></label></p>
                                    </div>
                                    <div class="radio">
                                        <p><label ><input name="gluu_users_can_register" type="radio" id="gluu_users_can_register_1" <?php if($gluu_users_can_register==2){ echo "checked";} ?> value="2" style="margin-right: 3px"> <b>Only register and allow ongoing access to users with one or more of the following roles in the OpenID Provider</b></label></p>
                                        <div style="margin-left: 20px;">
                                            <div id="p_role" >
                                                <?php $k=0;
                                                    if(!empty($gluu_new_roles)) {
                                                        foreach ($gluu_new_roles as $gluu_new_role) {
                                                            if (!$k) {
                                                                $k++;
                                                                ?>
                                                                <p class="role_p" style="padding-top: 10px">
                                                                    <input  type="text" name="gluu_new_role[]" required class="form-control" style="display: inline; width: 200px !important; "
                                                                            placeholder="Input role name"
                                                                            value="<?php echo $gluu_new_role; ?>"/>
                                                                    <button type="button" class="btn btn-xs add_new_role"><span class="glyphicon glyphicon-plus"></span></button>
                                                                </p>
                                                                <?php
                                                            } else {
                                                                ?>
                                                                <p class="role_p" style="padding-top: 10px">
                                                                    <input type="text" name="gluu_new_role[]" required class="form-control" style="display: inline; width: 200px !important; "
                                                                           placeholder="Input role name"
                                                                           value="<?php echo $gluu_new_role; ?>"/>
                                                                    <button type="button"  class="btn btn-xs add_new_role"><span class="glyphicon glyphicon-plus"></span></button>
                                                                    <button type="button"  class="btn btn-xs remrole"><span class="glyphicon glyphicon-minus"></span></button>
                                                                </p>
                                                            <?php }
                                                        }
                                                    }else{
                                                        ?>
                                                        <p class="role_p" style="padding-top: 10px">
                                                            <input type="text" name="gluu_new_role[]" required class="form-control" placeholder="Input role name" style="display: inline; width: 200px !important; " value=""/>
                                                            <button  type="button" class="btn btn-xs add_new_role"><span class="glyphicon glyphicon-plus"></span></button>
                                                        </p>
                                                        <?php
                                                    }?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="radio">
                                        <p>
                                            <label >
                                                <input name="gluu_users_can_register" type="radio" id="gluu_users_can_register_2" <?php if($gluu_users_can_register==3){ echo "checked";} ?> value="3" style="margin-right: 3px">
                                                <b>Disable automatic registration</b>
                                            </label>
                                        </p>
                                    </div>
                                    <table class="table">
                                        <tr>
                                            <td style="width: 250px"><b>New User Default Group:</b></td>
                                            <td>
                                                <div class="form-group" style="margin-bottom: 0px !important;">
                                                    <select id="UserType" style="width: 200px" name="gluu_user_role" >
                                                        <?php
                                                            foreach($groups as $user_type){
                                                                ?>
                                                                <option <?php if($user_type == $gluu_user_role) echo 'selected'; ?> value="<?php echo $user_type;?>"><?php echo $user_type;?></option>
                                                                <?php
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php if(!empty($openid_error)){?>
                                            <tr>
                                                <td style="width: 250px">
                                                    <div><input class="button button-primary button-large" type="submit" name="register" value="Register" style=";width: 120px; float: right;"/></div>
                                                </td>
                                                <td>
                                                    <div><a class="button button-danger button-large" onclick="return confirm('Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.')" style="background-color:red;text-decoration: none;text-align:center; float: left; width: 120px;" href="gluupostdataget&submit=delete">Delete</a></div>
                                                </td>
                                            </tr>
                                        <?php }
                                        else{?>
                                            <tr>
                                                <?php if(!empty($gluu_provider)){?>
                                                    <td style="width: 250px">
                                                        <div><input type="submit" name="register" value="Register" style="width: 120px; float: right;" class="button button-primary button-large"/></div>
                                                    </td>
                                                    <td>
                                                        <a class="button button-primary button-large" onclick="return confirm('Are you sure that you want to remove this OpenID Connect provider? Users will no longer be able to authenticate against this OP.')" style="background-color:red;text-decoration: none;text-align:center; float: left; width: 120px;" href="gluupostdataget&submit=delete">Delete</a>
                                                    </td>
                                                <?php }else{?>
                                                    <td style="width: 250px">
                                                        <div><input type="submit" name="submit" value="Register" style="width: 120px; float: right;" class="button button-primary button-large"/></div>
                                                    </td>
                                                    <td>
                                                    </td>
                                                <?php }?>
                                            </tr>
                                        <?php }?>
                                    </table>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            <?php }
            else{?>
                <!-- General edit tab without client_id and client_secret -->
                <div style="padding: 20px !important;" id="accountsetup">
                    <form id="register_GluuOxd" name="f" method="post" action="gluupostdata">
                        <input type="hidden" name="form_key" value="general_oxd_id_reset"/>
                        <fieldset style="border: 2px solid #53cc6b; padding: 20px">
                            <legend style="border-bottom:none; width: 110px !important;">
                                <img style=" height: 45px;" src="<?php echo image_path('gluusso', 'gl1.png'); ?>"/>
                            </legend>
                            <div style="padding-left: 20px; margin-top: -30px;">
                                <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%; ">Server Settings</h3>
                                <table class="table">
                                    <tr>
                                        <td style="width: 250px"><b>URI of the OpenID Provider:</b></td>
                                        <td><input type="url" name="gluu_provider" id="gluu_provider"
                                                   disabled placeholder="Enter URI of the OpenID Provider."
                                                   style="width:400px;"
                                                   value="<?php echo $gluu_provider; ?>"/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 250px"><b>Custom URI after logout:</b></td>
                                        <td><input class="" type="url" name="gluu_custom_logout" id="gluu_custom_logout"
                                                   autofocus="true" disabled placeholder="Enter custom URI after logout"
                                                   style="width:400px;"
                                                   value="<?php echo $gluu_custom_logout;?>"/>
                                        </td>
                                    </tr>
                                    <?php if(!empty($gluu_config['gluu_client_id']) and !empty($gluu_config['gluu_client_secret'])){?>
                                        <tr>
                                            <td style="width: 250px"><b><font color="#FF0000">*</font>Redirect URL:</b></td>
                                            <td><input class="" type="url" name="gluu_redirect_url" id="gluu_redirect_url"
                                                       autofocus="true" placeholder="Your redirect URL." disabled
                                                       style="width:400px;"
                                                       value="<?php echo $gluu_config['authorization_redirect_uri'];?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 250px"><b><font color="#FF0000">*</font>Client ID:</b></td>
                                            <td><input class="" type="text" name="gluu_client_id" id="gluu_client_id"
                                                       autofocus="true" placeholder="Enter OpenID Provider client ID."
                                                       style="width:400px; background-color: rgb(235, 235, 228);"
                                                       value="<?php if(!empty($gluu_config['gluu_client_id'])) echo $gluu_config['gluu_client_id']; ?>"/>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="width: 250px"><b><font color="#FF0000">*</font>Client Secret:</b></td>
                                            <td>
                                                <input class="" type="text" name="gluu_client_secret" id="gluu_client_secret"
                                                       autofocus="true" placeholder="Enter OpenID Provider client secret."  style="width:400px; background-color: rgb(235, 235, 228);" value="<?php if(!empty($gluu_config['gluu_client_secret'])) echo $gluu_config['gluu_client_secret']; ?>"/>
                                            </td>
                                        </tr>
                                    <?php }?>
                                    <tr>
                                        <td style="width: 250px"><b><font color="#FF0000">*</font>oxd port:</b></td>
                                        <td>
                                            <input class="" type="text" disabled name="gluu_oxd_port" min="0" max="65535"
                                                   value="<?php echo $gluu_config['gluu_oxd_port']; ?>"
                                                   style="width:400px; background-color: rgb(235, 235, 228);" placeholder="Please enter free port (for example 8099). (Min. number 0, Max. number 65535)."/>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 250px"><b>oxd ID:</b></td>
                                        <td>
                                            <input class="" type="text" disabled name="oxd_id"
                                                   value="<?php echo $gluu_is_oxd_registered; ?>"
                                                   style="width:400px;     background-color: rgb(235, 235, 228);" placeholder="Your oxd ID" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div style="padding-left: 20px;">
                                <h3 style="font-weight:bold;padding-left: 10px;padding-bottom: 20px; border-bottom: 2px solid black; width: 60%;">Enrollment and Access Management
                                    <a data-toggle="tooltip" class="tooltipLink" data-original-title="Choose whether to register new users when they login at an external identity provider. If you disable automatic registration, new users will need to be manually created">
                                        <span class="glyphicon glyphicon-info-sign"></span>
                                    </a>
                                </h3>
                                <div>
                                    <p><label><input name="gluu_users_can_register" disabled type="radio" id="gluu_users_can_register" <?php if($gluu_users_can_register==1){ echo "checked";} ?> value="1" style="margin-right: 3px"><b> Automatically register any user with an account in the OpenID Provider</b></label></p>
                                </div>
                                <div>
                                    <p><label ><input name="gluu_users_can_register" disabled type="radio" id="gluu_users_can_register" <?php if($gluu_users_can_register==2){ echo "checked";} ?> value="2" style="margin-right: 3px"><b> Only register and allow ongoing access to users with one or more of the following roles in the OpenID Provider</b></label></p>
                                    <div style="margin-left: 20px;">
                                        <div id="p_role_disabled">
                                            <?php $k=0;
                                                if(!empty($gluu_new_roles)) {
                                                    foreach ($gluu_new_roles as $gluu_new_role) {
                                                        if (!$k) {
                                                            $k++;
                                                            ?>
                                                            <p class="role_p" style="padding-top: 10px">
                                                                <input  type="text" name="gluu_new_role[]" disabled  style="display: inline; width: 200px !important; "
                                                                        placeholder="Input role name" class="form-control"
                                                                        value="<?php echo $gluu_new_role; ?>"/>
                                                                <button type="button" class="btn btn-xs " disabled="true"><span class="glyphicon glyphicon-plus"></span></button>
                                                            </p>
                                                            <?php
                                                        } else {
                                                            ?>
                                                            <p class="role_p" style="padding-top: 10px">
                                                                <input type="text" name="gluu_new_role[]" disabled class="form-control"
                                                                       placeholder="Input role name" style="display: inline; width: 200px !important; "
                                                                       value="<?php echo $gluu_new_role; ?>"/>
                                                                <button type="button" class="btn btn-xs " disabled="true" ><span class="glyphicon glyphicon-plus"></span></button>
                                                                <button type="button" class="btn btn-xs " disabled="true"><span class="glyphicon glyphicon-minus"></span></button>
                                                            </p>
                                                        <?php }
                                                    }
                                                }else{
                                                    ?>
                                                    <p class="role_p" style="padding-top: 10px">
                                                        <input type="text" name="gluu_new_role[]" disabled placeholder="Input role name" class="form-control" style="display: inline; width: 200px !important; " value=""/>
                                                        <button type="button" class="btn btn-xs " disabled="true" ><span class="glyphicon glyphicon-plus"></span></button>
                                                    </p>
                                                    <?php
                                                }?>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p><label><input name="gluu_users_can_register" disabled type="radio" id="gluu_users_can_register_2" <?php if($gluu_users_can_register==3){ echo "checked";} ?> value="3" style="margin-right: 3px"><b>Disable automatic registration</b></label></p>
                                </div>
                                <table class="table">
                                    <tr>
                                        <td style="width: 250px"><b>New User Default Group:</b></td>
                                        <td>
                                            <div class="form-group" style="margin-bottom: 0px !important;">
                                                <select id="UserType" style="width: 200px" name="gluu_user_role" disabled>
			                                            <?php
				                                            foreach($groups as $user_type){
					                                            ?>
                                                        <option <?php if($user_type == $gluu_user_role) echo 'selected'; ?> value="<?php echo $user_type;?>"><?php echo $user_type;?></option>
					                                            <?php
				                                            }
			                                            ?>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 250px">
                                            <a class="button button-primary button-large" style="float:right;text-decoration: none;text-align:center; width: 120px;background-color: #cfcfcf" href="editpage">Edit</a>
                                        </td>
                                        <td>
                                            <input type="submit" id="confirm"  name="resetButton" value="Delete" style="background-color:#ff4008;color:white;text-decoration: none;text-align:center; float: left; width: 120px;" class="button button-danger button-large"/>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </fieldset>
                    </form>
                </div>

            <?php }?>
        </div>
    </div>
</div>
