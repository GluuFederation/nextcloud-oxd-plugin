<?php
    
    /**
     * @copyright Copyright (c) 2017, Gluu Inc. (https://gluu.org/)
     * @license	  MIT   License            : <http://opensource.org/licenses/MIT>
     *
     * @package	  OpenID Connect SSO APP by Gluu
     * @category  Application for NextCloud
     * @version   3.0.0
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
            <li id="account_setup"><a href="<?php echo $base_url;?>">General</a></li>
            <?php if ( !$gluu_is_oxd_registered) {?>
                <li class="active" id="social-sharing-setup"><button disabled >OpenID Connect Configuration</button></li>
            <?php }else {?>
                <li class="active" id="social-sharing-setup"><a href="<?php echo $base_url;?>openidconfig/">OpenID Connect Configuration</a></li>
            <?php }?>
            <li id=""><a data-method="#configopenid" href="https://oxd.gluu.org/docs/plugin/nextcloud/" target="_blank">Documentation</a></li>
        </ul>
        <div class="container-page">
            <div id="configopenid" style="padding: 20px !important;">
                <form action="<?php echo $base_url;?>gluupostdata" method="post" id="scpe_update">
                    <input type="hidden" name="form_key" value="openid_config_page"/>
                    <fieldset style="border: 2px solid #53cc6b; padding: 20px">
                        <legend style="border-bottom:none; width: 110px !important;">
                            <img style=" height: 45px;" src="<?php echo image_path('gluusso', 'gl1.png'); ?>"/>
                        </legend>
                        <h1 style="margin-left: 30px;padding-bottom: 20px; border-bottom: 2px solid black; width: 75% ">User Scopes</h1>
                        <div >
                            <table style="margin-left: 30px" class="form-table">
                                <tr style="border-bottom: 1px solid green !important;">
                                    <th style="width: 200px; padding: 0px">
                                        <p style="text-align: left;" id="scop_section">
                                            Requested scopes
                                            <a data-toggle="tooltip" class="tooltipLink" data-original-title="Scopes are bundles of attributes that the OP stores about each user. It is recommended that you request the minimum set of scopes required">
                                                <span class="glyphicon glyphicon-info-sign"></span>
                                            </a>
                                        </p>
                                    </th>
                                    <td style="width: 200px; padding-left: 10px !important">
                                        <table id="table-striped" class="form-table" >
                                            <tbody style="width: inherit !important;">
                                            <tr style="padding: 0px">
                                                <td style="padding: 0px !important; width: 10%">
                                                    <p >
                                                        <input checked type="checkbox" name=""  id="openid" value="openid"  disabled />

                                                    </p>
                                                </td>
                                                <td style="padding: 0px !important; width: 70%">
                                                    <p >
                                                        <input type="hidden"  name="scope[]"  value="openid" />openid
                                                    </p>
                                                </td>
                                                <td style="padding: 0px !important;  width: 20%">
                                                    <a class="btn btn-danger btn-xs" style="margin: 5px; float: right" disabled><span class="glyphicon glyphicon-trash"></span></a>
                                                </td>
                                            </tr>
                                            <tr style="padding: 0px">
                                                <td style="padding: 0px !important; width: 10%">
                                                    <p >
                                                        <input checked type="checkbox" name=""  id="email" value="email"  disabled />

                                                    </p>
                                                </td>
                                                <td style="padding: 0px !important; width: 70%">
                                                    <p >
                                                        <input type="hidden"  name="scope[]"  value="email" />email
                                                    </p>
                                                </td>
                                                <td style="padding: 0px !important;  width: 20%">
                                                    <a class="btn btn-danger btn-xs" style="margin: 5px; float: right" disabled><span class="glyphicon glyphicon-trash"></span></a>
                                                </td>
                                            </tr>
                                            <tr style="padding: 0px">
                                                <td style="padding: 0px !important; width: 10%">
                                                    <p >
                                                        <input checked type="checkbox" name=""  id="profile" value="profile"  disabled />

                                                    </p>
                                                </td>
                                                <td style="padding: 0px !important; width: 70%">
                                                    <p >
                                                        <input type="hidden"  name="scope[]"  value="profile" />profile
                                                    </p>
                                                </td>
                                                <td style="padding: 0px !important;  width: 20%">
                                                    <a class="btn btn-danger btn-xs" style="margin: 5px; float: right" disabled><span class="glyphicon glyphicon-trash"></span></a>
                                                </td>
                                            </tr>
                                            <?php foreach($get_scopes as $scop) :?>
                                                <?php if ($scop == 'openid' or $scop == 'email' or $scop == 'profile'){?>
                                                <?php } else{?>
                                                    <tr style="padding: 0px">
                                                        <td>
                                                            <p id="<?php echo $scop;?>1">
                                                                <input <?php if($gluu_config && in_array($scop, $gluu_config['config_scopes'])){ echo "checked";} ?> type="checkbox" name="scope[]"  id="<?php echo $scop;?>1" value="<?php echo $scop;?>" <?php if (!$gluu_is_oxd_registered || $scop=='openid') echo ' disabled '; ?> />
                                                            </p>
                                                        </td>
                                                        <td>
                                                            <p id="<?php echo $scop;?>1">
                                                                <?php echo $scop;?>
                                                            </p>
                                                        </td>
                                                        <td style="padding: 0px !important; ">
                                                            <button type="button" class="btn btn-danger btn-xs delete_scopes" style="margin: 5px; float: right" value="<?php echo $scop;?>" ><span class="glyphicon glyphicon-trash"></span></button>
                                                        </td>
                                                    </tr>
                                                <?php } endforeach;?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                <tr style="border-bottom: 1px solid green !important;">
                                    <th>
                                        <p style="text-align: left;" id="scop_section1">
                                            Add scopes
                                        </p>
                                    </th>
                                    <td>
                                        <div style="margin-left: 10px" id="p_scents">
                                            <p>
                                                <input <?php if(!$gluu_is_oxd_registered) echo 'disabled'?> class="form-control" type="text" id="new_scope_field" name="new_scope[]" placeholder="Input scope name" />
                                            </p>
                                            <br/>
                                            <p>
                                                <input type="button" style="width: 80px" class="btn btn-primary btn-large" value="Add" id="add_new_scope"/>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <br/>
                        <h1 style="margin-left: 30px;padding-bottom: 20px; border-bottom: 2px solid black; width: 75%">Authentication</h1>
                        <br/>
                        <p style=" margin-left: 30px !important; font-weight:bold "><label style="display: inline !important; "><input type="checkbox" name="send_user_check" id="send_user" value="1" <?php if(!$gluu_is_oxd_registered) echo 'disabled'?> <?php if( $gluu_send_user_check) echo 'checked';?> /> <span>Bypass the local NextCloud login page and send users straight to the OP for authentication</span></label>
                        </p>
                        <br/>
                        <div>
                            <table style="margin-left: 30px" class="form-table">
                                <tbody>
                                <tr>
                                    <th style="width: 200px; padding: 0px; ">
                                        <p style="text-align: left;" id="scop_section">
                                            Select ACR: <a data-toggle="tooltip" class="tooltipLink" data-original-title="The OpenID Provider may make available multiple authentication mechanisms. To signal which type of authentication should be used for access to this site you can request a specific ACR. To accept the OP's default authentication, set this field to none.">
                                                <span class="glyphicon glyphicon-info-sign"></span>
                                            </a>
                                        </p>
                                    </th>
                                    <td >
                                        <?php
                                            $custom_scripts = $gluu_acr;
                                            if(!empty($custom_scripts)){
                                                ?>
                                                <select style="margin-left: 5px; padding: 0px !important; width: 200px"  name="send_user_type" id="send_user_type" <?php if(!$gluu_is_oxd_registered) echo 'disabled'?>>
                                                    <option value="default">none</option>
                                                    <?php
                                                        if($custom_scripts){
                                                            foreach($custom_scripts as $custom_script){
                                                                if($custom_script != "default" and $custom_script != "none"){
                                                                    ?>
                                                                    <option <?php if($gluu_auth_type == $custom_script) echo 'selected'; ?> value="<?php echo $custom_script;?>"><?php echo $custom_script;?></option>
                                                                    <?php
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            <?php } ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th >
                                        <input type="submit" class="btn btn-primary btn-large" <?php if(!$gluu_is_oxd_registered) echo 'disabled'?> value="Save Authentication Settings" name="set_oxd_config" />
                                    </th>
                                    <td>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
</div>

