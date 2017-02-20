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



    vendor_script('jsTimezoneDetect/jstz');
    script('core', [
            'visitortimezone',
            'lostpassword',
            'login'
    ]);
	
    $login_url                   = $_['login_url'];
	$gluu_oxd_id                   = $_['gluu_oxd_id'];
	$gluu_is_port_working                   = $_['gluu_is_port_working'];
    if(!empty($gluu_oxd_id) && $gluu_is_port_working){
	    script('gluusso', 'loginpage');
?>
<?php
if (!empty($_['error_script'])) {
	print_unescaped($_['error_script']);
}
?>
<div style='margin: 10px'><br/><label><h2 style='color:white; font-weight: bold; float: left'><input type='radio' name='radio' id='OpenID' value='Yes' />Login by OpenID Provider </h2></label>
    <br/><br/><label><h2 style='font-weight: bold;color:white;  float: left'><input type='radio' name='radio' id='base' value='No' />Show login form </h2></label><br/></div><br/>
<a href="<?php echo $login_url;?>" style="border-radius: 3px !important; padding: 10px 10px !important;font-weight: bold !important;font-size: 20px !important; margin: 5px !important; width: 269px !important;" class="login primary" id="gluu_login">Login by OpenID Provider</a>
<!--[if IE 8]><style>input[type="checkbox"]{padding:0;}</style><![endif]-->

<?php
        }
?>

<form method="post" name="login">
    <fieldset>
        <?php if (!empty($_['redirect_url'])) {
            print_unescaped('<input type="hidden" name="redirect_url" value="' . \OCP\Util::sanitizeHTML($_['redirect_url']) . '">');
        } ?>
        <?php if (isset($_['apacheauthfailed']) && ($_['apacheauthfailed'])): ?>
            <div class="warning">
                <?php p($l->t('Server side authentication failed!')); ?><br>
                <small><?php p($l->t('Please contact your administrator.')); ?></small>
            </div>
        <?php endif; ?>
        <?php foreach($_['messages'] as $message): ?>
            <div class="warning">
                <?php p($message); ?><br>
            </div>
        <?php endforeach; ?>
        <?php if (isset($_['internalexception']) && ($_['internalexception'])): ?>
            <div class="warning">
                <?php p($l->t('An internal error occurred.')); ?><br>
                <small><?php p($l->t('Please try again or contact your administrator.')); ?></small>
            </div>
        <?php endif; ?>
        <div id="message" class="hidden">
            <img class="float-spinner" alt=""
                 src="<?php p(image_path('core', 'loading-dark.gif'));?>">
            <span id="messageText"></span>
            <!-- the following div ensures that the spinner is always inside the #message div -->
            <div style="clear: both;"></div>
        </div>
        <p class="grouptop<?php if (!empty($_['invalidpassword'])) { ?> shake<?php } ?>">
            <input type="text" name="user" id="user"
                   placeholder="<?php p($l->t('Username or email')); ?>"
                   value="<?php p($_['loginName']); ?>"
                    <?php p($_['user_autofocus'] ? 'autofocus' : ''); ?>
                   autocomplete="on" autocapitalize="off" autocorrect="off" required>
            <label for="user" class="infield"><?php p($l->t('Username or email')); ?></label>
        </p>

        <p class="groupbottom<?php if (!empty($_['invalidpassword'])) { ?> shake<?php } ?>">
            <input type="password" name="password" id="password" value=""
                   placeholder="<?php p($l->t('Password')); ?>"
                    <?php p($_['user_autofocus'] ? '' : 'autofocus'); ?>
                   autocomplete="on" autocapitalize="off" autocorrect="off" required>
            <label for="password" class="infield"><?php p($l->t('Password')); ?></label>
        </p>

        <?php if (!empty($_['invalidpassword']) && !empty($_['canResetPassword'])) { ?>
            <a id="lost-password" class="warning" href="<?php p($_['resetPasswordLink']); ?>">
                <?php p($l->t('Wrong password. Reset it?')); ?>
            </a>
        <?php } else if (!empty($_['invalidpassword'])) { ?>
            <p class="warning">
                <?php p($l->t('Wrong password.')); ?>
            </p>
        <?php } ?>

        <input type="submit" id="submit" class="login primary icon-confirm-white" title="" value="<?php p($l->t('Log in')); ?>" disabled="disabled" />

        <div class="login-additional">
            <?php if ($_['rememberLoginAllowed'] === true) : ?>
                <div class="remember-login-container">
                    <?php if ($_['rememberLoginState'] === 0) { ?>
                        <input type="checkbox" name="remember_login" value="1" id="remember_login" class="checkbox checkbox--white">
                    <?php } else { ?>
                        <input type="checkbox" name="remember_login" value="1" id="remember_login" class="checkbox checkbox--white" checked="checked">
                    <?php } ?>
                    <label for="remember_login"><?php p($l->t('Stay logged in')); ?></label>
                </div>
            <?php endif; ?>
        </div>

        <input type="hidden" name="timezone_offset" id="timezone_offset"/>
        <input type="hidden" name="timezone" id="timezone"/>
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
    </fieldset>
</form>
<?php if (!empty($_['alt_login'])) { ?>
    <form id="alternative-logins">
        <fieldset>
            <legend><?php p($l->t('Alternative Logins')) ?></legend>
            <ul>
                <?php foreach($_['alt_login'] as $login): ?>
                    <li><a class="button" href="<?php print_unescaped($login['href']); ?>" ><?php p($login['name']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </fieldset>
    </form>
<?php }
?>

