<?php

namespace wuyuan\cookie;

use wuyuan\base\Config;

/**
 * wuyuan Cookie 操作类.
 * 
 * @property string $name 名称(只读).
 * @property mixed $value 值(只读). 
 * @property integer $expire 有效期(秒数).
 * @property string $path 有效路径, 默认 /.
 * @property string $domain 有效域名, 默认空串; 如主域下有效设置为 .xxx.com.
 * @property boolean $secure 仅 HTTPS 有效, 默认 FALSE.
 * @property boolean $httpOnly 仅 Http 协议有效, 默认 TRUE.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Cookie {
	
	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	protected $_configs = [
		// 有效期(秒数).
		'expire' => 0,
		// 有效路径.
		'path' => '/',
		// 有效域名, 主域名下所有子域名都有效, 设置为 .xxx.com.
		'domain' => '',
		// 仅 HTTPS 有效.
		'secure' => FALSE,
		// 仅 Http 协议有效.
		'httpOnly' => TRUE
	];
	
	/**
	 * Cookie 名称.
	 * 
	 * @var string
	 */
	protected $name = NULL;
	
	/**
	 * Cookie 值.
	 * 
	 * @var mixed
	 */
	protected $value = NULL;
	
	/**
	 * 添加 Cookie.
	 * 
	 * @access public
	 * @param \wy\http\Cookie $cookie
	 * @return boolean 添加成功返回 TRUE; 否则返回 FALSE.
	 */
	public static function add(Cookie $cookie) {
		return (boolean)setcookie($cookie->name, 
								  $cookie->value, 
								  $cookie->expire, 
								  $cookie->path, 
								  $cookie->domain, 
								  $cookie->secure,
								  $cookie->httpOnly);
	}
	
	/**
	 * 获取属性值.
	 * 
	 * @access public
	 * @param string $name 属性名.
	 * @return mixed $name 无效, 返回 NULL.
	 */
	public function __get($name) {
		if(isset($this->$name)) {
			return $this->$name;
		} elseif(isset($this->_configs[$name])) {
			return $this->_configs[$name];
		}
		
		return NULL;
	}
	
	/**
	 * 设置属性值.
	 * 
	 * @access public
	 * @param string $name 属性名.
	 * @param mixed $value 属性值.
	 * @return void
	 */
	public function __set($name, $value) {
		if(isset($this->_configs[$name])) {
			$this->_configs[$name] = $value;
		}
	}
	
	/**
	 * 构造方法.
	 *
	 * @access public
	 * @param string $name 名称.
	 * @param mixed $value 值.
	 * @param array $configs 配置项, 为空使用配置文件中的配置.
	 * @return void
	 */
	public function __construct($name, $value, array $configs = []) {
		if(empty($configs)) {
			$configs = Config::get(__CLASS__);
		}
				
		$this->_configs = array_merge($this->_configs, $configs);
		$this->name = $name;
		$this->value = $value;
	}
	
}
