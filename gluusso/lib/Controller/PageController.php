<?php
	
	/**
	 * @copyright Copyright (c) 2017, Gluu, Inc.
	 *
	 * @author Vlad Karapetyan <vlad.karapetyan@mail.ru>
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
	 */
	
	namespace OCA\GluuSso\Controller;
	
	use OC_App;
	use OC_Util;
	
	use OC\Accounts\AccountManager;
	use OC\User\User;
	use OC\ForbiddenException;
	use OC\Authentication\TwoFactorAuth\Manager;
	use OC\Authentication\Token\IProvider;
	use OC\Security\Bruteforce\Throttler;
	
	use OCP\AppFramework\Http\RedirectResponse;
	use OCP\AppFramework\Http;
	use OCP\AppFramework\Http\TemplateResponse;
	use OCP\AppFramework\Http\DataResponse;
	use OCP\AppFramework\Controller;
	use OCP\IRequest;
	use OCP\IURLGenerator;
	use OCP\IUserSession;
	use OCP\ISession;
	use OCP\IConfig;
	use OCP\IUser;
	use OCP\IUserManager;
	use OCP\IGroupManager;
	use OCP\IL10N;
	
	use OCA\GluuSso\Provider\GluuManager;
	use OCA\GluuSso\oxdrplib\Get_authorization_url;
	use OCA\GluuSso\oxdrplib\Get_tokens_by_code;
	use OCA\GluuSso\oxdrplib\Get_user_info;
	use OCA\GluuSso\oxdrplib\Logout;
	use OCA\GluuSso\oxdrplib\Register_site;
	use OCA\GluuSso\oxdrplib\Update_site_registration;
	
	class PageController extends Controller
	{
		/** @var IUserManager $manager */
		private $manager;
		
		/** @var IProvider */
		private $tokenProvider;
		
		/** @var IL10N */
		private $l10n;
		
		/** @var AccountManager */
		private $accountManager;
		
		private $userId;
		
		/** @var GluuManager */
		private $mapper;
		
		/** @var IURLGenerator */
		protected $urlGenerator;
		
		/** @var ISession */
		private $session;
		
		/** @var IUserSession */
		private $userSession;
		
		/** @var IConfig */
		private $config;
		
		/** @var Throttler */
		private $throttler;
		
		/** @var Manager */
		private $twoFactorManager;
		
		/** @var IUserManager */
		private $userManager;
		
		/** @var IGroupManager */
		private $groupManager;
		
		/**
		 * @param string $appName
		 * @param string $UserId,
		 * @param IRequest $request
		 * @param IUserManager $userManager
		 * @param IGroupManager $groupManager
		 * @param IUserSession $userSession
		 * @param IConfig $config
		 * @param IL10N $l10n
		 * @param IURLGenerator $urlGenerator
		 * @param GluuManager $mapper
		 * @param ISession $session
		 * @param Manager $twoFactorManager
		 * @param Throttler $throttler
		 * @param AccountManager $accountManager
		 * @param IUserManager $manager
		 *
		 */
		public function __construct($AppName, $UserId, IRequest $request, IUserManager $userManager, IGroupManager $groupManager, IUserSession $userSession, IConfig $config, IL10N $l10n,
		                            IURLGenerator $urlGenerator, GluuManager $mapper, ISession $session, Manager $twoFactorManager,
		                            Throttler $throttler, AccountManager $accountManager, IUserManager $manager)
		{
			parent::__construct($AppName, $request);
			$this->userId = $UserId;
			$this->l10n = $l10n;
			$this->mapper = $mapper;
			$this->manager = $manager;
			$this->urlGenerator = $urlGenerator;
			$this->session = $session;
			$this->config = $config;
			$this->groupManager = $groupManager;
			$this->userSession = $userSession;
			$this->throttler = $throttler;
			$this->twoFactorManager = $twoFactorManager;
			$this->userManager = $userManager;
			$this->accountManager = $accountManager;
		}
		
		/**
		 * Adding necessary parameters , and returning all data for admin panel frontend
		 * @return $param
		 */
		public function getParamsValue()
		{
			$base_url = $this->getBaseUrl();
			$group = array();
			$groups = $this->mapper->select_group_query();
			
			foreach ($groups as $group_key){
				$group[] = $group_key['gid'];
			}
			@session_start();
			if (!$this->mapper->select_query('gluu_scopes')) {
				$get_scopes = json_encode(array("openid", "profile", "email"));
				$result = $this->mapper->insert_query('gluu_scopes', $get_scopes);
			}
			if (!$this->mapper->select_query('gluu_acr')) {
				$custom_scripts = json_encode(array('none'));
				$result = $this->mapper->insert_query('gluu_acr', $custom_scripts);
			}
			if (!$this->mapper->select_query('gluu_config')) {
				$gluu_config = json_encode(array(
					"gluu_oxd_port" => 8099,
					"admin_email" => '',
					"authorization_redirect_uri" => $base_url . 'loginfromopenid',
					"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
					"config_scopes" => ["openid", "profile", "email"],
					"gluu_client_id" => "",
					"gluu_client_secret" => "",
					"config_acr" => []
				));
				$result = $this->mapper->insert_query('gluu_config', $gluu_config);
			}
			if (!$this->mapper->select_query('gluu_other_config')) {
				
				$gluu_other_config = json_encode(array(
					'gluu_auth_type' => 'default',
					'gluu_custom_logout' => '',
					'gluu_provider' => '',
					'gluu_send_user_check' => 0,
					'gluu_oxd_id' => '',
					'gluu_user_role' => 'admin',
					'gluu_new_roles' => array(),
					'gluu_users_can_register' => 1
				));
				
				$result = $this->mapper->insert_query('gluu_other_config', $gluu_other_config);
			}
			$get_scopes = json_decode($this->mapper->select_query('gluu_scopes'), true);
			$gluu_config = json_decode($this->mapper->select_query('gluu_config'), true);
			$gluu_acr = json_decode($this->mapper->select_query('gluu_acr'), true);
			$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
			$gluu_is_oxd_registered = $this->gluu_is_oxd_registered();
			
			$params = [
				'gluu_scopes' => $get_scopes,
				'gluu_acr' => $gluu_acr,
				'gluu_config' => $gluu_config,
				'gluu_auth_type' => $gluu_other_config['gluu_auth_type'],
				'gluu_custom_logout' => $gluu_other_config['gluu_custom_logout'],
				'gluu_provider' => $gluu_other_config['gluu_provider'],
				'gluu_send_user_check' => $gluu_other_config['gluu_send_user_check'],
				'gluu_oxd_id' => $gluu_other_config['gluu_oxd_id'],
				'gluu_user_role' => $gluu_other_config['gluu_user_role'],
				'gluu_users_can_register' => $gluu_other_config['gluu_users_can_register'],
				'gluu_is_oxd_registered' => !empty($gluu_is_oxd_registered) ? $gluu_is_oxd_registered : false,
				'base_url' => $base_url,
				'message_error' => !empty($_SESSION['message_error']) ? $_SESSION['message_error'] : '',
				'message_success' => !empty($_SESSION['message_success']) ? $_SESSION['message_success'] : '',
				'openid_error' => !empty($_SESSION['openid_error']) ? $_SESSION['openid_error'] : '',
				'gluu_new_roles' => $gluu_other_config['gluu_new_role'],
				'groups' => $group
			];
			unset($_SESSION['message_error']);
			unset($_SESSION['message_success']);
			unset($_SESSION['openid_error']);
			
			return $params;
		}
		
		/**
		 * @NoAdminRequired
		 * @NoCSRFRequired
		 * @return TemplateResponse
		 */
		public function index()
		{
			
			$user = \OC::$server->getUserSession()->getUser();
			if ($user and \OC::$server->getGroupManager()->isAdmin($user->getUID())) {
				$params = $this->getParamsValue();
				
				return new TemplateResponse('gluusso', 'index', $params);  // templates/index.php
			}else{
				return new RedirectResponse($this->urlGenerator->linkToRoute(''));
			}
			
		}
		
		/**
		 * @NoAdminRequired
		 * @NoCSRFRequired
		 * @return TemplateResponse
		 */
		public function editpage()
		{
			$user = \OC::$server->getUserSession()->getUser();
			if ($user and \OC::$server->getGroupManager()->isAdmin($user->getUID())) {
				$params = $this->getParamsValue();
				
				return new TemplateResponse('gluusso', 'editpage', $params);  // templates/index.php
			}else{
				return new RedirectResponse($this->urlGenerator->linkToRoute(''));
			}
		}
		
		/**
		 * @NoAdminRequired
		 * @NoCSRFRequired
		 * @return TemplateResponse
		 */
		public function openidconfigpage()
		{
			$user = \OC::$server->getUserSession()->getUser();
			if ($user and \OC::$server->getGroupManager()->isAdmin($user->getUID())) {
				$params = $this->getParamsValue();
				
				return new TemplateResponse('gluusso', 'openidconfigshow', $params);  // templates/index.php
			}else{
				return new RedirectResponse($this->urlGenerator->linkToRoute(''));
			}
			
		}
		
		/**
		 * Getting base url
		 * @return $base_url
		 */
		public function getBaseUrl()
		{
			$currentPath = $_SERVER['PHP_SELF'];
			$pathInfo = pathinfo($currentPath);
			$hostName = $_SERVER['HTTP_HOST'];
			$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
			if (strpos($pathInfo['dirname'], '\\') !== false) {
				return $protocol . $hostName . "/";
			} else {
				return $protocol . $hostName . $pathInfo['dirname'] . "/";
			}
		}
		
		/**
		 * Checking plugin registration with oxd in OpenID Provider
		 * @return $oxd_id
		 */
		public function gluu_is_oxd_registered()
		{
			$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
			if ($gluu_other_config['gluu_oxd_id']) {
				$oxd_id = $gluu_other_config['gluu_oxd_id'];
				if (!$oxd_id) {
					return 0;
				} else {
					return $oxd_id;
				}
			}
		}
		
		/**
		 * Checking oxd port
		 * @return bool
		 */
		public function gluu_is_port_working()
		{
			$config_option = json_decode($this->mapper->select_query('gluu_config'), true);
			$connection = @fsockopen('127.0.0.1', $config_option['gluu_oxd_port']);
			if (is_resource($connection)) {
				fclose($connection);
				
				return true;
			} else {
				return false;
			}
		}
		
		/**
		 * @NoAdminRequired
		 * @NoCSRFRequired
		 * @return TemplateResponse
		 */
		public function gluupostdata()
		{
			@session_start();
			$base_url = $this->getBaseUrl();
			if (isset($_REQUEST['submit']) and strpos($_REQUEST['submit'], 'delete') !== false and !empty($_REQUEST['submit'])) {
				$this->mapper->delete_query();
				unset($_SESSION['openid_error']);
				$_SESSION['message_success'] = 'Configurations deleted Successfully.';
				
				return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
				exit;
			}
			if (isset($_REQUEST['form_key']) and strpos($_REQUEST['form_key'], 'general_register_page') !== false) {
				
				if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != "on") {
					$_SESSION['message_error'] = 'OpenID Connect requires https. This plugin will not work if your website uses http only.';
					
					return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
				}
				if (!empty($_POST['gluu_user_role'])) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_user_role'] = $_POST['gluu_user_role'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				} else {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_user_role'] = '';
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				}
				if ($_POST['gluu_users_can_register'] == 1) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_users_can_register'] = $_POST['gluu_users_can_register'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					
					if (!empty(array_values(array_filter($_POST['gluu_new_role'])))) {
						
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array_values(array_filter($_POST['gluu_new_role']));
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array();
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				}
				if ($_POST['gluu_users_can_register'] == 2) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_users_can_register'] = $_POST['gluu_users_can_register'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					
					
					if (!empty(array_values(array_filter($_POST['gluu_new_role'])))) {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array_values(array_filter($_POST['gluu_new_role']));
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array();
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				}
				if ($_POST['gluu_users_can_register'] == 3) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_users_can_register'] = $_POST['gluu_users_can_register'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					if (!empty(array_values(array_filter($_POST['gluu_new_role'])))) {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array_values(array_filter($_POST['gluu_new_role']));
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array();
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				}
				if (empty($_POST['gluu_oxd_port'])) {
					$_SESSION['message_error'] = 'All the fields are required. Please enter valid entries.';
					
					return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
				} else if (intval($_POST['gluu_oxd_port']) > 65535 && intval($_POST['gluu_oxd_port']) < 0) {
					$_SESSION['message_error'] = 'Enter your oxd host port (Min. number 1, Max. number 65535)';
					
					return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
				} else if (!empty($_POST['gluu_provider'])) {
					if (filter_var($_POST['gluu_provider'], FILTER_VALIDATE_URL) === false) {
						$_SESSION['message_error'] = 'Please enter valid OpenID Provider URI.';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
				}
				if (!empty($_POST['gluu_custom_logout'])) {
					if (filter_var($_POST['gluu_custom_logout'], FILTER_VALIDATE_URL) === false) {
						$_SESSION['message_error'] = 'Please enter valid Custom URI.';
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_custom_logout'] = $_POST['gluu_custom_logout'];
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				} else {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_custom_logout'] ='';
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				}
				if (isset($_POST['gluu_provider']) and !empty($_POST['gluu_provider'])) {
					
					$gluu_provider = $_POST['gluu_provider'];
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_provider'] =$_POST['gluu_provider'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					//var_dump($gluu_provider);exit;
					$arrContextOptions = array(
						"ssl" => array(
							"verify_peer" => false,
							"verify_peer_name" => false,
						),
					);
					$json = file_get_contents($gluu_provider . '/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
					$obj = json_decode($json);
					if (!empty($obj->userinfo_endpoint)) {
						
						if (empty($obj->registration_endpoint)) {
							$_SESSION['message_success'] = "Please enter your client_id and client_secret.";
							$gluu_config = json_encode(array(
								"gluu_oxd_port" => $_POST['gluu_oxd_port'],
								"admin_email" => '',
								"authorization_redirect_uri" => $base_url . 'loginfromopenid',
								"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
								"config_scopes" => ["openid", "profile", "email"],
								"gluu_client_id" => "",
								"gluu_client_secret" => "",
								"config_acr" => []
							));
							if ($_POST['gluu_users_can_register'] == 2) {
								$config = json_decode($this->mapper->select_query('gluu_config'), true);
								array_push($config['config_scopes'], 'permission');
								$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
							}
							$gluu_config = json_decode($this->mapper->update_query('gluu_config', $gluu_config), true);
							if (isset($_POST['gluu_client_id']) and !empty($_POST['gluu_client_id']) and
								isset($_POST['gluu_client_secret']) and !empty($_POST['gluu_client_secret'])
							) {
								$gluu_config = json_encode(array(
									"gluu_oxd_port" => $_POST['gluu_oxd_port'],
									"admin_email" => '',
									"authorization_redirect_uri" => $base_url . 'loginfromopenid',
									"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
									"config_scopes" => ["openid", "profile", "email"],
									"gluu_client_id" => $_POST['gluu_client_id'],
									"gluu_client_secret" => $_POST['gluu_client_secret'],
									"config_acr" => []
								));
								$gluu_config = json_decode($this->mapper->update_query('gluu_config', $gluu_config), true);
								if ($_POST['gluu_users_can_register'] == 2) {
									$config = json_decode($this->mapper->select_query('gluu_config'), true);
									array_push($config['config_scopes'], 'permission');
									$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
								}
								if (!$this->gluu_is_port_working()) {
									$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								}
								$register_site = new Register_site($this->mapper);
								$register_site->setRequestOpHost($gluu_provider);
								$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
								$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
								$register_site->setRequestContacts([$gluu_config['admin_email']]);
								$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
								$get_scopes = json_encode($obj->scopes_supported);
								if (!empty($obj->acr_values_supported)) {
									$get_acr = json_encode($obj->acr_values_supported);
									$get_acr = $this->mapper->update_query('gluu_acr', $get_acr);
									$register_site->setRequestAcrValues($gluu_config['config_acr']);
								} else {
									$register_site->setRequestAcrValues($gluu_config['config_acr']);
								}
								if (!empty($obj->scopes_supported)) {
									$get_scopes = json_encode($obj->scopes_supported);
									$get_scopes = $this->mapper->update_query('gluu_scopes', $get_scopes);
									$register_site->setRequestScope($obj->scopes_supported);
								} else {
									$register_site->setRequestScope($gluu_config['config_scopes']);
								}
								$register_site->setRequestClientId($gluu_config['gluu_client_id']);
								$register_site->setRequestClientSecret($gluu_config['gluu_client_secret']);
								$status = $register_site->request();
								if ($status['message'] == 'invalid_op_host') {
									$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								}
								if (!$status['status']) {
									$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								}
								if ($status['message'] == 'internal_error') {
									$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								}
								$gluu_oxd_id = $register_site->getResponseOxdId();
								//var_dump($register_site->getResponseObject());exit;
								if ($gluu_oxd_id) {
									$gluu_provider = $register_site->getResponseOpHost();
									$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
									$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
									$gluu_other_config['gluu_provider'] =$gluu_provider;
									$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
									
									$_SESSION['message_success'] = 'Your settings are saved successfully.';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								} else {
									$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								}
							} else {
								$_SESSION['openid_error'] = 'Error505.';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
						}
						else {
							
							$gluu_config = json_encode(array(
								"gluu_oxd_port" => $_POST['gluu_oxd_port'],
								"admin_email" => '',
								"authorization_redirect_uri" => $base_url . 'loginfromopenid',
								"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
								"config_scopes" => ["openid", "profile", "email"],
								"gluu_client_id" => "",
								"gluu_client_secret" => "",
								"config_acr" => []
							));
							$gluu_config = json_decode($this->mapper->update_query('gluu_config', $gluu_config), true);
							if ($_POST['gluu_users_can_register'] == 2) {
								$config = json_decode($this->mapper->select_query('gluu_config'), true);
								array_push($config['config_scopes'], 'permission');
								$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
							}
							if (!$this->gluu_is_port_working()) {
								$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							$register_site = new Register_site($this->mapper);
							$register_site->setRequestOpHost($gluu_provider);
							$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
							$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
							$register_site->setRequestContacts([$gluu_config['admin_email']]);
							$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
							$get_scopes = json_encode($obj->scopes_supported);
							if (!empty($obj->acr_values_supported)) {
								$get_acr = json_encode($obj->acr_values_supported);
								$get_acr = json_decode($this->mapper->update_query('gluu_acr', $get_acr));
								$register_site->setRequestAcrValues($gluu_config['config_acr']);
							} else {
								$register_site->setRequestAcrValues($gluu_config['config_acr']);
							}
							if (!empty($obj->scopes_supported)) {
								$get_scopes = json_encode($obj->scopes_supported);
								$get_scopes = json_decode($this->mapper->update_query('gluu_scopes', $get_scopes));
								$register_site->setRequestScope($obj->scopes_supported);
							} else {
								$register_site->setRequestScope($gluu_config['config_scopes']);
							}
							$status = $register_site->request();
							//var_dump($status);exit;
							if ($status['message'] == 'invalid_op_host') {
								$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							if (!$status['status']) {
								$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							if ($status['message'] == 'internal_error') {
								$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							$gluu_oxd_id = $register_site->getResponseOxdId();
							if ($gluu_oxd_id) {
								$gluu_provider = $register_site->getResponseOpHost();
								$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
								$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
								$gluu_other_config['gluu_provider'] =$gluu_provider;
								$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
								$_SESSION['message_success'] = 'Your settings are saved successfully.';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							} else {
								$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
						}
					} else {
						$_SESSION['message_error'] = 'Please enter correct URI of the OpenID Provider';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					
				} else {
					$gluu_config = json_encode(array(
						"gluu_oxd_port" => $_POST['gluu_oxd_port'],
						"admin_email" => '',
						"authorization_redirect_uri" => $base_url . 'loginfromopenid',
						"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
						"config_scopes" => ["openid", "profile", "email"],
						"gluu_client_id" => "",
						"gluu_client_secret" => "",
						"config_acr" => []
					));
					$gluu_config = json_decode($this->mapper->update_query('gluu_config', $gluu_config), true);
					if ($_POST['gluu_users_can_register'] == 2) {
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					}
					if (!$this->gluu_is_port_working()) {
						$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					$register_site = new Register_site($this->mapper);
					$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
					$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
					$register_site->setRequestContacts([$gluu_config['admin_email']]);
					$register_site->setRequestAcrValues($gluu_config['config_acr']);
					$register_site->setRequestScope($gluu_config['config_scopes']);
					$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
					$status = $register_site->request();
					
					if ($status['message'] == 'invalid_op_host') {
						$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					if (!$status['status']) {
						$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					if ($status['message'] == 'internal_error') {
						$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					$gluu_oxd_id = $register_site->getResponseOxdId();
					if ($gluu_oxd_id) {
						$gluu_provider = $register_site->getResponseOpHost();
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
						$gluu_other_config['gluu_provider'] =$gluu_provider;
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						$arrContextOptions = array(
							"ssl" => array(
								"verify_peer" => false,
								"verify_peer_name" => false,
							),
						);
						$json = file_get_contents($gluu_provider . '/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
						$obj = json_decode($json);
						if (!$this->gluu_is_port_working()) {
							$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						$register_site = new Register_site($this->mapper);
						$register_site->setRequestOpHost($gluu_provider);
						$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
						$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
						$register_site->setRequestContacts([$gluu_config['admin_email']]);
						$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
						
						$get_scopes = json_encode($obj->scopes_supported);
						if (!empty($obj->acr_values_supported)) {
							$get_acr = json_encode($obj->acr_values_supported);
							$get_acr = $this->mapper->update_query('gluu_acr', $get_acr);
							$register_site->setRequestAcrValues($gluu_config['config_acr']);
						} else {
							$register_site->setRequestAcrValues($gluu_config['config_acr']);
						}
						if (!empty($obj->scopes_supported)) {
							$get_scopes = json_encode($obj->scopes_supported);
							$get_scopes = $this->mapper->update_query('gluu_scopes', $get_scopes);
							$register_site->setRequestScope($obj->scopes_supported);
						} else {
							$register_site->setRequestScope($gluu_config['config_scopes']);
						}
						$status = $register_site->request();
						if ($status['message'] == 'invalid_op_host') {
							$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						if (!$status['status']) {
							$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						if ($status['message'] == 'internal_error') {
							$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						$gluu_oxd_id = $register_site->getResponseOxdId();
						if ($gluu_oxd_id) {
							$gluu_provider = $register_site->getResponseOpHost();
							$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
							$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
							$gluu_other_config['gluu_provider'] =$gluu_provider;
							$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
							$_SESSION['message_success'] = 'Your settings are saved successfully.';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						} else {
							$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
					} else {
						$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
				}
			}
			else if (isset($_REQUEST['form_key']) and strpos($_REQUEST['form_key'], 'general_oxd_edit') !== false) {
				
				if (!empty($_POST['gluu_user_role'])) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_user_role'] =$_POST['gluu_user_role'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					
				} else {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_user_role'] ='';
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				}
				if ($_POST['gluu_users_can_register'] == 1) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_users_can_register'] = $_POST['gluu_users_can_register'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					
					if (!empty(array_values(array_filter($_POST['gluu_new_role'])))) {
						
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array_values(array_filter($_POST['gluu_new_role']));
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array();
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				}
				if ($_POST['gluu_users_can_register'] == 2) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_users_can_register'] = $_POST['gluu_users_can_register'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					
					
					if (!empty(array_values(array_filter($_POST['gluu_new_role'])))) {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array_values(array_filter($_POST['gluu_new_role']));
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array();
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				}
				if ($_POST['gluu_users_can_register'] == 3) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_users_can_register'] = $_POST['gluu_users_can_register'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					if (!empty(array_values(array_filter($_POST['gluu_new_role'])))) {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array_values(array_filter($_POST['gluu_new_role']));
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_new_role'] = array();
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				}
				$get_scopes = json_encode(array("openid", "profile", "email"));
				$get_scopes = $this->mapper->update_query('get_scopes', $get_scopes);
				
				$gluu_acr = json_encode(array("none"));
				$gluu_acr = $this->mapper->update_query('gluu_acr', $gluu_acr);
				
				if (!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != "on") {
					$_SESSION['message_error'] = 'OpenID Connect requires https. This plugin will not work if your website uses http only.';
					
					return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
				}
				if (empty($_POST['gluu_oxd_port'])) {
					$_SESSION['message_error'] = 'All the fields are required. Please enter valid entries.';
					
					return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
				} else if (intval($_POST['gluu_oxd_port']) > 65535 && intval($_POST['gluu_oxd_port']) < 0) {
					$_SESSION['message_error'] = 'Enter your oxd host port (Min. number 0, Max. number 65535).';
					
					return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
				}
				if (!empty($_POST['gluu_custom_logout'])) {
					if (filter_var($_POST['gluu_custom_logout'], FILTER_VALIDATE_URL) === false) {
						$_SESSION['message_error'] = 'Please enter valid Custom URI.';
					} else {
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_custom_logout'] = $_POST['gluu_custom_logout'];
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					}
				} else {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_custom_logout'] = '';
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				}
				$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
				$gluu_other_config['gluu_oxd_id'] = '';
				$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				$gluu_config = array(
					"gluu_oxd_port" => $_POST['gluu_oxd_port'],
					"admin_email" => '',
					"authorization_redirect_uri" => $base_url . 'loginfromopenid',
					"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
					"config_scopes" => ["openid", "profile", "email"],
					"gluu_client_id" => "",
					"gluu_client_secret" => "",
					"config_acr" => []
				);
				
				$gluu_config = $this->mapper->update_query('gluu_config', json_encode($gluu_config));
				if ($_POST['gluu_users_can_register'] == 2) {
					$config = json_decode($this->mapper->select_query('gluu_config'), true);
					array_push($config['config_scopes'], 'permission');
					$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
				}
				$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
				$gluu_provider = $gluu_other_config['gluu_provider'];
				if (!empty($gluu_provider)) {
					$arrContextOptions = array(
						"ssl" => array(
							"verify_peer" => false,
							"verify_peer_name" => false,
						),
					);
					$json = file_get_contents($gluu_provider . '/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
					$obj = json_decode($json);
					if (!empty($obj->userinfo_endpoint)) {
						if (empty($obj->registration_endpoint)) {
							if (isset($_POST['gluu_client_id']) and !empty($_POST['gluu_client_id']) and
								isset($_POST['gluu_client_secret']) and !empty($_POST['gluu_client_secret']) and !$obj->registration_endpoint
							) {
								$gluu_config = array(
									"gluu_oxd_port" => $_POST['gluu_oxd_port'],
									"admin_email" => '',
									"gluu_client_id" => $_POST['gluu_client_id'],
									"gluu_client_secret" => $_POST['gluu_client_secret'],
									"authorization_redirect_uri" => $base_url . 'loginfromopenid',
									"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
									"config_scopes" => ["openid", "profile", "email"],
									"config_acr" => []
								);
								$gluu_config1 = $this->mapper->update_query('gluu_config', json_encode($gluu_config));
								if ($_POST['gluu_users_can_register'] == 2) {
									$config = json_decode($this->mapper->select_query('gluu_config'), true);
									array_push($config['config_scopes'], 'permission');
									$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
								}
								if (!$this->gluu_is_port_working()) {
									$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								}
								$register_site = new Register_site($this->mapper);
								$register_site->setRequestOpHost($gluu_provider);
								$register_site->setRequestAcrValues($gluu_config['config_acr']);
								$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
								$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
								$register_site->setRequestContacts(['']);
								$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
								if (!empty($obj->acr_values_supported)) {
									$get_acr = json_encode($obj->acr_values_supported);
									$gluu_config = $this->mapper->update_query('gluu_acr', $gluu_acr);
								}
								if (!empty($obj->scopes_supported)) {
									$get_scopes = json_encode($obj->scopes_supported);
									$gluu_config = $this->mapper->update_query('get_scopes', $get_scopes);
									$register_site->setRequestScope($obj->scopes_supported);
								} else {
									$register_site->setRequestScope($gluu_config['config_scopes']);
								}
								$register_site->setRequestClientId($_POST['gluu_client_id']);
								$register_site->setRequestClientSecret($_POST['gluu_client_secret']);
								$status = $register_site->request();
								if ($status['message'] == 'invalid_op_host') {
									$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
								}
								if (!$status['status']) {
									$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
								}
								if ($status['message'] == 'internal_error') {
									$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
								}
								$gluu_oxd_id = $register_site->getResponseOxdId();
								if ($gluu_oxd_id) {
									$gluu_provider = $register_site->getResponseOpHost();
									$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
									$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
									$gluu_other_config['gluu_provider'] =$gluu_provider;
									$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
									$_SESSION['message_success'] = 'Your settings are saved successfully.';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								} else {
									$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
									
									return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
								}
							} else {
								$_SESSION['openid_error_edit'] = 'Error506';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
							}
						} else {
							$gluu_config = array(
								"gluu_oxd_port" => $_POST['gluu_oxd_port'],
								"admin_email" => '',
								"authorization_redirect_uri" => $base_url . 'loginfromopenid',
								"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
								"config_scopes" => ["openid", "profile", "email"],
								"gluu_client_id" => "",
								"gluu_client_secret" => "",
								"config_acr" => []
							);
							$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($gluu_config)), true);
							if ($_POST['gluu_users_can_register'] == 2) {
								$config = json_decode($this->mapper->select_query('gluu_config'), true);
								array_push($config['config_scopes'], 'permission');
								$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
							}
							if (!$this->gluu_is_port_working()) {
								$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							$register_site = new Register_site($this->mapper);
							$register_site->setRequestOpHost($gluu_provider);
							$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
							$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
							$register_site->setRequestContacts([$gluu_config['admin_email']]);
							$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
							$get_scopes = json_encode($obj->scopes_supported);
							if (!empty($obj->acr_values_supported)) {
								$get_acr = json_encode($obj->acr_values_supported);
								$get_acr = json_decode($this->mapper->update_query('gluu_acr', $get_acr));
								$register_site->setRequestAcrValues($gluu_config['config_acr']);
							} else {
								$register_site->setRequestAcrValues($gluu_config['config_acr']);
							}
							if (!empty($obj->scopes_supported)) {
								$get_scopes = json_encode($obj->scopes_supported);
								$get_scopes = json_decode($this->mapper->update_query('gluu_scopes', $get_scopes));
								$register_site->setRequestScope($obj->scopes_supported);
							} else {
								$register_site->setRequestScope($gluu_config['config_scopes']);
							}
							$status = $register_site->request();
							//var_dump($status);exit;
							if ($status['message'] == 'invalid_op_host') {
								$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							if (!$status['status']) {
								$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							if ($status['message'] == 'internal_error') {
								$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
							$gluu_oxd_id = $register_site->getResponseOxdId();
							if ($gluu_oxd_id) {
								$gluu_provider = $register_site->getResponseOpHost();
								$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
								$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
								$gluu_other_config['gluu_provider'] =$gluu_provider;
								$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
								$_SESSION['message_success'] = 'Your settings are saved successfully.';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							} else {
								$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
								
								return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
							}
						}
					} else {
						$_SESSION['message_error'] = 'Please enter correct URI of the OpenID Provider';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.indexedit'));
					}
				} else {
					$gluu_config = array(
						"gluu_oxd_port" => $_POST['gluu_oxd_port'],
						"admin_email" => '',
						"authorization_redirect_uri" => $base_url . 'loginfromopenid',
						"post_logout_redirect_uri" => $base_url . 'logoutfromopenid',
						"config_scopes" => ["openid", "profile", "email"],
						"gluu_client_id" => "",
						"gluu_client_secret" => "",
						"config_acr" => []
					);
					$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($gluu_config)), true);
					if ($_POST['gluu_users_can_register'] == 2) {
						$config = json_decode($this->mapper->select_query('gluu_config'), true);
						array_push($config['config_scopes'], 'permission');
						$gluu_config = json_decode($this->mapper->update_query('gluu_config', json_encode($config)), true);
					}
					if (!$this->gluu_is_port_working()) {
						$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					$register_site = new Register_site($this->mapper);
					$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
					$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
					$register_site->setRequestContacts([$gluu_config['admin_email']]);
					$register_site->setRequestAcrValues($gluu_config['config_acr']);
					$register_site->setRequestScope($gluu_config['config_scopes']);
					$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
					$status = $register_site->request();
					
					if ($status['message'] == 'invalid_op_host') {
						$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					if (!$status['status']) {
						$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					if ($status['message'] == 'internal_error') {
						$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
					$gluu_oxd_id = $register_site->getResponseOxdId();
					if ($gluu_oxd_id) {
						$gluu_provider = $register_site->getResponseOpHost();
						$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
						$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
						$gluu_other_config['gluu_provider'] =$gluu_provider;
						$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
						$arrContextOptions = array(
							"ssl" => array(
								"verify_peer" => false,
								"verify_peer_name" => false,
							),
						);
						$json = file_get_contents($gluu_provider . '/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
						$obj = json_decode($json);
						if (!$this->gluu_is_port_working()) {
							$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						$register_site = new Register_site($this->mapper);
						$register_site->setRequestOpHost($gluu_provider);
						$register_site->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
						$register_site->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
						$register_site->setRequestContacts([$gluu_config['admin_email']]);
						$register_site->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
						
						$get_scopes = json_encode($obj->scopes_supported);
						if (!empty($obj->acr_values_supported)) {
							$get_acr = json_encode($obj->acr_values_supported);
							$get_acr = $this->mapper->update_query('gluu_acr', $get_acr);
							$register_site->setRequestAcrValues($gluu_config['config_acr']);
						} else {
							$register_site->setRequestAcrValues($gluu_config['config_acr']);
						}
						if (!empty($obj->scopes_supported)) {
							$get_scopes = json_encode($obj->scopes_supported);
							$get_scopes = $this->mapper->update_query('gluu_scopes', $get_scopes);
							$register_site->setRequestScope($obj->scopes_supported);
						} else {
							$register_site->setRequestScope($gluu_config['config_scopes']);
						}
						$status = $register_site->request();
						if ($status['message'] == 'invalid_op_host') {
							$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						if (!$status['status']) {
							$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						if ($status['message'] == 'internal_error') {
							$_SESSION['message_error'] = 'ERROR: ' . $status['error_message'];
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
						$gluu_oxd_id = $register_site->getResponseOxdId();
						if ($gluu_oxd_id) {
							$gluu_provider = $register_site->getResponseOpHost();
							$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
							$gluu_other_config['gluu_oxd_id'] =$gluu_oxd_id;
							$gluu_other_config['gluu_provider'] =$gluu_provider;
							$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
							$_SESSION['message_success'] = 'Your settings are saved successfully.';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						} else {
							$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
							
							return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
						}
					} else {
						$_SESSION['message_error'] = 'ERROR: OpenID Provider host is required if you don\'t provide it in oxd-default-site-config.json';
						
						return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
					}
				}
			}
			else if (isset($_REQUEST['form_key']) and strpos($_REQUEST['form_key'], 'general_oxd_id_reset') !== false and !empty($_REQUEST['resetButton'])) {
				$this->mapper->delete_query();
				unset($_SESSION['openid_error']);
				$_SESSION['message_success'] = 'Configurations deleted Successfully.';
				
				return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
			}
			else if (isset($_REQUEST['form_key']) and strpos($_REQUEST['form_key'], 'openid_config_page') !== false) {
				$params = $_REQUEST;
				$message_success = '';
				$message_error = '';
				
				if ($_POST['send_user_type']) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_auth_type'] = $_POST['send_user_type'];
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				}
				else {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_auth_type'] = 'default';
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
					
				}
				$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
				$gluu_other_config['gluu_send_user_check'] = $_POST['send_user_check'];
				$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				
				
				if (!empty($params['scope']) && isset($params['scope'])) {
					$gluu_config = json_decode($this->mapper->select_query("gluu_config"), true);
					$gluu_config['config_scopes'] = $params['scope'];
					$gluu_config = json_encode($gluu_config);
					$gluu_config = json_decode($this->mapper->update_query('gluu_config', $gluu_config), true);
				}
				if (!empty($params['scope_name']) && isset($params['scope_name'])) {
					$get_scopes = json_decode($this->mapper->select_query("gluu_scopes"), true);
					foreach ($params['scope_name'] as $scope) {
						if ($scope && !in_array($scope, $get_scopes)) {
							array_push($get_scopes, $scope);
						}
					}
					$get_scopes = json_encode($get_scopes);
					$get_scopes = json_decode($this->mapper->update_query('gluu_scopes', $get_scopes), true);
				}
				$gluu_acr = json_decode($this->mapper->select_query('gluu_acr'), true);
				
				if (!empty($params['acr_name']) && isset($params['acr_name'])) {
					$get_acr = json_decode($this->mapper->select_query("gluu_acr"), true);
					foreach ($params['acr_name'] as $scope) {
						if ($scope && !in_array($scope, $get_acr)) {
							array_push($get_acr, $scope);
						}
					}
					$get_acr = json_encode($get_acr);
					$get_acr = json_decode($this->mapper->update_query('gluu_acr', $get_acr), true);
				}
				$gluu_config = json_decode($this->mapper->select_query("gluu_config"), true);
				$gluu_oxd_id = $this->mapper->select_query("gluu_oxd_id");
				if (!$this->gluu_is_port_working()) {
					$_SESSION['message_error'] = 'Can not connect to the oxd server. Please check the oxd-config.json file to make sure you have entered the correct port and the oxd server is operational.';
					
					return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
				}
				$update_site_registration = new Update_site_registration($this->mapper);
				$update_site_registration->setRequestOxdId($gluu_oxd_id);
				$update_site_registration->setRequestAcrValues($gluu_config['acr_values']);
				$update_site_registration->setRequestAuthorizationRedirectUri($gluu_config['authorization_redirect_uri']);
				$update_site_registration->setRequestLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
				$update_site_registration->setRequestContacts([$gluu_config['admin_email']]);
				$update_site_registration->setRequestClientLogoutUri($gluu_config['post_logout_redirect_uri']);
				$update_site_registration->setRequestScope($gluu_config['config_scopes']);
				$status = $update_site_registration->request();
				$new_oxd_id = $update_site_registration->getResponseOxdId();
				if ($new_oxd_id) {
					$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
					$gluu_other_config['gluu_oxd_id'] =$new_oxd_id;
					$this->mapper->update_query('gluu_other_config', json_encode($gluu_other_config));
				}
				
				
				$_SESSION['message_success'] = 'Your OpenID connect configuration has been saved.';
				$_SESSION['message_error'] = $message_error;
				
				return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.openidconfigpage'));
				exit;
			}
		}
		
		/**
		 * @NoAdminRequired
		 * @NoCSRFRequired
		 * @return TemplateResponse
		 */
		public function gluupostdataajax()
		{
			@session_start();
			$base_url = $this->getBaseUrl();
			if( isset( $_POST['form_key_scope_delete'] ) and strpos( $_POST['form_key_scope_delete'], 'form_key_scope_delete' ) !== false ) {
				$get_scopes = json_decode($this->mapper->select_query("gluu_scopes"), true);
				$up_cust_sc =  array();
				foreach($get_scopes as $custom_scop){
					if($custom_scop !=$_POST['delete_scope']){
						array_push($up_cust_sc,$custom_scop);
					}
				}
				$get_scopes = json_encode($up_cust_sc);
				$get_scopes = $this->mapper->update_query('gluu_scopes', $get_scopes);
				
				
				$gluu_config =   json_decode($this->mapper->select_query("gluu_config"),true);
				$up_cust_scope =  array();
				foreach($gluu_config['config_scopes'] as $custom_scop){
					if($custom_scop !=$_POST['delete_scope']){
						array_push($up_cust_scope,$custom_scop);
					}
				}
				$gluu_config['config_scopes'] = $up_cust_scope;
				$gluu_config = json_encode($gluu_config);
				$gluu_config = json_decode($this->mapper->update_query('gluu_config', $gluu_config),true);
				return true;
			}
			else if (isset($_POST['form_key_scope']) and strpos( $_POST['form_key_scope'], 'oxd_openid_config_new_scope' ) !== false) {
				if ($this->gluu_is_oxd_registered()) {
					if (!empty($_POST['new_value_scope']) && isset($_POST['new_value_scope'])) {
						
						$get_scopes =   json_decode($this->mapper->select_query("gluu_scopes"),true);
						if($_POST['new_value_scope'] && !in_array($_POST['new_value_scope'],$get_scopes)){
							array_push($get_scopes, $_POST['new_value_scope']);
						}
						$get_scopes = json_encode($get_scopes);
						$this->mapper->update_query('gluu_scopes', $get_scopes);
						return true;
					}
					
				}
			}
			else if( isset( $_REQUEST['form_key'] ) and strpos( $_REQUEST['form_key'], 'openid_config_page' ) !== false ) {
				$params = $_REQUEST;
				if(!empty($params['scope']) && isset($params['scope'])){
					$gluu_config =   json_decode($this->mapper->select_query("gluu_config"),true);
					$gluu_config['config_scopes'] = $params['scope'];
					$gluu_config = json_encode($gluu_config);
					$gluu_config = json_decode($this->mapper->update_query('gluu_config', $gluu_config),true);
					return true;
				}
			}
		}
		
		/**
		 * @NoAdminRequired
		 * @NoCSRFRequired
		 * @return TemplateResponse
		 */
		public function gluupostdataget()
		{
			@session_start();
			$base_url = $this->getBaseUrl();
			if (isset($_REQUEST['submit']) and strpos($_REQUEST['submit'], 'delete') !== false and !empty($_REQUEST['submit'])) {
				$this->mapper->delete_query();
				unset($_SESSION['openid_error']);
				$_SESSION['message_success'] = 'Configurations deleted Successfully.';
				
				return new RedirectResponse($this->urlGenerator->linkToRoute('gluusso.page.index'));
				exit;
			}
		}
		
		/**
		 * @PublicPage
		 * @UseSession
		 * @OnlyUnauthenticatedUsers
		 * @return Http\RedirectResponse
		 * @throws \Exception
		 */
		public function loginpage($user, $redirect_url, $remember_login)
		{
			
			$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
			$gluu_oxd_id = $gluu_other_config['gluu_oxd_id'];
			
			$parameters = array();
			$parameters['login_url'] = $this->login_url();
			$parameters['gluu_oxd_id'] = !empty($gluu_oxd_id) ? $gluu_oxd_id : '';
			$parameters['gluu_is_port_working'] = $this->gluu_is_port_working();
			$script = !empty($_SESSION['error_script']) ? $_SESSION['error_script'] : '';
			unset($_SESSION['error_script']);
			if(!empty($script)){
				$parameters['error_script'] = $script;
			}
			$loginMessages = $this->session->get('loginMessages');
			$errors = [];
			$messages = [];
			if (is_array($loginMessages)) {
				list($errors, $messages) = $loginMessages;
			}
			$this->session->remove('loginMessages');
			foreach ($errors as $value) {
				$parameters[$value] = true;
			}
			
			$parameters['messages'] = $messages;
			if (!is_null($user) && $user !== '') {
				$parameters['loginName'] = $user;
				$parameters['user_autofocus'] = false;
			} else {
				$parameters['loginName'] = '';
				$parameters['user_autofocus'] = true;
			}
			if (!empty($redirect_url)) {
				$parameters['redirect_url'] = $redirect_url;
			}
			
			$parameters['canResetPassword'] = true;
			$parameters['resetPasswordLink'] = $this->config->getSystemValue('lost_password_link', '');
			if (!$parameters['resetPasswordLink']) {
				if (!is_null($user) && $user !== '') {
					$userObj = $this->userManager->get($user);
					if ($userObj instanceof IUser) {
						$parameters['canResetPassword'] = $userObj->canChangePassword();
					}
				}
			}
			
			$parameters['alt_login'] = OC_App::getAlternativeLogIns();
			$parameters['rememberLoginAllowed'] = OC_Util::rememberLoginAllowed();
			$parameters['rememberLoginState'] = !empty($remember_login) ? $remember_login : 0;
			
			if (!is_null($user) && $user !== '') {
				$parameters['loginName'] = $user;
				$parameters['user_autofocus'] = false;
			} else {
				$parameters['loginName'] = '';
				$parameters['user_autofocus'] = true;
			}
			
			return new TemplateResponse('gluusso', 'login', $parameters, 'guest');  // templates/show-temp.php
		}
		
		/**
		 * Getting OpenID Provider login url
		 * @return string $result
		 */
		public function login_url($prompt = '')
		{
			$gluu_config = json_decode($this->mapper->select_query('gluu_config'), true);
			
			$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
			$gluu_oxd_id = $gluu_other_config['gluu_oxd_id'];
			$gluu_auth_type = $gluu_other_config['gluu_auth_type'];
			
			$get_authorization_url = new Get_authorization_url($this->mapper);
			$get_authorization_url->setRequestOxdId($gluu_oxd_id);
			
			
			$get_authorization_url->setRequestScope($gluu_config['config_scopes']);
			if ($gluu_auth_type != "default") {
				$get_authorization_url->setRequestAcrValues([$gluu_auth_type]);
			} else {
				$get_authorization_url->setRequestAcrValues(null);
			}
			if ($prompt) {
				$get_authorization_url->setRequestPrompt($prompt);
			}
			
			$get_authorization_url->request();
			
			return $get_authorization_url->getResponseAuthorizationUrl();
		}
		
		/**
		 * Getting OpenID Provider logout url
		 * @return string $result
		 */
		public function gluu_sso_doing_logout($user_oxd_id_token, $session_states, $state)
		{
			@session_start();
			
			$gluu_config = json_decode($this->mapper->select_query('gluu_config'), true);
			$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
			$gluu_provider = $gluu_other_config['gluu_provider'];
			$gluu_oxd_id = $gluu_other_config['gluu_oxd_id'];
			$arrContextOptions=array(
				"ssl"=>array(
					"verify_peer"=>false,
					"verify_peer_name"=>false,
				),
			);
			$json = file_get_contents($gluu_provider.'/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
			$obj = json_decode($json);
			
			if (!empty($obj->end_session_endpoint ) or $gluu_provider == 'https://accounts.google.com') {
				if (!empty($_SESSION['user_oxd_id_token'])) {
					if ($gluu_oxd_id && $_SESSION['user_oxd_id_token'] && $_SESSION['session_in_op']) {
						$logout = new Logout($this->mapper);
						$logout->setRequestOxdId($gluu_oxd_id);
						$logout->setRequestIdToken($_SESSION['user_oxd_id_token']);
						$logout->setRequestPostLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
						$logout->setRequestSessionState($_SESSION['session_state']);
						$logout->setRequestState($_SESSION['state']);
						$logout->request();
						unset($_SESSION['user_oxd_access_token']);
						unset($_SESSION['user_oxd_id_token']);
						unset($_SESSION['session_state']);
						unset($_SESSION['state']);
						unset($_SESSION['session_in_op']);
						return $logout->getResponseObject()->data->uri;
					}
				}
			}
			
			return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
		}
		
		/**
		 * @PublicPage
		 * @UseSession
		 * @NoCSRFRequired
		 * @param string $user
		 * @param string $password
		 * @param string $redirect_url
		 * @param boolean $remember_login
		 * @param string $timezone
		 * @param string $timezone_offset
		 * @return RedirectResponse
		 */
		public function tryLogin($user, $password, $redirect_url, $remember_login = false, $timezone = '', $timezone_offset = '')
		{
			
			$currentDelay = $this->throttler->getDelay($this->request->getRemoteAddress());
			$this->throttler->sleepDelay($this->request->getRemoteAddress());
			
			// If the user is already logged in and the CSRF check does not pass then
			// simply redirect the user to the correct page as required. This is the
			// case when an user has already logged-in, in another tab.
			if (!$this->request->passesCSRFCheck()) {
				return $this->generateRedirect($redirect_url);
			}
			
			$originalUser = $user;
			// TODO: Add all the insane error handling
			/* @var $loginResult IUser */
			$loginResult = $this->userManager->checkPassword($user, $password);
			if ($loginResult === false) {
				$users = $this->userManager->getByEmail($user);
				// we only allow login by email if unique
				if (count($users) === 1) {
					$user = $users[0]->getUID();
					$loginResult = $this->userManager->checkPassword($user, $password);
				}
			}
			if ($loginResult === false) {
				$this->throttler->registerAttempt('login', $this->request->getRemoteAddress(), ['user' => $originalUser]);
				if ($currentDelay === 0) {
					$this->throttler->sleepDelay($this->request->getRemoteAddress());
				}
				$this->session->set('loginMessages', [
					['invalidpassword'], []
				]);
				// Read current user and append if possible - we need to return the unmodified user otherwise we will leak the login name
				$args = !is_null($user) ? ['user' => $originalUser] : [];
				
				return new RedirectResponse($this->urlGenerator->linkToRoute('core.login.showLoginForm', $args));
			}
			// TODO: remove password checks from above and let the user session handle failures
			// requires https://github.com/owncloud/core/pull/24616
			$this->userSession->login($user, $password);
			
			$this->userSession->createSessionToken($this->request, $loginResult->getUID(), $user, $password, (int)$remember_login);
			
			// User has successfully logged in, now remove the password reset link, when it is available
			$this->config->deleteUserValue($loginResult->getUID(), 'core', 'lostpassword');
			
			$this->session->set('last-password-confirm', $loginResult->getLastLogin());
			
			if ($timezone_offset !== '') {
				$this->config->setUserValue($loginResult->getUID(), 'core', 'timezone', $timezone);
				$this->session->set('timezone', $timezone_offset);
			}
			
			if ($this->twoFactorManager->isTwoFactorAuthenticated($loginResult)) {
				$this->twoFactorManager->prepareTwoFactorLogin($loginResult, $remember_login);
				
				$providers = $this->twoFactorManager->getProviders($loginResult);
				if (count($providers) === 1) {
					// Single provider, hence we can redirect to that provider's challenge page directly
					/* @var $provider IProvider */
					$provider = array_pop($providers);
					$url = 'core.TwoFactorChallenge.showChallenge';
					$urlParams = [
						'challengeProviderId' => $provider->getId(),
					];
				} else {
					$url = 'core.TwoFactorChallenge.selectChallenge';
					$urlParams = [];
				}
				
				if (!is_null($redirect_url)) {
					$urlParams['redirect_url'] = $redirect_url;
				}
				
				return new RedirectResponse($this->urlGenerator->linkToRoute($url, $urlParams));
			}
			
			if ($remember_login) {
				$this->userSession->createRememberMeToken($loginResult);
			}
			
			return $this->generateRedirect($redirect_url);
		}
		
		/**
		 * @PublicPage
		 * @UseSession
		 * @NoCSRFRequired
		 * @param string $user
		 * @param string $password
		 * @param string $redirect_url
		 * @param boolean $remember_login
		 * @param string $timezone
		 * @param string $timezone_offset
		 * @return RedirectResponse
		 */
		public function loginfromopenid()
		{
			@session_start();
			$base_url = $this->getBaseUrl();
			if (isset($_REQUEST['error']) and strpos($_REQUEST['error'], 'session_selection_required') !== false) {
				
				header("Location: " . $this->login_url('login'));
				exit;
			}
			$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
			$gluu_oxd_id = $gluu_other_config['gluu_oxd_id'];
			
			$get_tokens_by_code = new Get_tokens_by_code($this->mapper);
			$get_tokens_by_code->setRequestOxdId($gluu_oxd_id);
			$get_tokens_by_code->setRequestCode($_REQUEST['code']);
			$get_tokens_by_code->setRequestState($_REQUEST['state']);
			$get_tokens_by_code->request();
			
			$get_tokens_by_code_array = array();
			
			if (!empty($get_tokens_by_code->getResponseObject()->data->id_token_claims)) {
				$get_tokens_by_code_array = $get_tokens_by_code->getResponseObject()->data->id_token_claims;
			} else {
				$_SESSION['error_script'] = "<a class='warning'>Missing claims : Please talk to your organizational system administrator or try again.</a><br/>";
				return new RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
				exit;
			}
			
			$get_user_info = new Get_user_info($this->mapper);
			$get_user_info->setRequestOxdId($gluu_oxd_id);
			$get_user_info->setRequestAccessToken($get_tokens_by_code->getResponseAccessToken());
			$get_user_info->request();
			$get_user_info_array = $get_user_info->getResponseObject()->data->claims;
			$_SESSION['session_in_op'] = $get_tokens_by_code->getResponseIdTokenClaims()->exp[0];
			$_SESSION['user_oxd_id_token'] = $get_tokens_by_code->getResponseIdToken();
			$_SESSION['user_oxd_access_token'] = $get_tokens_by_code->getResponseAccessToken();
			$_SESSION['session_state'] = $_REQUEST['session_state'];
			$_SESSION['state'] = $_REQUEST['state'];
			$get_user_info_array = $get_user_info->getResponseObject()->data->claims;
			/*echo '<pre>';
			var_dump($get_user_info_array);exit;*/
			$reg_first_name = '';
			$reg_user_name = '';
			$reg_last_name = '';
			$reg_email = '';
			$reg_avatar = '';
			$reg_display_name = '';
			$reg_nikname = '';
			$reg_website = '';
			$reg_middle_name = '';
			$reg_country = '';
			$reg_city = '';
			$reg_region = '';
			$reg_gender = '';
			$reg_postal_code = '';
			$reg_fax = '';
			$reg_home_phone_number = '';
			$reg_phone_mobile_number = '';
			$reg_street_address = '';
			$reg_street_address_2 = '';
			$reg_birthdate = '';
			$reg_user_permission = '';
			if (!empty($get_user_info_array->email[0])) {
				$reg_email = $get_user_info_array->email[0];
			} elseif (!empty($get_tokens_by_code_array->email[0])) {
				$reg_email = $get_tokens_by_code_array->email[0];
			} else {
				$_SESSION['error_script'] = "<a class='warning'>Missing claim : (email). Please talk to your organizational system administrator.</a><br/>";
				return new RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
				exit;
			}
			if (!empty($get_user_info_array->website[0])) {
				$reg_website = $get_user_info_array->website[0];
			} elseif (!empty($get_tokens_by_code_array->website[0])) {
				$reg_website = $get_tokens_by_code_array->website[0];
			}
			if (!empty($get_user_info_array->nickname[0])) {
				$reg_nikname = $get_user_info_array->nickname[0];
			} elseif (!empty($get_tokens_by_code_array->nickname[0])) {
				$reg_nikname = $get_tokens_by_code_array->nickname[0];
			}
			
			if (!empty($get_user_info_array->given_name[0])) {
				$reg_first_name = $get_user_info_array->given_name[0];
			} elseif (!empty($get_tokens_by_code_array->given_name[0])) {
				$reg_first_name = $get_tokens_by_code_array->given_name[0];
			}
			if (!empty($get_user_info_array->family_name[0])) {
				$reg_last_name = $get_user_info_array->family_name[0];
			} elseif (!empty($get_tokens_by_code_array->family_name[0])) {
				$reg_last_name = $get_tokens_by_code_array->family_name[0];
			}
			if (!empty($get_user_info_array->middle_name[0])) {
				$reg_middle_name = $get_user_info_array->middle_name[0];
			} elseif (!empty($get_tokens_by_code_array->middle_name[0])) {
				$reg_middle_name = $get_tokens_by_code_array->middle_name[0];
			}
			if (!empty($get_user_info_array->name[0])) {
				$reg_display_name = $get_user_info_array->name[0];
			} elseif (!empty($get_tokens_by_code_array->name[0])) {
				$reg_display_name = $get_tokens_by_code_array->name[0];
			}else{
				$reg_display_name = $reg_first_name.' '.$reg_last_name.' '.$reg_middle_name;
			}
			if (!empty($get_user_info_array->country[0])) {
				$reg_country = $get_user_info_array->country[0];
			} elseif (!empty($get_tokens_by_code_array->country[0])) {
				$reg_country = $get_tokens_by_code_array->country[0];
			}
			if (!empty($get_user_info_array->gender[0])) {
				if ($get_user_info_array->gender[0] == 'male') {
					$reg_gender = '1';
				} else {
					$reg_gender = '2';
				}
				
			} elseif (!empty($get_tokens_by_code_array->gender[0])) {
				if ($get_tokens_by_code_array->gender[0] == 'male') {
					$reg_gender = '1';
				} else {
					$reg_gender = '2';
				}
			}
			if (!empty($get_user_info_array->locality[0])) {
				$reg_city = $get_user_info_array->locality[0];
			} elseif (!empty($get_tokens_by_code_array->locality[0])) {
				$reg_city = $get_tokens_by_code_array->locality[0];
			}
			if (!empty($get_user_info_array->postal_code[0])) {
				$reg_postal_code = $get_user_info_array->postal_code[0];
			} elseif (!empty($get_tokens_by_code_array->postal_code[0])) {
				$reg_postal_code = $get_tokens_by_code_array->postal_code[0];
			}
			if (!empty($get_user_info_array->phone_number[0])) {
				$reg_home_phone_number = $get_user_info_array->phone_number[0];
			} elseif (!empty($get_tokens_by_code_array->phone_number[0])) {
				$reg_home_phone_number = $get_tokens_by_code_array->phone_number[0];
			}
			if (!empty($get_user_info_array->work_phone[0])) {
				$reg_phone_mobile_number = $get_user_info_array->work_phone[0];
			} elseif (!empty($get_tokens_by_code_array->work_phone[0])) {
				$reg_phone_mobile_number = $get_tokens_by_code_array->work_phone[0];
			}
			if (!empty($get_user_info_array->picture[0])) {
				$reg_avatar = $get_user_info_array->picture[0];
			} elseif (!empty($get_tokens_by_code_array->picture[0])) {
				$reg_avatar = $get_tokens_by_code_array->picture[0];
			}
			if (!empty($get_user_info_array->street_address[0])) {
				$reg_street_address = $get_user_info_array->street_address[0];
			} elseif (!empty($get_tokens_by_code_array->street_address[0])) {
				$reg_street_address = $get_tokens_by_code_array->street_address[0];
			}
			if (!empty($get_user_info_array->street_address[1])) {
				$reg_street_address_2 = $get_user_info_array->street_address[1];
			} elseif (!empty($get_tokens_by_code_array->street_address[1])) {
				$reg_street_address_2 = $get_tokens_by_code_array->street_address[1];
			}
			if (!empty($get_user_info_array->birthdate[0])) {
				$reg_birthdate = $get_user_info_array->birthdate[0];
			} elseif (!empty($get_tokens_by_code_array->birthdate[0])) {
				$reg_birthdate = $get_tokens_by_code_array->birthdate[0];
			}
			if (!empty($get_user_info_array->region[0])) {
				$reg_region = $get_user_info_array->region[0];
			} elseif (!empty($get_tokens_by_code_array->region[0])) {
				$reg_region = $get_tokens_by_code_array->region[0];
			}
			
			$username = '';
			if (!empty($get_user_info_array->user_name[0])) {
				$username = $get_user_info_array->user_name[0];
			} else {
				$email_split = explode("@", $reg_email);
				$username = $email_split[0];
			}
			if (!empty($get_user_info_array->permission[0])) {
				$world = str_replace("[", "", $get_user_info_array->permission[0]);
				$reg_user_permission = str_replace("]", "", $world);
			} elseif (!empty($get_tokens_by_code_array->permission[0])) {
				$world = str_replace("[", "", $get_user_info_array->permission[0]);
				$reg_user_permission = str_replace("]", "", $world);
			}
			
			if($this->userManager->userExists($username)){
				$loginResult = $this->userManager->get($username);
				
				
				$userSession = \OC::$server->getUserSession();
				$request = \OC::$server->getRequest();
				
				$loginSuccess = $userSession->tryTokenLogin($request);
				if (!$loginSuccess) {
					$loginSuccess = $userSession->tryBasicAuthLogin($request, \OC::$server->getBruteForceThrottler());
				}
				
				$this->userSession->createSessionToken($this->request, $loginResult->getUID(), $username, null, (int)0);
				
				// User has successfully logged in, now remove the password reset link, when it is available
				$this->config->deleteUserValue($loginResult->getUID(), 'core', 'lostpassword');
				
				$this->session->set('last-password-confirm', $loginResult->getLastLogin());
				$this->updateUserSettings($loginResult->getUID(),$reg_avatar, $reg_display_name, $reg_display_name,$reg_home_phone_number,$reg_home_phone_number,$reg_email,$reg_email,$reg_website,$reg_website,$reg_street_address,$reg_street_address,'','');
				
			}else{
				
				$bool = true;
				$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
				$gluu_new_roles = $gluu_other_config['gluu_user_role'];
				$gluu_users_can_register = $gluu_other_config['gluu_users_can_register'];
				
				
				if($gluu_users_can_register == 2 and !empty($gluu_new_roles)){
					if (!in_array($reg_user_permission, $gluu_new_roles)) {
						$bool = false;
					}else{
						$bool = True;
					}
				}
				if(!$bool or $gluu_users_can_register == 3){
					$_SESSION['error_script'] = "<a class='warning'>You are not authorized for an account on this application. If you think this is an error, please contact your OpenID Connect Provider (OP) admin.</a><br/>
					<a style='border-radius: 3px !important; padding: 10px 10px !important;font-weight: bold !important;font-size: 15px !important; margin: 5px !important; width: 269px !important;' class='login primary' href='" . $this->gluu_sso_doing_logout($get_tokens_by_code->getResponseIdToken(), $_REQUEST['session_state'], $_REQUEST['state']) . "'>Logout from OpenID Provider</a>";
					return new RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
					exit;
				}
				$password = $this->randomPassword();
				$this->create($username, $password, array($gluu_new_roles), $reg_email);
				
				$loginResult = $this->userManager->get($username);
				$this->setUser($loginResult);
				$this->setLoginName($loginResult->getUID());
				// setUserSettings( $address, $addressScope, $twitter, $twitterScope
				//$avatarScope,$displayname, $displaynameScope,$phone, $phoneScope,$email, $emailScope,$website,	$websiteScope,
				$loginResult = $this->userManager->get($username);
				$this->userSession->login($username, $password);
				
				$this->userSession->createSessionToken($this->request, $loginResult->getUID(), $username, $password, (int)0);
				
				// User has successfully logged in, now remove the password reset link, when it is available
				$this->config->deleteUserValue($loginResult->getUID(), 'core', 'lostpassword');
				
				$this->session->set('last-password-confirm', $loginResult->getLastLogin());
				$this->setUserSettings($reg_avatar, $reg_display_name, $reg_display_name,$reg_phone_mobile_number,$reg_phone_mobile_number,$reg_email,$reg_email,$reg_website,$reg_website,$reg_street_address,$reg_street_address,'','');
				
			}
			return new RedirectResponse(\OC::$server->getURLGenerator()->getAbsoluteURL('/'));
		}
		
		/**
		 * set the login name
		 *
		 * @param string|null $loginName for the logged in user
		 */
		public function setLoginName($loginName) {
			if (is_null($loginName)) {
				$this->session->remove('loginname');
			} else {
				$this->session->set('loginname', $loginName);
			}
		}
		
		/**
		 * set user
		 *
		 * @param string|null $loginName for the logged in user
		 */
		public function setUser($user) {
			if (is_null($user)) {
				$this->session->remove('user_id');
			} else {
				$this->session->set('user_id', $user->getUID());
			}
			$this->activeUser = $user;
		}
		
		/**
		 * @NoAdminRequired
		 * @UseSession
		 *
		 * @return RedirectResponse
		 */
		public function logoutfromopenid()
		{
			$gluu_config = json_decode($this->mapper->select_query('gluu_config'), true);
			$gluu_other_config = json_decode($this->mapper->select_query('gluu_other_config'), true);
			$gluu_provider = $gluu_other_config['gluu_provider'];
			$gluu_oxd_id = $gluu_other_config['gluu_oxd_id'];
			if (isset($_SESSION['session_in_op'])) {
				if (time() < (int)$_SESSION['session_in_op']) {
					$arrContextOptions = array(
						"ssl" => array(
							"verify_peer" => false,
							"verify_peer_name" => false,
						),
					);
					$json = file_get_contents($gluu_provider . '/.well-known/openid-configuration', false, stream_context_create($arrContextOptions));
					$obj = json_decode($json);
					
					if (!empty($obj->end_session_endpoint) or $gluu_provider == 'https://accounts.google.com') {
						if (!empty($_SESSION['user_oxd_id_token'])) {
							if ($gluu_oxd_id && $_SESSION['user_oxd_id_token'] && $_SESSION['session_in_op']) {
								$logout = new Logout($this->mapper);
								$logout->setRequestOxdId($gluu_oxd_id);
								$logout->setRequestIdToken($_SESSION['user_oxd_id_token']);
								$logout->setRequestPostLogoutRedirectUri($gluu_config['post_logout_redirect_uri']);
								$logout->setRequestSessionState($_SESSION['session_state']);
								$logout->setRequestState($_SESSION['state']);
								$logout->request();
								unset($_SESSION['user_oxd_access_token']);
								unset($_SESSION['user_oxd_id_token']);
								unset($_SESSION['session_state']);
								unset($_SESSION['state']);
								unset($_SESSION['session_in_op']);
								$loginToken = $this->request->getCookie('oc_token');
								if (!is_null($loginToken)) {
									$this->config->deleteUserValue($this->userSession->getUser()->getUID(), 'login_token', $loginToken);
								}
								$this->userSession->logout();
								header("Location: " . $logout->getResponseObject()->data->uri);
								exit;
							}
						}
					} else {
						unset($_SESSION['user_oxd_access_token']);
						unset($_SESSION['user_oxd_id_token']);
						unset($_SESSION['session_state']);
						unset($_SESSION['state']);
						unset($_SESSION['session_in_op']);
						$loginToken = $this->request->getCookie('oc_token');
						if (!is_null($loginToken)) {
							$this->config->deleteUserValue($this->userSession->getUser()->getUID(), 'login_token', $loginToken);
						}
						$this->userSession->logout();
					}
				}
			}
			$loginToken = $this->request->getCookie('oc_token');
			if (!is_null($loginToken)) {
				$this->config->deleteUserValue($this->userSession->getUser()->getUID(), 'login_token', $loginToken);
			}
			$this->userSession->logout();
			
			return new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('core.login.showLoginForm'));
		}
		
		/**
		 * @param string $redirectUrl
		 * @return RedirectResponse
		 */
		private function generateRedirect($redirectUrl)
		{
			if (!is_null($redirectUrl) && $this->userSession->isLoggedIn()) {
				$location = $this->urlGenerator->getAbsoluteURL(urldecode($redirectUrl));
				if (strpos($location, '@') === false) {
					return new RedirectResponse($location);
				}
			}
			
			return new RedirectResponse(OC_Util::getDefaultPageUrl());
		}
		
		/**
		 * @NoAdminRequired
		 * @PasswordConfirmationRequired
		 * @param string $username
		 * @param string $password
		 * @param array $groups
		 * @param string $email
		 * @return DataResponse
		 */
		public function create($username, $password, array $groups = array(), $email = '')
		{
			
			try {
				$user = $this->userManager->createUser($username, $password);
			} catch (\Exception $exception) {
				$message = $exception->getMessage();
				if (!$message) {
					$message = $this->l10n->t('Unable to create user.');
				}
				
				return new DataResponse(
					array(
						'message' => (string)$message,
					),
					Http::STATUS_FORBIDDEN
				);
			}
			
			if ($user instanceof User) {
				if ($groups !== null) {
					foreach ($groups as $groupName) {
						$group = $this->groupManager->get($groupName);
						
						if (empty($group)) {
							$group = $this->groupManager->createGroup($groupName);
						}
						$group->addUser($user);
					}
				}
				
				
				return true;
			}
		}
		
		/**
		 * @NoAdminRequired
		 * @NoSubadminRequired
		 * @PasswordConfirmationRequired
		 *
		 * @param string $avatarScope
		 * @param string $displayname
		 * @param string $displaynameScope
		 * @param string $phone
		 * @param string $phoneScope
		 * @param string $email
		 * @param string $emailScope
		 * @param string $website
		 * @param string $websiteScope
		 * @param string $address
		 * @param string $addressScope
		 * @param string $twitter
		 * @param string $twitterScope
		 * @return DataResponse
		 */
		public function setUserSettings($avatarScope,
		                                $displayname,
		                                $displaynameScope,
		                                $phone,
		                                $phoneScope,
		                                $email,
		                                $emailScope,
		                                $website,
		                                $websiteScope,
		                                $address,
		                                $addressScope,
		                                $twitter,
		                                $twitterScope
		) {
			
			$data = [
				AccountManager::PROPERTY_AVATAR =>  ['scope' => $avatarScope],
				AccountManager::PROPERTY_DISPLAYNAME => ['value' => $displayname, 'scope' => $displaynameScope],
				AccountManager::PROPERTY_EMAIL=> ['value' => $email, 'scope' => $emailScope],
				AccountManager::PROPERTY_WEBSITE => ['value' => $website, 'scope' => $websiteScope],
				AccountManager::PROPERTY_ADDRESS => ['value' => $address, 'scope' => $addressScope],
				AccountManager::PROPERTY_PHONE => ['value' => $phone, 'scope' => $phoneScope],
				AccountManager::PROPERTY_TWITTER => ['value' => $twitter, 'scope' => $twitterScope]
			];
			
			$user = $this->userSession->getUser();
			
			try {
				$this->saveUserSettings($user, $data);
				
			} catch (ForbiddenException $e) {
				
			}
		}
		
		/**
		 * update account manager with new user data
		 *
		 * @param IUser $user
		 * @param array $data
		 * @throws ForbiddenException
		 */
		public function saveUserSettings($user, $data) {
			
			// keep the user back-end up-to-date with the latest display name and email
			// address
			$oldDisplayName = is_null($user->getDisplayName()) ? '' : $user->getDisplayName();
			if (isset($data[AccountManager::PROPERTY_DISPLAYNAME]['value'])
				&& $oldDisplayName !== $data[AccountManager::PROPERTY_DISPLAYNAME]['value']
			) {
				$result = $user->setDisplayName($data[AccountManager::PROPERTY_DISPLAYNAME]['value']);
				if ($result === false) {
					throw new ForbiddenException($this->l10n->t('Unable to change full name'));
				}
			}
			
			$oldEmailAddress = $user->getEMailAddress();
			$oldEmailAddress = is_null($oldEmailAddress) ? '' : $oldEmailAddress;
			if (isset($data[AccountManager::PROPERTY_EMAIL]['value'])
				&& $oldEmailAddress !== $data[AccountManager::PROPERTY_EMAIL]['value']
			) {
				// this is the only permission a backend provides and is also used
				// for the permission of setting a email address
				if (!$user->canChangeDisplayName()) {
					throw new ForbiddenException($this->l10n->t('Unable to change email address'));
				}
				$user->setEMailAddress($data[AccountManager::PROPERTY_EMAIL]['value']);
			}
			
			$this->accountManager->updateUser($user, $data);
			$this->mapper->updateUser($user, $data);
		}
		
		/**
		 * @NoAdminRequired
		 * @NoSubadminRequired
		 * @PasswordConfirmationRequired
		 *
		 * @param string $avatarScope
		 * @param string $displayname
		 * @param string $displaynameScope
		 * @param string $phone
		 * @param string $phoneScope
		 * @param string $email
		 * @param string $emailScope
		 * @param string $website
		 * @param string $websiteScope
		 * @param string $address
		 * @param string $addressScope
		 * @param string $twitter
		 * @param string $twitterScope
		 * @return DataResponse
		 */
		public function updateUserSettings($uid,$avatarScope,
		                                   $displayname,
		                                   $displaynameScope,
		                                   $phone,
		                                   $phoneScope,
		                                   $email,
		                                   $emailScope,
		                                   $website,
		                                   $websiteScope,
		                                   $address,
		                                   $addressScope,
		                                   $twitter,
		                                   $twitterScope
		) {
			
			$data = [
				AccountManager::PROPERTY_AVATAR =>  ['scope' => $avatarScope],
				AccountManager::PROPERTY_DISPLAYNAME => ['value' => $displayname, 'scope' => $displaynameScope],
				AccountManager::PROPERTY_EMAIL=> ['value' => $email, 'scope' => $emailScope],
				AccountManager::PROPERTY_WEBSITE => ['value' => $website, 'scope' => $websiteScope],
				AccountManager::PROPERTY_ADDRESS => ['value' => $address, 'scope' => $addressScope],
				AccountManager::PROPERTY_PHONE => ['value' => $phone, 'scope' => $phoneScope],
				AccountManager::PROPERTY_TWITTER => ['value' => $twitter, 'scope' => $twitterScope]
			];
			
			try {
				$this->updateUserData($uid, $data);
				
			} catch (ForbiddenException $e) {
				
			}
		}
		
		/**
		 * update account manager with new user data
		 *
		 * @param IUser $user
		 * @param array $data
		 * @throws ForbiddenException
		 */
		public function updateUserData($user, $data) {
			$this->mapper->updateUser($user, $data);
		}
		
		/**
		 * Generating rundom password
		 * @return string $result
		 */
		public function randomPassword() {
			$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
			$pass = array(); //remember to declare $pass as an array
			$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
			for ($i = 0; $i < 8; $i++) {
				$n = rand(0, $alphaLength);
				$pass[] = $alphabet[$n];
			}
			return implode($pass); //turn the array into a string
		}
		
	}