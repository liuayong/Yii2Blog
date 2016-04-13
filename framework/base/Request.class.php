<?php

namespace wuyuan\base;

/**
 * wuyuan 请求类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Request {

	/**
	 * PATH_INFO.
	 *
	 * @access public
	 * @return string
	 */
	public static function pathInfo() {
		$infoName = Config::get('var_pathinfo_name');
		$pathInfo = isset($_SERVER[$infoName]) ? $_SERVER[$infoName] : '';
		// 服务器不支持 PATH_INFO 时, 计算 PATH_INFO
		if(empty($pathInfo)) {
			$uri = self::requestUri();
			$scriptName = self::scriptName();
			// 带有脚本名.
			if(FALSE !== strpos($uri, $scriptName)) {
			    $pathInfo = str_replace($scriptName, '', $uri);
			} else {
				// 开启 Rewrite.
				$baseUrl = self::baseUrl();
				if(empty($baseUrl)) {
					$pathInfo = parse_url($uri, PHP_URL_PATH);
				} else {
					$pathInfo = str_replace($baseUrl, '', $uri);
				}
			}
	
			// 再次处理 pathinfo
			if('/' == $pathInfo || '\\' == $pathInfo || !preg_match('/^\/.*/', $pathInfo)) {
				$pathInfo = '';
			}
		}
	
		return empty($pathInfo) ? '' : parse_url($pathInfo, PHP_URL_PATH);
	}

	/**
	 * 读取参数值.
	 * 从 $_GET, $_POST, $_REQUEST, $GLOBALS, $_SERVER, $_COOKIE 读取参数值;
	 * name 参数格式: get.参数名; 如 name 为 get. , 返回整个 $_GET 的值.
	 * 
	 * @access public
	 * @param string $name 参数名.
	 * @param mixed $default 默认值, 默认 NULL.
	 * @return mixed
	 */
	public static function getParam($name, $default = NULL) {
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		if(FALSE !== strpos($name, '.')) {
			list($method, $name) = explode('.', $name);
		}
		
		$method = strtolower($method);
		$input = [];
		switch($method) {
			case 'get':
				$input = & $_GET;
				break;
			case 'post':
				$input = & $_POST;
				break;
			case 'request':
				$input = & $_REQUEST;
				break;
			case 'globals':
				$input = & $GLOBALS;
				break;
			case 'server':
				$input = & $_SERVER;
				break;
			case 'cookie':
				$input = & $_COOKIE;
				break;
			default: // 没有有效的方法时
				$input = & $_GET;
		}
		
		if(isset($input[$name])) {
			return $input[$name];
		} elseif(empty($name)) {
			return $input;
		} else {
			return $default;
		}
	}
	
	/**
	 * 获取 $_GET 参数值.
	 * 
	 * @access public
	 * @param string $name 参数名.
	 * @param mixed $default 默认值, 默认 NULL.
	 * @return mixed
	 */
	public static function get($name, $default = NULL) {
		return self::getParam('get.' . $name, $default);
	}
	
	/**
	 * 获取 $_REQUEST 参数值.
	 *
	 * @access public
	 * @param string $name 参数名.
	 * @param mixed $default 默认值, 默认 NULL.
	 * @return mixed
	 */
	public static function params($name, $default = NULL) {
		return self::getParam('request.' . $name, $default);
	}
	
	/**
	 * 获取 $_POST 参数值.
	 *
	 * @access public
	 * @param string $name 参数名.
	 * @param mixed $default 默认值, 默认 NULL.
	 * @return mixed
	 */
	public static function post($name, $default = NULL) {
		return self::getParam('post.' . $name, $default);
	}
	
	/**
	 * 获取 $GLOBALS 参数值.
	 *
	 * @access public
	 * @param string $name 参数名.
	 * @param mixed $default 默认值, 默认 NULL.
	 * @return mixed
	 */
	public static function globals($name, $default = NULL) {
		return self::getParam('globals.' . $name, $default);
	}
	
	/**
	 * 获取 $_COOKIE 参数值.
	 *
	 * @access public
	 * @param string $name 参数名.
	 * @param mixed $default 默认值, 默认 NULL.
	 * @return mixed
	 */
	public static function cookie($name, $default = NULL) {
		return self::getParam('cookie.' . $name, $default);
	}
	
	/**
	 * 获取 $_SERVER 参数值.
	 *
	 * @access public
	 * @param string $name 参数名.
	 * @param mixed $default 默认值, 默认 NULL.
	 * @return mixed
	 */
	public static function server($name, $default = NULL) {
		return self::getParam('server.' . $name, $default);
	}

	/**
	 * REQUEST_URI.
	 * 
	 * @access public
	 * @return string
	 */
	public static function requestUri() {
		return $_SERVER['REQUEST_URI'];
	}

	/**
	 * 基本 URL.
	 * 
	 * @access public
	 * @return string
	 */
	public static function baseUrl() {
		$url = dirname($_SERVER['SCRIPT_NAME']);
		if('/' === $url || '\\' === $url) {
			$url = '';
		}
		
		return $url;
	}

	/**
	 * 带域名的 URL.
	 * 
	 * @access public
	 * @return string
	 */
	public static function domainUrl() {
		$http = self::isSecure() ? 'https://' : 'http://';
		$url = $http . $_SERVER['SERVER_NAME'];
		$port = intval($_SERVER['SERVER_PORT']);
		if(!self::isSecure() && 80 !== $port) {
			$url .= ':' . $port;
		}
		
		return $url;
	}

	/**
	 * SCRIPT_NAME.
	 * 
	 * @access public
	 * @return string
	 */
	public static function scriptName() {
		return $_SERVER['SCRIPT_NAME'];
	}

	/**
	 * QUERY_STRING.
	 * 
	 * @access public
	 * @return array
	 */
	public static function queryParams() {
		$params = [];
		$strQuery = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		parse_str($strQuery, $params);
		return $params;
	}

	/**
	 * 访问者IP.
	 * 
	 * @access public
	 * @param integer $type IP 类型, 0: 数字, 1: 字符串, 默认 1.
	 * @return integer|string
	 */
	public static function clientIp($type = 1) {
		$type = $type === 1 ? 1 : 0;
		$result = '';
		if(isset($_SERVER['HTTP_CLIENT_IP'])) {
			$result = $_SERVER['HTTP_CLIENT_IP'];
		} elseif(isset($_SERVER['REMOTE_ADDR'])) {
			$result = $_SERVER['REMOTE_ADDR'];
		} elseif(isset($_SERVER['REMOTE_HOST'])) {
			$result = $_SERVER['REMOTE_HOST'];
		} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { // 提取自 TP 框架.
			$_arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$_pos = array_search('unknown', $_arr);
			if($_pos !== FALSE) {
				unset($_arr[$_pos]);
			}
			
			$result = trim($_arr[0]);
		}
		
		$longip = sprintf('%u', ip2long($result));
		$result = $longip ? [$longip, $result] : [0, '0.0.0.0'];
		return $result[$type];
	}

	/**
	 * 是否为 HTTPS 请求.
	 * 
	 * @access public
	 * @return boolean
	 */
	public static function isSecure() {
		if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
			return TRUE;
		} elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * 是否为 GET 请求.
	 * 
	 * @access public
	 * @return boolean
	 */
	public static function isGet() {
		return 'GET' === (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET');
	}

	/**
	 * 是否为 POST 请求.
	 * 
	 * @access public
	 * @return boolean
	 */
	public static function isPost() {
		return 'POST' === (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET');
	}

	/**
	 * 是否为 AJAX 请求.
	 * 
	 * @access public
	 * @return boolean
	 */
	public static function isAjax() {
		$varIsAjax = Config::get('var_is_ajax');
		$result = FALSE;
		
		if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			$result = ('xmlhttprequest' === strtolower($_SERVER['HTTP_X_REQUESTED_WITH']));
		}
		if(FALSE === $result) {
			$result = isset($_GET[$varIsAjax]) ? (boolean)$_GET[$varIsAjax] : FALSE;
		}
		
		return $result;
	}

	/**
	 * 是否为 PUT 请求.
	 * 
	 * @access public
	 * @return boolean
	 */
	public static function isPut() {
		return 'PUT' === (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET');
	}

	/**
	 * 是否为 DELETE 请求.
	 * 
	 * @access public
	 * @return boolean
	 */
	public static function isDelete() {
		return 'DELETE' === (isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET');
	}

}
