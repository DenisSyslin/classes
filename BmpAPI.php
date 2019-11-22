<?php

	/**
	 * API применяется для регистрации клиентов в системе, получения и изменения данных профиля, получения информации о покупках и бонусном балансе клиентов.
	 * 
	 * @name        BmpAPI
	 * @category	Subsribe
	 * @author		Denis Syslin <programist1985@gmail.com>
	 *
	 * @see https://personalarea.docs.apiary.io
	 * @see https://samarasoft.com/docs/loyalty/personal-area-api/
	 */
	 
	class BmpAPI {

		/**
		 * Метод запроса Get
		 * @const string
		 */
		const GET_METHOD = 'GET';
		
		/**
		 * Метод запроса POST
		 * @const string
		 */
		const POST_METHOD = 'POST';
		
		/**
		 * Метод запроса DELETE
		 * @const string
		 */
		const DELETE_METHOD = 'DELETE';

		/**
		 * Токен для доступа к системе
		 * @var string
		 */
		private $apiToken;

		/**
		 * Адрес сервиса
		 * @var string
		 */
		private $apiUrl;
		
		/**
		 * Время ожидания ответа от сервиса
		 * @var string
		 */
		private $timeout = 10;
		
		/**
		 * Заголовки запроса
		 * @var array
		 */
		protected $requestHeaders = [];
		
		/**
		 * Заголовки ответа
		 * @var array
		 */
		protected $responseHeaders = [];
		
		/**
		 * Ответ от сервера
		 * @var string
		 */
		protected $response = '';
		
		/**
		 * Тело запроса POST 
		 * @var string
		 */
		protected $request = '';
		
		/**
		 * Размер заголовков ответа
		 * @var string
		 */
		protected $headSize;
		
		/**
		 * HTTP код ответа
		 * @var string
		 */
		protected $httpCode;
		
		/**
		 * Заголовки запроса сформированный cURL
		 * @var string
		 */
		protected $headerOut;
		
		/**
		 * Конструктор
		 * 
		 * @param string $apiToken
		 * @param string $apiUrl
		 */
		public function __construct($apiToken, $apiUrl) {

			$this -> apiToken = $apiToken;
			$this -> apiUrl = $apiUrl;
		}

		/**
		 * Сеттер
		 * @param $key
		 * @param $value
		 */
		function __set($key, $value) {

			$this -> {$key} = $value;
		}

		/**
		 * Метод регистрации контакта в программе лояльности.
		 *
		 * @return array $params
		 * @return mixed
		 */
		public function contactCreate($params) {
			return $this -> call('contact/create', self::POST_METHOD, $params);
		}

		/**
		 * Метод редактирования данных клиента, таких как: 
		 * 		Бренд, ФИО, Email, Телефон, 
		 * 		Дата рождения, Пол, Страна, 
		 * 		Город, Адрес, Подписки, Произвольных полей в блоке customFields,
		 * 		Точка продаж, где выполняется регистрации контакта
		 *
		 * @return array $params
		 * @return mixed
		 */
		public function contactEdit($params) {
			return $this -> call('contact/edit', self::POST_METHOD, $params);
		}
		
		/**
		 * Метод получения данных клиента
		 *
		 * @return array $params идентификация пользователя id, loyaltyId, phone, email
		 * @return mixed
		 */
		public function getContactInfo($params) {
			return $this -> call('contact/info?' . $this -> setParams($params));
		}

		/**
		 * Метод подтверждения телефона в программе лояльности.
		 * 
		 * Метод инициирует отправку СМС с кодом подтверждения на указанный номер телефона
		 * Для подтверждения кода необходимо выполнить запрос contact/checkCode
		 * 
		 * @see https://samarasoft.com/docs/loyalty/admin-guide/processing-connection - настройка шаблона СМС
		 * @see https://samarasoft.com/docs/loyalty/personal-area-api/contact-checkcode/ - checkCode
		 * 
		 * @return array $params
		 * @return mixed
		 */
		public function contactSendCode($params) {
			return $this -> call('contact/sendCode', self::POST_METHOD, $params);
		}

		/**
		 * Метод подтверждения кода, отправленного на телефон контакта. 
		 * После успешного выполнения в bpm и личном кабинете проставляется соответствующий признак [Телефон подтвержден].
		 * 
		 * @return array $params
		 * @return mixed
		 */
		public function contactCheckCode($params) {
			return $this -> call('contact/checkCode', self::POST_METHOD, $params);
		}

		/**
		 * Метод создания и изменения подписки контакта на рассылку.
		 * 
		 * @return array $params
		 * @return mixed
		 */
		public function contactSubscribe($params) {
			return $this -> call('contact/subscribe', self::POST_METHOD, $params);
		}

		/**
		 * Метод отписки контакта от рассылки.
		 * 
		 * @return array $params
		 * @return mixed
		 */
		public function contactUnsubscribe($params) {
			return $this -> call('contact/unsubscribe', self::POST_METHOD, $params);
		}

		/**
		 * Метод получения информации о покупках текущего клиента
		 * 
		 * @return array $params идентификация пользователя id, loyaltyId, phone, email
		 * @return mixed
		 */
		public function getPurchaseList($params) {
			return $this -> call('purchase/list?' . $this -> setParams($params));
		}

		/**
		 * Метод получения информации о движениях бонусов клиента
		 * 
		 * @return array $params идентификация пользователя id, loyaltyId, phone, email
		 * @return mixed
		 */
		public function getBonusList($params) {
			return $this -> call('bonus/list?' . $this -> setParams($params));
		}
		
		/**
		 * Выполнить запрос
		 *
		 * @param string $action 
		 * @param string $httpMethod
		 * @param array $params
		 * @return mixed
		 */
		private function call($action, $httpMethod = 'GET', $params = []) {

			$options = [
				CURLOPT_URL            => $this -> apiUrl . $action,
				CURLOPT_ENCODING       => 'gzip, deflate',
				CURLOPT_VERBOSE        => false,
				CURLOPT_TIMEOUT        => $this -> timeout,
				CURLOPT_HEADER         => true,
				CURLINFO_HEADER_OUT    => true,
				CURLOPT_FRESH_CONNECT  => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_USERAGENT      => 'PHP Bmp API client 1.0.0',
				CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $this -> apiToken]
			];

			if ($httpMethod == self::POST_METHOD) {
				$this -> request = json_encode($params, JSON_UNESCAPED_UNICODE);
				$options[ CURLOPT_POST ] = true;
				$options[ CURLOPT_POSTFIELDS ] = $this -> request;
			} 
			else if ($httpMethod == self::DELETE_METHOD) {
				$options[ CURLOPT_CUSTOMREQUEST ] = self::DELETE_METHOD;
			}
			
			$curl = curl_init();
			curl_setopt_array($curl, $options);
			
			// Получить ответ
			$response = curl_exec($curl);
			
			// Размер заголовков
			$this -> headSize  = @curl_getinfo($curl, CURLINFO_HEADER_SIZE);
			$this -> headerOut = @curl_getinfo($curl, CURLINFO_HEADER_OUT);
			$this -> httpCode  = @curl_getinfo($curl, CURLINFO_HTTP_CODE);
			
			// Curl вернул ошибку
			if ($errorNumber = curl_errno($curl)) {
				
				$errorString = curl_error($curl);
				curl_close($curl);
			
				throw new Exception($errorString, $errorNumber);
			}
			
			curl_close($curl);
			
			//var_dump($response);
			return $this -> getRequestBody($response);
		}

		/**
		 * Получить тело ответа на запрос
		 *
		 * @param string $response строка ответа
		 * @return string тело ответа
		 */
		protected function getRequestBody($response) {

			// Ответ по частям
			$head = @substr($response, 0, $this -> headSize);
			$body = @substr($response,    $this -> headSize);
			$body = json_decode($body);

			if (empty($body)) {
				return false;
			}

			$this -> responseHeaders = $this -> parseResponseHeaders($head);

			return (object)$body;
		}  
		
		/**
		 * Разбор заголовков ответа
		 * 
		 * @param string $headerContent заголовки ответа в виде строки
		 * @return array
		 */ 	
		protected function parseResponseHeaders($headerContent) {
			
			$headers = [];

			$splitHeaders = explode("\r\n", $headerContent);
			
			// Разбиваем строку
			foreach ($splitHeaders as $i => $line) {
				
				if (empty($line)) {
					continue;
				}
				
				if ($i === 0) {
					$headers[ 'http_code' ] = $line;
				}
				else {
					
					list($key, $value) = explode(': ', $line);
					$headers[ $key ] = $value;
				}
			}
			
			return $headers;			
		}
		
		/**
		 * Установить параметры
		 *
		 * @param array $params
		 * @return string
		 */
		private function setParams($params = []) {

			$result = [];
			
			if (is_array($params)) {
				foreach ($params as $key => $value) {
					$result[ $key ] = $value;
				}
			}
			
			return urldecode(http_build_query($result));
		}
	}
	
    /* End of file BmpAPI.php */
    /* Location: ./classes/BmpAPI.php */
