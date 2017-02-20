<?php
	/**
	 * Gluu-oxd-library
	 *
	 * An open source application library for PHP
	 *
	 *
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

	/**
	 * Client authorization class
	 *
	 * Class is connecting to oxd-server via socket, and getting authorization url from gluu-server.
	 *
	 * @package		  Gluu-oxd-library
	 * @subpackage	Libraries
	 * @category	  Relying Party (RP) and User Managed Access (UMA)
	 * @see	        Client_OXD_RP
	 */

namespace OCA\GluuSso\oxdrplib;
use OCA\GluuSso\oxdrplib\Client_OXD_RP;
use OCA\GluuSso\Provider\GluuManager;

class Get_authorization_url extends Client_OXD_RP
{
    /**
     * @var string $request_oxd_id                            This parameter you must get after registration site in gluu-server
     */
    private $request_oxd_id = null;
    /**
     * @var string $request_oxd_id                            This parameter you must get after registration site in gluu-server
     */
    private $request_scope = null;
    /**
     * @var string $request_prompt                            This parameter need use when you use many authentication
     */
    private $request_prompt = null;
    /**
     * @var array $request_acr_values                        It is gluu-server login parameter type
     */
    private $request_acr_values = null;

    /**
     * It is authorization url to gluu server.
     * After getting this parameter go to that url and you can login to gluu server, and get response about your users
     * @var string $response_authorization_url
     */
    private $response_authorization_url;

    /**
     * Constructor
     *
     * @return	void
     */
    public function __construct(GluuManager $mapper)
    {
        parent::__construct($mapper); // TODO: Change the autogenerated stub
        
    }
    /**
     * @return null
     */
    public function getRequestScope()
    {
        return $this->request_scope;
    }

    /**
     * @param null $request_scope
     */
    public function setRequestScope($request_scope)
    {
        $this->request_scope = $request_scope;
    }
    /**
     * @return string
     */
    public function getRequestPrompt()
    {
        return $this->request_prompt;
    }

    /**
     * @param string $request_prompt
     */
    public function setRequestPrompt($request_prompt)
    {
        $this->request_prompt = $request_prompt;
    }

    /**
     * @return string
     */
    public function getRequestOxdId()
    {
        return $this->request_oxd_id;
    }

    /**
     * @param string $request_oxd_id
     * @return void
     */
    public function setRequestOxdId($request_oxd_id)
    {
        $this->request_oxd_id = $request_oxd_id;
    }

    /**
     * @return array
     */
    public function getRequestAcrValues()
    {
        return $this->request_acr_values;
    }

    /**
     * @param array $request_acr_values
     * @return void
     */
    public function setRequestAcrValues($request_acr_values)
    {
        $this->request_acr_values = $request_acr_values;
    }

    /**
     * @return string
     */
    public function getResponseAuthorizationUrl()
    {
        $this->response_authorization_url = $this->getResponseData()->authorization_url;
        return $this->response_authorization_url;
    }
    /**
     * Protocol command to oXD server
     * @return void
     */
    public function setCommand()
    {
        $this->command = 'get_authorization_url';
    }
    /**
     * Protocol parameter to oXD server
     * @return void
     */
    public function setParams()
    {
        $this->params = array(
            "oxd_id" => $this->getRequestOxdId(),
            "acr_values" => $this->getRequestAcrValues(),
            "prompt" => $this->getRequestPrompt(),
            "scope" => $this->getRequestScope()
        );
    }

}