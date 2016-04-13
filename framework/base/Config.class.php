<?php

namespace wuyuan\base;

/**
 * wuyuan 配置静态类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Config {
	
	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	private static $_configs = [];
	
	/**
	 * 应用的全部配置项.
	 * 
	 * @var array
	 */
	private static $_appConfigs = [];
	
	/**
	 * 加载默认配置项.
	 * 默认会加载框架目录下的 config.inc.php 和 应用配置目录下的 config.inc.php 中的 common 节点配置;
	 * 并将 common 节点中的配置覆盖框架配置中相同的配置项.
	 * 
	 * @access public
	 * @return void
	 */
	public static function loadDefault() {
		$frameConfigFilePath = WY_DIR . 'config.inc.php';
		$appConfigFilePath = WY_APP_CONFIG_DIR . 'config.inc.php';
		if(!is_readable($frameConfigFilePath)) {
			throw new FileException('加载框架配置文件('. $frameConfigFilePath .')时出错.', FileException::READ_FAILD);
		} elseif(!is_readable($appConfigFilePath)) {
			throw new FileException('加载应用配置文件('. $appConfigFilePath .')时出错.', FileException::READ_FAILD);
		}
		
		self::$_configs = require $frameConfigFilePath;
		self::$_appConfigs = require $appConfigFilePath;
		foreach(self::$_appConfigs['common'] as $k => $v) {
			if(isset(self::$_configs[$k]) && is_array($v)) {
				self::$_configs[$k] = array_merge(self::$_configs[$k], $v);
			} else {
				self::$_configs[$k] = $v;
			}
		}
	}
	
	/**
	 * 加载节点配置项.
	 * 加载应用配置目录下 config.inc.php 中的节点配置并将其合并到已有的配置项中.
	 * 
	 * @access public
	 * @param string $nodeName 节点配置名称.
	 * @return void
	 */
	public static function loadNode($nodeName) {
		if(isset(self::$_appConfigs[$nodeName])) {
			foreach(self::$_appConfigs[$nodeName] as $k => $v) {
				if(isset(self::$_configs[$k]) && is_array($v)) {
					self::$_configs[$k] = array_merge(self::$_configs[$k], $v);
				} else {
					self::$_configs[$k] = $v;
				}
			}
		}
	}
	
	/**
	 * 从文件加载配置.
	 * 加载指定的配置文件并将其合并到已有的配置项中.
	 * 
	 * @access public
	 * @param string $filePath 配置文件路径.
	 * @return void
	 */
	public static function loadFile($filePath) {
		if(!is_readable($filePath)) {
			throw new FileException('加载应用配置文件('. $filePath .')时出错.', FileException::READ_FAILD);
		}
		
		static $requireds = [];
		if(!isset($requireds[$filePath])) {
			$configs = require $filePath;
			foreach($configs as $k => $v) {
				if(isset(self::$_configs[$k]) && is_array($v)) {
					self::$_configs[$k] = array_merge(self::$_configs[$k], $v);
				} else {
					self::$_configs[$k] = $v;
				}
			}
		}
	}
	
	/**
	 * 获取配置值.
	 * 
	 * @access public
	 * @param string|array $name 配置名称, 默认 NULL; array 获取批量, NULL 获取全部. 
	 * @return mixed $name 为 string, 返回相应配置项的值; $name 无效返回 NULL; $name 为 NULL, 
	 * 返回 array; $name 为 array, 也返回 array.
	 */
	public static function get($name = NULL) {
		if(NULL === $name) {
			return self::$_configs;
		} elseif(is_array($name)) {
			$configs = [];
			foreach($name as $v) {
				$configs[$v] = isset(self::$_configs[$v]) ? self::$_configs[$v] : NULL;
			}
				
			return $configs;
		}
		
		return isset(self::$_configs[$name]) ? self::$_configs[$name] : NULL;
	}
	
}
