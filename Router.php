<?php

	namespace Core;

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

		public function compileRoute($route){

			$route = preg_replace('/\:(\w+)/i', '(?<$1>[^\\/]+)', $route);
			$route = '/^'.preg_replace('/\//', '\/', $route).'(\/|$)/iU';
			return $route;

		}

		public function addGet($route = '', $call){

			$_SERVER['REQUEST_METHOD'] == 'GET' && $this->addAll($route, $call);

		}

		public function addPost($route = '', $call){

			$_SERVER['REQUEST_METHOD'] == 'POST' && $this->addAll($route, $call);

		}

		public function addAjax($route = '', $call){

			$import = ['exp' => $route, 'call' => $call];
			isset($_SERVER['HTTP_X_REQUESTED_WITH'])
				&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
				&& $this->addAll($route, $call);

		}

		protected function addAll($route = '', $call){

			$route = $this->compileRoute($route);
			$this->queue[] = ['route' => $route, 'call' => $call];

		}

		public function callGet($route = '', callable $obj = null){

			return $_SERVER['REQUEST_METHOD'] == 'GET' ? $this->callAll($route, $obj) : false;

		}

		public function callPost($route = '', callable $obj = null){

			return $_SERVER['REQUEST_METHOD'] == 'POST' ? $this->callAll($route, $obj) : false;

		}

		public function callAjax($route = '', callable $obj = null){

			return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
				&& $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
				? $this->callAll($route, $obj) : false;

		}

		/**
		 * @brief Выполняет функцию обратного вызова по заданному маршруту.
		 * @param $route Регулярное выражение определяющее маршрут.
		 * @param $obj Функция обратного вызова.
		 * @return Array
		 */

		public function callAll($route = '', callable $obj = null){

			$route = $this->compileRoute($route);
			if(preg_match($route, $this->url, $matches)){
				return $obj ? call_user_func($obj, $matches, preg_replace($route, '', $this->url)) : $matches;
			}

		}

		/**
		 * @brief Запускает обработчики по текущему маршруту.
		 * @return Void
		 */

		public function start(){

			$queue = current($this->queue);
			if(!$queue) return;
			next($this->queue);
			extract($queue, EXTR_REFS);

			if(preg_match($route, $this->url, $req)){

				if($call instanceOf self){
					$call->url = '/'.preg_replace($route, '', $this->url);
					$call->props = $req;
					$call->addAll('', [$this, 'start']);
					$call->start();
				}
				elseif(is_callable($call))call_user_func($call, $req, [$this, 'start']);

			}
			else $this->start();

		}

	}
