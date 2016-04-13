<?php

namespace wuyuan\cache;

use wuyuan\base\ICacheDriver;

/**
 * 缓存类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Cache implements ICacheDriver {

	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	protected $configs = [
		'class' => '\wuyuan\cache\driver\File',			// 缓存处理驱动类名.
		'opts' => [],									// // 驱动类配置项, 具体配置项参考各驱动类.
	];
	
	/**
	 * 驱动类对象.
	 * 
	 * @var \wuyuan\base\ICacheDriver
	 */
	private $_driver = NULL;

	/**
	 * 已关闭标记.
	 * 
	 * @var boolean
	 */
	private $_closed = FALSE;
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::connect()
	 */
	public function connect() {
		$driverClass = $this->configs['class'];
		$driverConfig = $this->configs['opts'];
		$this->_driver = new $driverClass($driverConfig);
		return $this->_driver->connect();
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::close()
	 */
	public function close() {
		if(NULL !== $this->_driver) {
			$this->_driver->close();
		}
		
		$this->_driver = NULL;
		$this->_closed = TRUE;
		return TRUE;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::flush()
	 */
	public function flush() {
		if(NULL === $this->_driver) {
			$this->connect();
		}
		
		$this->_driver->flush();
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::get()
	 */
	public function get($name) {
		if(NULL === $this->_driver) {
			$this->connect();
		}
		
		return $this->_driver->get($name);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::remove()
	 */
	public function remove($name) {
		if(NULL === $this->_driver) {
			$this->connect();
		}
		
		$this->_driver->remove($name);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::set()
	 */
	public function set($name, $value, $expire = NULL) {
		if(NULL === $this->_driver) {
			$this->connect();
		}
		
		$this->_driver->set($name, $value, $expire);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::setConfig()
	 */
	public function setConfig(array $configs) {
		$this->configs = array_merge($this->configs, $configs);
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::getInstance()
	 */
	public static function getInstance(array $configs = []) {
		static $ins = NULL;
		if(NULL === $ins) {
			$ins = new static($configs);
		}
		
		return $ins;
	}
	
	/**
	 * 设置属性值.
	 * 
	 * @param string $name 属性名称.
	 * @param mixed $value 属性值.
	 * @return \wuyuan\base\ICacheDriver
	 */
	public function __set($name, $value) {
		if(isset($this->configs[$name])) {
			$this->configs[$name] = $value;
		}
		
		return $this;
	}
	
	/**
	 * 获取属性值.
	 * 
	 * @access public
	 * @param string $name 属性名称.
	 * @return mixed 无效的属性返回 NULL.
	 */
	public function __get($name) {
		return isset($this->configs[$name]) ? $this->configs[$name] : NULL;
	}
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param array $configs 配置项, 为空使用配置文件中的配置.
	 * @return void
	 */
	public function __construct(array $configs = []) {
		if(empty($configs)) {
			$configs = \wuyuan\base\Config::get(get_class($this));
		}
		
		$this->configs = array_merge($this->configs, $configs);
	}
	
	/**
	 * 析构方法.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		if(!$this->_closed) {
			$this->close();
		}
	}

}
