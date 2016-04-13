<?php

namespace wuyuan\session;

use wuyuan\base\Config;

/**
 * wuyuan Session 操作类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Session {
	
	/**
	 * session 是否已开启.
	 * 
	 * @var boolean
	 */
	private static $_isStarted = FALSE;
	
	/**
	 * 是否已初始化.
	 * 
	 * @var boolean
	 */
	private static $_inited = FALSE;
	
	/**
	 * session 前缀.
	 * 
	 * @var string
	 */
	private static $_prefix = '';
	
	/**
	 * 自动开启 session.
	 * 
	 * @var boolean
	 */
	private static $_autoStart = FALSE;
	
	/**
	 * 初始化配置.
	 * 
	 * @access public
	 * @return void
	 */
	private static function _initialize() {
		if(self::$_inited) {
			return ;
		}
		
		$configs = Config::get(__CLASS__);
		self::$_prefix = $configs['prefix'];
		self::$_autoStart = $configs['auto_start'];
		session_name($configs['name']);
		ini_set('session.gc_maxlifetime', $configs['max_lifetime']);
		session_set_cookie_params($configs['cookie_expire'],
									$configs['cookie_path'],
									$configs['cookie_domain'],
									$configs['cookie_secure'],
									$configs['cookie_httpOnly']);
		unset($configs);
		self::$_inited = TRUE;
	}
	
	/**
	 * 开始会话.
	 * 
	 * @access public
	 * @return void
	 */
	public static function start() {
		if(!self::$_inited) {
			self::_initialize();
		}
		
		if(!self::$_isStarted) {
			session_start();
			self::$_isStarted = TRUE;
		}
	}
	
	/**
	 * 获取 Session.
	 * 
	 * @access public
	 * @param string $name 名称, 默认 NULL.
	 * @return mixed $name 为 NULL, 返回全部 session; $name 无效, 返回 NULL.
	 */
	public static function get($name = NULL) {
		if(!self::$_inited) {
			self::_initialize();
		}
		
		if(self::$_autoStart) {
			self::start();
		}
		
		if(NULL === $name) {
			return $_SESSION;
		}
		
		$prefix = self::$_prefix;
		$result = NULL;
		if(empty($prefix)) {
			$result = isset($_SESSION[$name]) ? $_SESSION[$name] : NULL;
		} else {
			$result = isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : NULL;
		}
		
		return $result;
	}
	
	/**
	 * 添加 Session.
	 * 
	 * @access public
	 * @param string|array $name 名称, array 为批量添加.
	 * @param mixed $value 值, 默认 NULL.
	 * @return void
	 */
	public static function set($name, $value = NULL) {
		if(!self::$_inited) {
			self::_initialize();
		}
		
		if(self::$_autoStart) {
			self::start();
		}
		
		$sess = $_SESSION;
		$prefix = self::$_prefix;
		if(is_array($name)) {
			foreach($name as $k => $v) {
				if(empty($prefix)) {
					$sess[$k] = $v;
				} else {
					$sess[$prefix][$k] = $v;
				}
			}
		} else {
			if(empty($prefix)) {
				$sess[$name] = $value;
			} else {
				$sess[$prefix][$name] = $value;
			}
		}
		
		$_SESSION = $sess; // 重新写入 session.
	}
	
	/**
	 * 删除 Session.
	 * 
	 * @access public
	 * @param string $name 名称.
	 * @return void
	 */
	public static function remove($name) {
		if(!self::$_inited) {
			self::_initialize();
		}
		
		if(self::$_autoStart) {
			self::start();
		}
		
		$prefix = self::$_prefix;
		if(empty($prefix) && isset($_SESSION[$name])) {
			unset($_SESSION[$name]);
			return;
		}
		
		if(isset($_SESSION[$prefix][$name])) {
			unset($_SERVER[$prefix][$name]);
		}
	}
	
	/**
	 * 清空会话.
	 * 
	 * @access public
	 * @return void
	 */
	public static function clear() {
		if(!self::$_inited) {
			self::_initialize();
		}
		
		if(self::$_autoStart) {
			self::start();
		}
		
		$prefix = self::$_prefix;
		if(empty($prefix)) {
			$_SESSION = [];
		} else {
			$_SESSION[$prefix] = [];
		}
		
		session_destroy();
		setcookie(session_name(), '', time() - 1);
	}
	
	/**
	 * 重新生成 SESSION_ID.
	 * 
	 * @access public
	 * @param boolean $isDeleteOld 是否删除旧 session_id 的文件, 默认 FALSE.
	 * @return boolean 成功返回 TRUE; 否则返回 FALSE.
	 */
	public static function reGenerateId($isDeleteOld = FALSE) {
		return session_regenerate_id($isDeleteOld);
	}
	
	/**
	 * 获取 SESSION_ID.
	 * 
	 * @access public
	 * @return string
	 */
	public static function getId() {
		return session_id();
	}
	
}
