<?php

namespace wuyuan\base;

use wuyuan\wy;

/**
 * wuyuan URL 类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Url {
	
	/**
	 * 分组模式.
	 * 
	 * @var boolean
	 */
	private static $_isGroup = FALSE;
	
	/**
	 * 请求的分组名.
	 * 
	 * @var string
	 */
	private static $_groupName = '';
	
	/**
	 * 请求的控制器名.
	 * 
	 * @var string
	 */
	private static $_controllerName = '';
	
	/**
	 * 请求的动作名.
	 * 
	 * @var string
	 */
	private static $_actionName = '';
	
	/**
	 * URL 普通模式.
	 * 
	 * @var integer
	 */
	const MODE_NORMAL = 1;
	
	/**
	 * URL PATHINFO 模式.
	 * 
	 * @var integer
	 */
	const MODE_PATHINFO = 2;
	
	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	private static $_configs = [
		'urlMode' => 2,									// URL 模式, 1: 普通, 2: pathinfo.
		'rewrite' => FALSE,								// 开启 URL 重写.
		'pathinfoSplit' => '/',							// pathinfo 分隔符.
		'enablePathLetterSplit' => TRUE, 				// 开启pathinfo 字符请求解析和生成 URL 的转换字符.
		'pathLetterSplit' => '-',						// pathinfo 字符请求解析和生成 URL 的转换字符.
		'urlSuffix' => '.html',							// URL 后缀名.
		'controllerNs' => 'app\controller',				// 控制器命名空间.
		'controllerSuffix' => 'Controller',				// 控制器后缀名.
		'actionSuffix' => 'Action',						// 动作后缀名.
		'groupMode' => FALSE,							// 开启分组模式.
		'groupList' => [],								// 分组列表.
		'defaultGroup' => '',							// 默认分组.
		'defaultController' => 'Index',					// 默认控制器.
		'defaultAction' => 'index',						// 默认动作.
		'varGroup' => 'g',								// 分组参数名.
		'varController' => 'c',							// 控制器参数名.
		'varAction' => 'a',								// 动作参数名.
		'enableRequestRule' => FALSE,					// 开启请求的路由规则.
		'requestRules' => [],							// 请求路由规则列表.
		'enableCreateRule' => FALSE,					// 开启创建 URL 路由规则.
		'createRules' => [],							// 创建 URL 规则列表.
	];
	
	/**
	 * 转换路由字符串.
	 * @param string $str 待处理的字符串.
	 * @param integer $type 类型, 默认 1, 1:　将大字字母转换成以 -x, 2: 将 -x 转换成大写字母.
	 * @return string
	 */
	private static function convertRouteName($str, $type = 1) {
		if(FALSE === self::$_configs['enablePathLetterSplit']) {
			return $str;
		}
		
		$letterSplit = self::$_configs['pathLetterSplit'];
		if(1 === $type) {
			$pattern = '/[A-Z]/';
			$str = preg_replace($pattern, $letterSplit . '$0', $str);
			$str = strtolower(trim($str, $letterSplit));
			return $str;
		}
		
		$pattern = '/['. $letterSplit .'][a-z]/';
		$str = preg_replace_callback($pattern, function(array $mat) use($letterSplit) {
			return strtoupper(trim($mat[0], $letterSplit));
		}, $str);
		return ucfirst($str);
	}
	
	/**
	 * 解析生成 URL 规则.
	 *
	 * @access private
	 * @param string $url URL.
	 * @return string
	 */
	private static function parseCreateUrlRule($url) {
		foreach(self::$_configs['createRules'] as $reg => $replace) {
			if(preg_match($reg, $url)) {
				$url = preg_replace($reg, $replace, $url);
				break;
			}
		}
	
		return $url;
	}
	
	/**
	 * 解析请求 URL 规则.
	 *
	 * @access private
	 * @param string $url URL.
	 * @return string
	 */
	private static function parseRequestUrlRule($url) {
		foreach(self::$_configs['requestRules'] as $reg => $replace) {
			if(preg_match($reg, $url)) {
				$newUrl = preg_replace($reg, $replace, $url);
				$arrUrlInfo = parse_url($newUrl);
				$url = isset($arrUrlInfo['path']) ? $arrUrlInfo['path'] : $url;
				if(isset($arrUrlInfo['query']) && !empty($arrUrlInfo['query'])) {
					$arrQuery = [];
					parse_str($arrUrlInfo['query'], $arrQuery);
					foreach($arrQuery as $k => $v) {
						$_GET[$k] = $v;
					}
				}
				break;
			}
		}
	
		return $url;
	}
	
	/**
	 * 生成普通模式 URL Route.
	 * 
	 * @access private
	 * @param array $route 路由.
	 * @return string
	 */
	private static function resolveNormalRoute(array $route) {
		$keys = [self::$_configs['varController'],	self::$_configs['varAction']];
			// 分组模式增加分组变量名
		if(self::$_isGroup) {
			array_unshift($keys, self::$_configs['varGroup']);
		}

		// 合并变量名 和 route, 以便生成 URL GET 字符串
		$route = array_combine($keys, $route);
		return http_build_query($route);
	}

	/**
	 * 仅解析生成 URL 路由部分.
	 * route 为空自动加上当前的控制器和方法名,
	 * 若指定了方法名, 则会增加控制器名; 若是分组模式也会增加分组名.
	 *
	 * @access private
	 * @param string $route 路由.
	 * @return string
	 */
	private static function parseCreateUrlRoute($route) {
		$pathinfo = trim($route, '/');
		// $route 为空时, 设置当前请求的控制器, 方法
		if(empty($pathinfo)) {
			$pathinfo = self::convertRouteName(self::$_controllerName . '/' . self::$_actionName);
			$pathinfo = (self::$_isGroup ? self::$_groupName . '/' : '') . $pathinfo;
			return self::MODE_NORMAL === self::$_configs['urlMode'] ? 
					self::resolveNormalRoute(explode('/', $pathinfo)) : $pathinfo;
		}
	
		$pathInfoArr = explode('/', $pathinfo);
		// 只指定方法名时, 增加当前控制器
		if(1 == count($pathInfoArr)) {
			array_unshift($pathInfoArr, self::$_controllerName);
		}
	
		// 分组模式且只指定了控制器和方法时, 增加当前分组
		if(self::$_isGroup && 2 === count($pathInfoArr)) {
			array_unshift($pathInfoArr, self::$_groupName);
		}
	
		$sliceLen = self::$_isGroup ? 3 : 2;
		// 只取正常的个数.
		$pathInfoArr = array_slice($pathInfoArr, 0, $sliceLen);
		for($i = 0, $len = count($pathInfoArr); $i < $len; ++$i) {
			if(!self::$_isGroup || (self::$_isGroup && $i !== 0)) {
				$pathInfoArr[$i] = self::convertRouteName($pathInfoArr[$i]);
			}
		}
		return self::MODE_NORMAL === self::$_configs['urlMode'] ? 
				self::resolveNormalRoute($pathInfoArr) : implode('/', $pathInfoArr);
	}
	
	/**
	 * 仅解析生成 URL 路由参数部分.
	 *
	 * @access private
	 * @param array $params URL 参数, 默认 [].
	 * @return string
	 */
	private static function parseCreateUrlParam(array $params = []) {
		return http_build_query($params);
	}
	
	/**
	 * 解析 normal 模式.
	 *
	 * @access private
	 * @return void
	 */
	private static function parseNormal() {
		$defaultGroup = self::$_configs['defaultGroup'];
		$defaultController = self::$_configs['defaultController'];
		$defaultAction = self::$_configs['defaultAction'];
		$varGroup = self::$_configs['varGroup'];
		$varController = self::$_configs['varController'];
		$varAction = self::$_configs['varAction'];
	
		if(self::$_isGroup) {
			self::$_groupName = isset($_GET[$varGroup]) ? $_GET[$varGroup] : $defaultGroup;
		}
	
		self::$_controllerName = isset($_GET[$varController]) ? 
			self::convertRouteName($_GET[$varController], 2) : $defaultController;
		self::$_actionName = isset($_GET[$varAction]) ? 
			lcfirst(self::convertRouteName($_GET[$varAction]), 2) : $defaultAction;
	}
	
	/**
	 * 解析 pathinfo 模式.
	 *
	 * @access private
	 * @return void
	 */
	private static function parsePathinfo() {
		$pathinfo = Request::pathInfo(); // 请求的 path_info.
		if(self::$_configs['enableRequestRule']) {
			$pathinfo = self::parseRequestUrlRule($pathinfo); // 解析请求 URL 规则.
		}
		
		// 去掉 URL 后缀
		$pathinfo = str_replace(self::$_configs['urlSuffix'], '', trim($pathinfo, '/'));
		$infoArr = [];
		$defaultGroup = self::$_configs['defaultGroup'];
		$defaultController = self::$_configs['defaultController'];
		$defaultAction = self::$_configs['defaultAction'];
		$pathInfoSep = self::$_configs['pathinfoSplit'];
		if(empty($pathinfo)) {
			self::$_groupName = self::$_isGroup ? $defaultGroup : '';
			self::$_controllerName = $defaultController;
			self::$_actionName = $defaultAction;
			return ;
		} 
	
		// 将 path_info 字符串分解成数组
		$infoArr = explode($pathInfoSep, $pathinfo);
		$infoLen = count($infoArr);
		if(self::$_isGroup && 1 === $infoLen) { // 组模式且有 1 个值, 将其识别为 组名.
			$infoArr[] = $defaultController;
			$infoArr[] = $defaultAction;
		} elseif(self::$_isGroup && 2 === $infoLen) { // 组模式且有 2 个值, 将其识别为 组名/控制器名.
			$infoArr[] = $defaultAction;
		} elseif(1 === $infoLen) { // 非组模式有 1 个值, 将识别为 控制名.
			$infoArr[] = $defaultAction;
		}
		
		if(count($infoArr) < (self::$_isGroup ? 3 : 2)) {
			throw new HttpException('无效路由('. $pathinfo .').', HttpException::INVALID_ROUTE);
		}
	
		self::$_groupName = self::$_isGroup ? $infoArr[0] : '';
		self::$_controllerName = self::convertRouteName((self::$_isGroup ? $infoArr[1] : $infoArr[0]), 2);
		self::$_actionName = lcfirst(self::convertRouteName((self::$_isGroup ? $infoArr[2] : $infoArr[1]), 2));
	}
	
	/**
	 * 创建 URL.
	 * 
	 * @access public
	 * @param string $route 路由.
	 * @param array $params 参数, 默认 [].
	 * @return string
	 */
	public static function createUrl($route, array $params = []) {
		$route = self::parseCreateUrlRoute($route);
		$params = self::parseCreateUrlParam($params);
		$url = '';
		if(self::$_configs['rewrite']) {
			$url = Request::baseUrl();
		} else {
			$url = Request::scriptName();
		}
		
		$pathInfoSep = self::$_configs['pathinfoSplit'];
		if(1 === self::$_configs['urlMode']) { // 普通模式.
			$url .= self::$_configs['rewrite'] ? '/' : '';
			$url .= '?' . $route;
			$url .= empty($params) ? '' : '&' . $params;
		} else {
			$url .= '/' . strtr($route, '/', $pathInfoSep) . self::$_configs['urlSuffix'];
			$url .= empty($params) ? '' : '?' . $params;
			// 根据生成 URL 规则获取生成后的 URL
			if(self::$_configs['enableCreateRule']) {
				$url = self::parseCreateUrlRule($url);
			}
		}
		
		return $url;
	}
	
	/**
	 * 创建绝对 URL.
	 * 
	 * @access public
	 * @param string $route 路由.
	 * @param array $params 参数, 默认 [].
	 */
	public static function createAbsUrl($route, array $params = []) {
		return Request::domainUrl() . self::createUrl($route, $params);
	}
	
	/**
	 * 解析请求.
	 * 
	 * @access public
	 * @return void
	 */
	public static function parseRequest() {
		$configs = Config::get(__CLASS__);
		self::$_configs = array_merge(self::$_configs, $configs);
		unset($configs);
		// 是否为分组模式.
		$groupMode = self::$_configs['groupMode'] && self::$_configs['groupList'];
		self::$_isGroup = $groupMode && self::$_configs['defaultGroup'];
		// 设置应用路径别名.
		$alias = strtok(self::$_configs['controllerNs'], '\\');
		wy::addAlias('@' . $alias, WY_APP_DIR);

		// 根据不同的 urlMode 解析.
		switch(self::$_configs['urlMode']) {
			case self::MODE_NORMAL: // normal.
				self::parseNormal();
				break;
			case self::MODE_PATHINFO: // pathinfo.
				self::parsePathinfo();
				break;
			default: // 默认 pathinfo.
				self::parsePathinfo();
		}
		
		// 分组模式, 合并分组节点的配置项.
		if(self::$_isGroup) {
			Config::loadNode(self::$_groupName);
		}
	}
	
	/**
	 * 请求分发.
	 * 
	 * @access public
	 * @return void
	 */
	public static function dispatch() {
		$className = '\\' . self::$_configs['controllerNs'] . '\\';
		if(self::$_isGroup) {
			// 分组模式时判断请求的分组名是否是有效的分组.
			if(!in_array(self::$_groupName, self::$_configs['groupList'], TRUE)) {
				throw new HttpException('无效的分组('. self::$_groupName .').', HttpException::NOT_FOUND);
			}
			
			$className .= self::$_groupName . '\\';
		}
		
		$className .= self::$_controllerName . self::$_configs['controllerSuffix'];
		$methodName = self::$_actionName . self::$_configs['actionSuffix'];
		
		$refMethod = NULL;
		try {
			$controller = new \ReflectionClass($className);
			if($controller->isAbstract()) {
				throw new HttpException('拒绝访问(' . $className . ').', HttpException::ACCESS_DENIED);
			}
			
			$controller = new $className();
			$refMethod = new \ReflectionMethod($controller, $methodName);
		} catch(\Exception $e) {
			throw new HttpException($e->getMessage(), HttpException::NOT_FOUND, $e);
		}
		
		if(!$refMethod->isStatic() && $refMethod->isPublic()) {
			$param = $refMethod->getParameters();
			$args = [];
			/* @var $refParam \ReflectionParameter */
			foreach($param as $refParam) {
				$paramName = $refParam->getName();
				$paramValue = Request::get($paramName, NULL);
				if(isset($paramValue)) {
					$args[] = $paramValue;
				} elseif($refParam->isDefaultValueAvailable()) {
					$args[] = $refParam->getDefaultValue();
				}
			}
				
			// 判断是否有 _beforeAction 方法.
			$_action = '_beforeAction';
			if(is_callable([$controller, $_action])) {
				$controller->$_action(self::$_groupName, self::$_controllerName, self::$_actionName);
			}
				
			// 判断是否有 _before_actionName 的方法, 如: _before_index().
			$_action = '_before_' . self::$_actionName;
			if(is_callable([$controller, $_action])) {
				$controller->$_action(self::$_groupName, self::$_controllerName, self::$_actionName);
			}
				
			// 调用控制器方法
			$refMethod->invokeArgs($controller, $args);
				
			// 判断是否有 _after_actionName 的方法, 如: after_index().
			$_action = '_after_' . self::$_actionName;
			if(is_callable([$controller, $_action])) {
				$controller->$_action(self::$_groupName, self::$_controllerName, self::$_actionName);
			}
				
			// 判断是否有 _afterAction 方法.
			$_action = '_afterAction';
			if(is_callable([$controller, $_action])) {
				$controller->$_action(self::$_groupName, self::$_controllerName, self::$_actionName);
			}
		} else {
			throw new HttpException('调用方法('. $className . '::' . $methodName .')时出错.', HttpException::NOT_FOUND);
		}
	}
	
	/**
	 * 获取完整的请求路由.
	 * 
	 * @access public
	 * @return string
	 */
	public static function route() {
		return (self::$_isGroup ? self::$_groupName . '/' : '') . self::$_controllerName . '/' . self::$_actionName;
	}
	
	/**
	 * 分组模式.
	 *
	 * @access public
	 * @return boolean
	 */
	public static function isGroup() {
		return self::$_isGroup;
	}
	
	/**
	 * 请求的分组名.
	 *
	 * @access public
	 * @return string
	 */
	public static function groupName() {
		return self::$_groupName;
	}
	
	/**
	 * 请求的控制器名.
	 *
	 * @access public
	 * @return string
	 */
	public static function controllerName() {
		return self::$_controllerName;
	}
	
	/**
	 * 请求的动作名.
	 *
	 * @access public
	 * @return string
	 */
	public static function actionName() {
		return self::$_actionName;
	}
	
}
