<?php

	class Router{

		protected $url = ''; /**< URL текущего маршрута.*/
		protected $props = []; /**< Параметры маршрута.*/
		private $queue = []; /**< Очередь обработчиков.*/

		/**
		 * @brief Конструктор класса.
		 */

		public function __construct(){

			$this->url = $this->url
			? $this->url
			: '/'.preg_replace('/^(\/)(.*)\/?(\?.*)?$/U', '$2', $_SERVER['REQUEST_URI']);

		}

		public function addGet($exp = '', $call){

			$_SERVER['REQUEST_METHOD'] == 'GET' && $this->addAll($exp, $call);

		}

		public function addPost($exp = '', $call){

			$_SERVER['REQUEST_METHOD'] == 'POST' && $this->addAll($exp, $call);

		}

		public function addAjax($exp = '', $call){

			$import = ['exp' => $exp, 'call' => $call];
			isset($_SERVER['HTTP_X_REQUESTED_WITH'])
				&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
				&& $this->addAll($exp, $call);

		}

		public function addAll($exp = '', $call){
			
			$exp = '/^'.preg_replace('/\//', '\/', $exp).'(\/|$)/U';
			isset($this->queue[$exp])
			? $this->queue[$exp][] = $call
			: $this->queue[$exp] = [$call];

		}

		public function callGet($exp = '', callable $obj = null){

			return $_SERVER['REQUEST_METHOD'] == 'GET' ? $this->callAll($exp, $obj) : false;

		}

		public function callPost($exp = '', callable $obj = null){

			return $_SERVER['REQUEST_METHOD'] == 'POST' ? $this->callAll($exp, $obj) : false;

		}

		public function callAjax($exp = '', callable $obj = null){

			return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
				&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
				? $this->callAll($exp, $obj) : false;

		}

		/**
		 * @brief Выполняет функцию обратного вызова по заданному маршруту.
		 * @param $exp Регулярное выражение определяющее маршрут.
		 * @param $obj Функция обратного вызова.
		 * @return Array
		 */

		public function callAll($exp = '', callable $obj = null){

			$exp = '/^'.preg_replace('/\//', '\/', $exp).'(\/|$)/U';
			if(preg_match($exp, $this->url, $matches)){
				return $obj ? call_user_func($obj, $matches, preg_replace($exp, '', $this->url)) : $matches;
			}

		}

		/**
		 * @brief Запускает обработчики по маршруам.
		 * @return Void
		 */

		public function start(){
			
			foreach($this->queue as $exp => $routes){
				if(preg_match($exp, $this->url, $matches)){
					$next = function() use (&$exp, &$routes, &$matches, &$next){
						$call = current($routes);
						next($routes);
						if($call instanceOf self){
							$call->url = '/'.preg_replace($exp, '', $this->url);
							$call->props = $matches;
							$call->start();
						}
						elseif(is_callable($call))
							call_user_func($call, $matches, $next);
					};
					$next();
				}
			}
			
		}

	}
