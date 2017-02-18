<?php
	
	/**
	 * @copyright Copyright (c) 2017, Gluu Inc. (https://gluu.org/)
	 * @license	  MIT   License            : <http://opensource.org/licenses/MIT>
	 *
	 * @package	  OpenID Connect APP by Gluu
	 * @category  Application for NextCloud
	 * @version   2.4.4
	 *
	 * @author    Gluu Inc.          : <https://gluu.org>
	 * @link      Oxd site           : <https://oxd.gluu.org>
	 * @link      Documentation      : <https://oxd.gluu.org/docs/2.4.4/plugin/nextcloud/>
	 * @director  Mike Schwartz      : <mike@gluu.org>
	 * @support   Support page       : <support@gluu.org>
	 * @developer Volodya Karapetyan : <mr.karapetyan88@gmail.com>
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
	
	use OCP\AppFramework\App;
		
	$app = new App('gluusso');
	$container = $app->getContainer();
	$user = \OC::$server->getUserSession()->getUser();
	if ($user and \OC::$server->getGroupManager()->isAdmin($user->getUID())) {
		$container->query('OCP\INavigationManager')->add(function () use ($container) {
			$urlGenerator = $container->query('OCP\IURLGenerator');
			$l10n = $container->query('OCP\IL10N');
			return [
				// the string under which your app will be referenced in Nextcloud
				'id' => 'gluusso',
				
				// sorting weight for the navigation. The higher the number, the higher
				// will it be listed in the navigation
				'order' => 10,
				
				// the route that will be shown on startup
				'href' => $urlGenerator->linkToRoute('gluusso.page.index'),
				
				// the icon that will be shown in the navigation
				// this file needs to exist in img/
				'icon' => $urlGenerator->imagePath('gluusso', 'gl.png'),
				
				// the title of your application. This will be used in the
				// navigation or on the settings page of your app
				'name' => $l10n->t('OpenID Connect SSO'),
			];
		});
	}
	
	$userSession = \OC::$server->getUserSession();
	$urlGenerator = \OC::$server->getURLGenerator();
	$container = $app->getContainer();
	$redirectSituation = false;
	$redirectLogout = false;
	if(!$userSession->isLoggedIn() &&\OC::$server->getRequest()->getPathInfo() === '/login'){
		$redirectSituation = true;
	}
	if($userSession->isLoggedIn() &&\OC::$server->getRequest()->getPathInfo() === '/logout'){
		$redirectLogout = true;
	}
	
	if($redirectSituation === true) {
		$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
		header('Location: '.$urlGenerator->linkToRouteAbsolute('gluusso.page.loginpage') .'?requesttoken='. urlencode($csrfToken->getEncryptedValue()));
		exit();
	}
	if($redirectLogout === true) {
		if (isset($_SESSION['session_in_op'])) {
			//$csrfToken = \OC::$server->getCsrfTokenManager()->getToken();
			header('Location: '.$urlGenerator->linkToRouteAbsolute('gluusso.page.logoutfromopenid'));
			exit();
		}
		
	}
