<?php

namespace wuyuan;

use Exception;
use ErrorException;
use wuyuan\base\HttpException;
use wuyuan\base\FileException;
use wuyuan\base\DbException;
use wuyuan\base\Log;
use wuyuan\base\Config;
use wuyuan\base\Url;

/**
 * 目录分隔符.
 */
define('WY_DS', '/');

/**
 * 框架根目录.
 */
define('WY_DIR', strtr(__DIR__, '\\', WY_DS) . WY_DS);

/**
 * 调试模式.
 */
defined('WY_DEBUG') or define('WY_DEBUG', FALSE);

/**
 * 应用根目录.
 */
defined('WY_APP_DIR') or define('WY_APP_DIR', WY_DIR . '../application/');

/**
 * 应用配置目录.
 */
defined('WY_APP_CONFIG_DIR') or define('WY_APP_CONFIG_DIR', WY_APP_DIR . 'config/');

/**
 * 应用控制器目录.
 */
defined('WY_APP_CONTROLLER_DIR') or define('WY_APP_CONTROLLER_DIR', WY_APP_DIR . 'controller/');

/**
 * 应用模型目录.
 */
defined('WY_APP_MODEL_DIR') or define('WY_APP_MODEL_DIR', WY_APP_DIR . 'model/');

/**
 * 应用视图目录.
 */
defined('WY_APP_VIEW_DIR') or define('WY_APP_VIEW_DIR', WY_APP_DIR . 'view/');

/**
 * 应用运行时目录.
 */
defined('WY_APP_RUNTIME_DIR') or define('WY_APP_RUNTIME_DIR', WY_APP_DIR . 'runtime/');

/**
 * 框架入口类.
 * 主要负责框架初始化, 注册错误处理函数和自动加载类函数; 另提供一些公用方法.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class wy {
	
	/**
	 * 别名映射列表.
	 * 
	 * @var array
	 */
	private static $_pathAlias = ['@wuyuan' => WY_DIR];
	
	/**
	 * 类路径地址映射列表.
	 * 
	 * @var array
	 */
	private static $_classMap = [];
	
	/**
	 * HTTP 异常时的回调函数.
	 * 
	 * @var callable
	 */
	public static $onHttpError = NULL;
	
	/**
	 * 数据库连接异常时的回调函数.
	 * 
	 * @var callable
	 */
	public static $onDbError = NULL;
	
	/**
	 * 其它异常时的回调函数.
	 * 
	 * @var callable
	 */
	public static $onException = NULL;
	
	/**
	 * 解析 URL 之前的回调函数.
	 * 
	 * @var callable
	 */
	public static $onBeforeParseRequest = NULL;
	
	/**
	 * 执行请求分发之前的回调函数.
	 * 
	 * @var callable
	 */
	public static $onBeforeDispatch = NULL;

	/**
	 * 创建应用目录结构和默认文件.
	 * 
	 * @access private
	 * @return void
	 */
	private static function createApp() {
		if(is_dir(WY_APP_DIR)) {
			return ;
		}
		
		// 要创建的应用目录.
		$dires = [ 
			WY_APP_DIR, WY_APP_CONFIG_DIR, WY_APP_CONTROLLER_DIR, WY_APP_MODEL_DIR, WY_APP_VIEW_DIR, 
			WY_APP_RUNTIME_DIR
		];
		
		$config = <<<config
<?php 

// 应用配置.
return [
	// 公共配置.
	'common' => []
];
config;
		
		$controller = <<<controller
<?php 

namespace app\controller;

use wuyuan\base\Controller;

class IndexController extends Controller {
	public function indexAction() {
		echo 'Hello, world!!!';
	}

}
		
controller;
		
		// 创建目录.
		foreach($dires as $v) {
			if(!is_dir($v) && FALSE === mkdir($v, 0777, TRUE)) {
				throw new FileException('创建目录('. $v .')时出错.', FileException::CREATE_FAILD);
			}
		}
		
		// 创建文件.
		$configFilePath = WY_APP_CONFIG_DIR . 'config.inc.php';
		if(FALSE === file_put_contents($configFilePath, $config)) {
			throw new FileException('创建默认配置文件('. $configFilePath .')时出错.', FileException::CREATE_FAILD);
		}
		
		$controllerFilePath = WY_APP_CONTROLLER_DIR . 'IndexController.php';
		if(FALSE === file_put_contents($controllerFilePath, $controller)) {
			throw new FileException('创建默认控制器文件('. $controllerFilePath .')时出错.', FileException::CREATE_FAILD);
		}
	}

	/**
	 * 框架初始化.
	 * 
	 * @access public
	 * @return void
	 */
	public static function initialize() {
		// 创建应用目录结构和默认文件.
		if(WY_DEBUG) {
			self::createApp();
		}
		
		// 加载默认配置文件.
		Config::loadDefault();
	}
	
	/**
	 * 打印变量信息.
	 *
	 * @access public
	 * @param mixed $vars 变量或表达式.
	 * @return void
	 */
	public static function dump($vars) {
		$args = func_get_args();
		echo '<pre>';
		foreach($args as $v) {
			var_dump($v);
		}
		echo '</pre>';
	}
	
	/**
	 * 删除目录下所有文件.
	 *
	 * @access public
	 * @param string $dir 目录路径.
	 * @param string $recursive 递归删除, 默认 FALSE.
	 * @return boolean 删除成功返回 TRUE.
	 */
	public static function removeAllFile($dir, $recursive = FALSE) {
		if(empty($dir)) {
			throw new Exception('目录路径不能为空.');
		}
		
		try {
			$result = TRUE;
			$iterator = new \DirectoryIterator($dir);
		} catch(\Exception $e) {
			throw new FileException('打开目录('. $dir .')时出错.', FileException::READ_FAILD);
		}
		
		foreach($iterator as $item) {
			if(!$result) {
				break;
			} elseif($item->isFile()) {
				$_file_path = $item->getRealPath();
				$result = unlink($_file_path);
				if(FALSE === $result) {
					throw new FileException('删除文件('. $_file_path .')时出错.', FileException::WRITE_FAILD);
				}
				
				unset($_file_path);
			} elseif($item->isDir() && !$item->isDot() && $recursive) {
				$result = self::removeAllFile($item->getRealPath(), $recursive);
				// 删除空目录.
				if($result) {
					$_dir_path = $item->getRealPath();
					$result = rmdir($_dir_path);
					if(FALSE === $result) {
						throw new FileException('删除目录('. $_dir_path .')时出错.', FileException::WRITE_FAILD);
					}
				}
			}
		}
	
		return $result;
	}
	
	/**
	 * 添加路径别名.
	 *
	 * @access public
	 * @param string $alias 别名, 以 @ 开头.
	 * @param string $path 目录路径(建议绝对路径), 以 / 结尾.
	 * @return void
	 */
	public static function addAlias($alias, $path) {
		if(empty($alias) || empty($path)) {
			throw new Exception('别名或目录路径参数不能为空.');
		} elseif(0 !== strncmp('@', $alias, 1)) {
			throw new Exception('别名格式错误.');
		}
	
		if(!isset(self::$_pathAlias[$alias])) {
			self::$_pathAlias[$alias] = $path;
		}
	}
	
	/**
	 * 获取别名路径.
	 *
	 * @access public
	 * @param string $alias 别名.
	 * @return string $alias $alias 无效, 返回 NULL.
	 */
	public static function getAlias($alias) {
		return isset(self::$_pathAlias[$alias]) ? self::$_pathAlias[$alias] : NULL;
	}
	
	/**
	 * 引用文件.
	 *
	 * @access public
	 * @param string $filePath 要引用的文件路径(支持别名路径).
	 * @return boolean 已引用文件返回 TRUE.
	 */
	public static function import($filePath) {
		static $imports = [];
		if(!isset($imports[$filePath])) {
			$oriPath = $filePath;
			// 路径包含别名.
			if(0 === strncmp('@', $filePath, 1)) {
				$alias = strtok($filePath, WY_DS);
				if(!isset(self::$_pathAlias[$alias])) {
					throw new Exception('引用文件的别名('. $alias .')不存在.');
				}
	
				$filePath = str_replace($alias . WY_DS, self::$_pathAlias[$alias], $filePath);
			}
			
			if(!file_exists($filePath)) {
				throw new FileException('引用文件('. $filePath .')时出错.', FileException::NOT_FOUND);
			}
			
			require $filePath;
			$imports[$oriPath] = TRUE;
		}
	
		return $imports[$filePath];
	}
	
	/**
	 * 添加类路径地址映射.
	 *
	 * @access public
	 * @param string|array $className 类名或批量类地址映射.
	 * @param string $path 类文件路径(建议绝对路径).
	 * @return void
	 */
	public static function addClassMap($className, $path) {
		if(is_array($className)) {
			self::$_classMap = array_merge(self::$_classMap, $className);
		} elseif(!isset(self::$_classMap[$className])) {
			self::$_classMap[$className] = $path;
		}
	}

	/**
	 * 自动加载.
	 * 
	 * @access public
	 * @param string $className 类名.
	 * @return void
	 */
	public static function autoload($className) {
		$filePath = '';
		if(isset(self::$_classMap[$className])) {
			$filePath = self::$_classMap[$className];
		} else {
			$token = strtok($className, '\\');
			$ext = '.php';
			if('wuyuan' === $token) {
				$ext = '.class.php';
			}
				
			$alias = '@' . $token;
			if(!isset(self::$_pathAlias[$alias])) {
				throw new Exception('类('. $className .')的路径别名('. $alias .')不存在.');
			}
				
			$filePath = str_replace([$alias . '\\', '\\'], [self::$_pathAlias[$alias], WY_DS], '@' . $className . $ext);
		}
		
		if(!file_exists($filePath)) {
			throw new FileException('自动加载类文件('. $filePath .')时出错.', FileException::NOT_FOUND);
		}
		
		require $filePath;
	}
	
	/**
	 * 打印错误信息.
	 * 非调试模式, 会记录错误日志并处理 onHttpError 回调.
	 *
	 * @access private
	 * @param array|Exception $ex
	 * @return void
	 */
	private static function displayError($ex) {
		$errTypes = [
			E_NOTICE => 'Notice',
			E_USER_NOTICE => 'Notice',
			E_ERROR => 'Error',
			E_USER_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_USER_WARNING => 'Warning'
		];
	
		$eol = PHP_EOL;
		$strErr = '';
		$trace = [];
		if(is_array($ex)) {
			if(isset($errTypes[$ex['type']])) {
				$strErr .= '<b>' . $errTypes[$ex['type']] . ': </b> ';
			} else {
				$strErr .= '<b>Unkonwn: </b> ';
			}
				
			$strErr .= $ex['message'];
			$strErr .= ' in <b>' . $ex['file'] . '</b>';
			$strErr .= ' on line <b>' . $ex['line'] . '</b>.' . $eol;
		} else {
			$code = $ex->getCode();
			if($ex instanceof ErrorException) { // 错误信息.
				$strErr .= isset($errTypes[$code]) ? '<b>' . $errTypes[$code] . ': </b> ' : '<b>Unkonwn: </b> ';
			} else {
				$strErr .= '<b>Exception code: '. $code .'</b> ';
			}
	
			$strErr .= $ex->getMessage();
			$strErr .= ' in <b>' . $ex->getFile() . '</b>';
			$strErr .= ' on line <b>' . $ex->getLine() . '</b>' . $eol;
			// 获取跟踪信息.
			$trace = $ex->getTrace();
		}
	
		// 拼接错误调用跟踪信息.
		$traceLength = count($trace);
		if($traceLength > 1) {
			for($i = 0; $i < $traceLength; ++$i) {
				// $j = $i + 1;
				$j = $traceLength - ($i + 1);
				$_class = isset($trace[$i]['class']) ? $trace[$i]['class'] : '';
				$_object = isset($trace[$i]['object']) ? $trace[$i]['object'] : '';
				$_class = empty($_class) ? $_object : $_class;
				$_type = isset($trace[$i]['type']) ? $trace[$i]['type'] : '';
				$_function = isset($trace[$i]['function']) ? $trace[$i]['function'] : '';
	
				$strErr .= '<b>Trace #'. $j .'</b> call ' . $_class . $_type . $_function;
				$strErr .= isset($trace[$i]['file']) ? ' in <b>' . $trace[$i]['file'] . '</b>' : '';
				$strErr .= isset($trace[$i]['line']) ? ' on line <b>' . $trace[$i]['line'] . '</b>.' . $eol : $eol;
			}
		}
	
		if(WY_DEBUG) {
			if(!empty($strErr)) {
				echo '<pre>', nl2br($strErr), '</pre>';
			}
		} else {
			// 记录错误日志.
			if(Config::get('enable_log')) {
				Log::record(strip_tags($strErr));
			}
			
			if(NULL !== self::$onHttpError && $ex instanceof HttpException) { // HTTP 错误.
				call_user_func(self::$onHttpError, $ex);
			} elseif(NULL !== self::$onDbError && $ex instanceof DbException) { // 数据库错误.
				call_user_func(self::$onDbError, $ex);
			} elseif(NULL !== self::onException && $ex instanceof \Exception) { // 其它错误.
				call_user_func(self::$onException, $ex);
			}
		}
	}
	
	/**
	 * 打印致命错误信息.
	 *
	 * @access private
	 * @return void
	 */
	private static function displayFatalError() {
		$lastError = error_get_last();
		$strErr = '';
		if(NULL !== $lastError) {
			$strErr .= "<b>Fatal error: </b> ";
			$strErr .= $lastError['message'];
			$strErr .= ' in <b>' . $lastError['file'] . '</b> ';
			$strErr .= ' on line <b>' . $lastError['line'] . '</b>';
		}
	
		if(WY_DEBUG) {
			if(!empty($strErr)) {
				echo '<pre>', nl2br($strErr), '</pre>';
			}
		} elseif(Config::get('enable_log')) {
			Log::record(strip_tags($strErr));
			// 将错误日志写入文件.
			Log::save();
		}
	}

	/**
	 * 错误处理器.
	 * 
	 * @access public
	 * @param integer $errno 错误代码.
	 * @param string $msg 错误信息.
	 * @param string $file 文件路径.
	 * @param integer $line 行号.
	 * @return void
	 */
	public static function errorHandler($errno, $msg, $file, $line) {
		switch($errno) {
			case E_ERROR:
			case E_USER_ERROR:
				throw new ErrorException($msg, $errno, $errno, $file, $line);
				break;
			case E_NOTICE:
			case E_WARNING:
			case E_USER_NOTICE:
			case E_USER_WARNING:
				$err = [];
				$err['type'] = $errno;
				$err['message'] = $msg;
				$err['file'] = $file;
				$err['line'] = $line;
				self::displayError($err);
				break;
		}
	}

	/**
	 * 致命错误处理器.
	 * 
	 * @access public
	 * @return void
	 */
	public static function fatalHandler() {
		self::displayFatalError();
	}

	/**
	 * 异常处理器.
	 * 
	 * @access public
	 * @param \Exception $exception
	 * @return void
	 */
	public static function exceptionHandler(\Exception $exception) {
		self::displayError($exception);
	}

	/**
	 * 开始运行应用.
	 * 
	 * @access public
	 * @return void
	 */
	public static function run() {
		ini_set('session.auto_start', 0); // 关闭 php.ini 中的自动开启 session.
		$configs = Config::get();
		// 设置时区.
		date_default_timezone_set($configs['default_timezone']);
		if($configs['gzip_output'] && function_exists('ob_gzhandler')) {
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}
		
		header('Content-type: text/html; charset=' . $configs['default_charset']);
		unset($configs);
		
		if(NULL !== self::$onBeforeParseRequest) {
			call_user_func(self::$onBeforeParseRequest);
		}
		
		Url::parseRequest(); // 解析请求.
		if(NULL !== self::$onBeforeDispatch) {
			call_user_func(self::$onBeforeDispatch);
		}
		
		Url::dispatch(); // 请求分发.
		
		ob_end_flush();
		flush();
	}

}

// 引用框架核心类.
require WY_DIR . 'base/Exception.class.php';
require WY_DIR . 'base/Interfaces.class.php';

set_error_handler(['\wuyuan\wy', 'errorHandler']);
set_exception_handler(['\wuyuan\wy', 'exceptionHandler']);
register_shutdown_function(['\wuyuan\wy', 'fatalHandler']);
spl_autoload_register(['\wuyuan\wy', 'autoload'], TRUE, TRUE);

// 框架类映射.
$classMaps = [
	'wuyuan\base\Config' => WY_DIR . 'base/Config.class.php',
	'wuyuan\base\Controller' => WY_DIR . 'base/Controller.class.php',
	'wuyuan\base\Log' => WY_DIR . 'base/Log.class.php',
	'wuyuan\base\Model' => WY_DIR . 'base/Model.class.php',
	'wuyuan\base\Request' => WY_DIR . 'base/Request.class.php',
	'wuyuan\base\Url' => WY_DIR . 'base/Url.class.php',
	'wuyuan\base\Validator' => WY_DIR . 'base/Validator.class.php',
	'wuyuan\base\View' => WY_DIR . 'base/View.class.php',

	'wuyuan\cookie\Cookie' => WY_DIR . 'cookie/Cookie.class.php',

	'wuyuan\db\Db' => WY_DIR . 'db/Db.class.php',
	'wuyuan\db\Connection' => WY_DIR . 'db/Connection.class.php',
	'wuyuan\db\driver\Mysqli' => WY_DIR . 'db/driver/Mysqli.class.php',

	'wuyuan\image\Image' => WY_DIR . 'image/Image.class.php',
	'wuyuan\image\driver\Gd' => WY_DIR . 'image/driver/Gd.class.php',
	'wuyuan\image\dirver\Gif' => WY_DIR . 'image/driver/Gif.class.php',

	'wuyuan\download\Download' => WY_DIR . 'download/Download.class.php',

	'wuyuan\page\Page' => WY_DIR . 'page/Page.class.php',

	'wuyuan\session\Session' => WY_DIR . 'session/Session.class.php',

	'wuyuan\upload\Upload' => WY_DIR . 'upload/Upload.class.php',
	
	'wuyuan\util\DirtyWordFilter' => WY_DIR . 'util/DirtyWordFilter.class.php',
	'wuyuan\util\SocketHttp' => WY_DIR . 'util/SocketHttp.class.php',
	
	'wuyuan\vcode\Vcode' => WY_DIR . 'vcode/Vcode.class.php',
	
	'wuyuan\cache\Cache' => WY_DIR . 'cache/Cache.class.php',
	'wuyuan\cache\driver\File' => WY_DIR . 'cache/driver/File.class.php',
];

wy::addClassMap($classMaps, NULL);

// 执行框架初始化.
wy::initialize();
