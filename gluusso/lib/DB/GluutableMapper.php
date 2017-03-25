<?php
	
	/**
	 * @copyright Copyright (c) 2017, Gluu Inc. (https://gluu.org/)
	 * @license	  MIT   License            : <http://opensource.org/licenses/MIT>
	 *
	 * @package	  OpenID Connect SSO APP by Gluu
	 * @category  Application for NextCloud
	 * @version   3.0.1
	 *
	 * @author    Gluu Inc.          : <https://gluu.org>
	 * @link      Oxd site           : <https://oxd.gluu.org>
	 * @link      Documentation      : <https://gluu.org/docs/oxd/plugin/nextcloud/>
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
	
	namespace OCA\GluuSso\DB;
	
	use OCP\AppFramework\Db\Mapper;
	use OCP\DB\QueryBuilder\IQueryBuilder;
	use OCP\IDBConnection;
	use OCP\IUser;
	
	class GluutableMapper extends Mapper {
		
		public function __construct(IDBConnection $db) {
			parent::__construct($db, 'gluu_sso');
		}
		
		/**
		 * @param $action
		 * @return $result
		 */
		public function selectQuery($action){
			/* @var $qb IQueryBuilder */
			$qb = $this->db->getQueryBuilder();
			
			$qb->select('gluu_value')
				->from('gluu_sso')
				->where($qb->expr()->like('gluu_action', $qb->createNamedParameter($action)));
			$result = $qb->execute();
			$row = $result->fetch();
			$result->closeCursor();
			if ($row) {
				
				return $row['gluu_value'];
			}
			
			return 0;
			
		}
		
		/**
		 * @return $result
		 */
		public function selectGroupQuery(){
			/* @var $qb IQueryBuilder */
			$qb = $this->db->getQueryBuilder();
			
			$qb->select('*')
				->from('groups');
			$result = $qb->execute();
			$row = $result->fetchAll();
			$result->closeCursor();
			if ($row) {
				
				return $row;
			}
			
		}
		/**
		 * @param $action
		 * @param $value
		 * @return $result
		 */
		public function insertQuery($action, $value){
			$qb = $this->db->getQueryBuilder();
			$qb->insert('gluu_sso')
				->values(
					array(
						'gluu_action' => $qb->createNamedParameter($action),
						'gluu_value' => $qb->createNamedParameter($value)
					)
				);
			$qb->execute();
			
			return $this->selectQuery($action);
		}
		
		public function deleteQuery(){
			$qb = $this->db->getQueryBuilder();
			$qb->delete('gluu_sso')
				->where($qb->expr()->like('gluu_action', $qb->createNamedParameter('%gluu%')));
			
			$qb->execute();
			
		}
		
		/**
		 * update existing user in accounts table
		 *
		 * @param string $uid
		 * @param array $data
		 */
		public function updateUser($uid, $data) {
			$jsonEncodedData = json_encode($data);
			$query = $this->db->getQueryBuilder();
			$query->update('accounts')
				->set('data', $query->createNamedParameter($jsonEncodedData))
				->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
				->execute();
		}
		
		/**
		 * @param $action
		 * @param $value
		 * @return $result
		 */
		public function updateQuery($action, $value){
			$qb = $this->db->getQueryBuilder();
			$qb->update('gluu_sso')
				->set('gluu_value', $qb->createNamedParameter($value))
				->where($qb->expr()->like('gluu_action', $qb->createNamedParameter($action)));
			$qb->execute();
			
			$result = $this->selectQuery($action);
			return $result;
		}
		
	}