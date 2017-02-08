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

	namespace OCA\GluuSso\Provider;


	use InvalidArgumentException;
	use OCA\GluuSso\DB\GluutableMapper;
	use OCA\GluuSso\DB\Gluutable;
	use OCP\Activity\IManager;
	use OCP\ILogger;
	use OCP\IRequest;
	use OCP\ISession;
	use OCP\IUser;
	use OCP\IDb;

	class GluuManager {

		/** @var GluutableMapper */
		private $mapper;


		/**
		 * @param GluutableMapper $mapper
		 */
		public function __construct(GluutableMapper $mapper) {
			$this->mapper = $mapper;
		}

		public function select_query($action){
			return $this->mapper->selectQuery($action);
		}
		
		public function select_group_query(){
			return $this->mapper->selectGroupQuery();
		}
		
		public function delete_query(){
			$this->mapper->deleteQuery();
		}
		
		public function updateUser($user, $data){
			return $this->mapper->updateUser($user, $data);
		}
		
		public function insert_query($action, $value){
			return $this->mapper->insertQuery($action, $value);
		}

		public function update_query($action, $value){
			return $this->mapper->updateQuery($action, $value);
		}

	}
