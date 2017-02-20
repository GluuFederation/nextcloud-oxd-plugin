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
 * User information class
 *
 * Class is connecting to oxd-server via socket, and getting logedin user information url from gluu-server.
 *
 * @package		  Gluu-oxd-library
 * @subpackage	Libraries
 * @category	  Relying Party (RP) and User Managed Access (UMA)
 * @see	        Client_OXD_RP
 */
    
namespace OCA\GluuSso\oxdrplib;
use OCA\GluuSso\oxdrplib\Client_OXD_RP;
use OCA\GluuSso\Provider\GluuManager;

class Get_user_info extends Client_OXD_RP
{
    /**
     * @var string $request_oxd_id                            This parameter you must get after registration site in gluu-server
     */
    private $request_oxd_id = null;
    /**
     * @var string $request_access_token                            This parameter you must get after using get_token_code class
     */
    private $request_access_token = null;
    /**
     * Response parameter from oXD-server
     * Showing logedin user information
     *
     * @var array $response_claims
     */
    private $response_claims;

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
     * @return array
     */
    public function getResponseClaims()
    {
        $this->response_claims = $this->getResponseData()->claims;
        return $this->response_claims;
    }

    /**
     * @return string
     */
    public function getRequestAccessToken()
    {
        return $this->request_access_token;
    }

    /**
     * @param string $request_access_token
     * @return void
     */
    public function setRequestAccessToken($request_access_token)
    {
        $this->request_access_token = $request_access_token;
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
     * Protocol command to oXD server
     * @return void
     */
    public function setCommand()
    {
        $this->command = 'get_user_info';
    }
    /**
     * Protocol parameter to oXD server
     * @return void
     */
    public function setParams()
    {
        $this->params = array(
            "oxd_id" => $this->getRequestOxdId(),
            "access_token" => $this->getRequestAccessToken()
        );
    }

}