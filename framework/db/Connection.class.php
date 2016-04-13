<?php

namespace wuyuan\db;

use wuyuan\base\Config;
use wuyuan\base\DbException;

/**
 * wuyuan 数据库连接管理类.
 * master 配置, 每条配置是一维数组, 每条配置包含: 'host', 'port', 'userName', 'passwd', 'dbName', 
 * 'charset', 每条配置可以指定键名. slave 跟 master 配置相同. 
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Connection {
	
	/**
	 * 主数据库.
	 * 
	 * @var integer
	 */
	const DB_TYPE_MASTER = 1;
	
	/**
	 * 从数据库.
	 * 
	 * @var integer
	 */
	const DB_TYPE_SLAVE = 2;
	
	/**
	 * 其它数据库.
	 * 
	 * @var integer
	 */
	const DB_TYPE_OTHER = 3;
	
	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	protected $configs = [
		// 驱动类.
		'class' => '\wuyuan\db\driver\Mysqli',
		// 读写分离模式.
		'rwMode' => FALSE,
		// 主数据库连接配置项. ['host' => '127.0.0.1', 'port' => '3306', 'userName' => 'root',
		// 'passwd' => NULL, 'dbName' => NULL, 'charset' => 'utf8']
		'master' => [],
		// 从数据库连接配置项.
		'slave' => [],
		// 其它数据库连接配置项.
		'other' => [],
		// 主数据库全部不可用时, 用从数据库代替.
		'masterStopUseSlave' => FALSE,
		// 从数据库全部不可用时, 用主数据库代替.
		'slaveStopUseMaster' => FALSE
	];
	
	/**
	 * 当前连接对象.
	 *  
	 * @var \wuyuan\base\IDbDriver
	 */
	private $_linkId = NULL;
	
	/**
	 * 管理的连接.
	 * 
	 * @var array
	 */
	private $_links = [];
	
	/**
	 * 创建数据库连接.
	 * 
	 * @access public
	 * @param array $config 连接配置项.
	 * @return \wuyuan\db\Db 出错时抛异常.
	 */
	public function createDbLink($config) {
		if(NULL === $this->_linkId) {
			$driverClass = $this->configs['class']; // 驱动类.
			$this->_linkId = new $driverClass($config);
		}
		
		$this->_linkId->connect();
		return $this->_linkId;
	}
	
	/**
	 * 获取数据库连接对象.
	 * 
	 * @access public
	 * @param string $name 配置名称, 默认 NULL 表示随机(主从模式时始终是随机的), 每一组配置可以给一个命名键名.
	 * @param integer $type 连接配置类型, 默认 \wuyuan\db\Connection::DB_TYPE_MASTER, DB_TYPE_SLAVE, DB_TYPE_OTHER.
	 * @return \wuyuan\db\Db 返回数据库驱动对象, 出错时抛异常.
	 */
	public function getDbLink($name = NULL, $type = self::DB_TYPE_MASTER) {
		// 非读写模式只能使用 master 和 other 连接配置项.
		if(!$this->configs['rwMode']) {
			// 确定连接类型.
			$type = self::DB_TYPE_OTHER !== $type ? self::DB_TYPE_MASTER : self::DB_TYPE_OTHER;
			$strType = self::DB_TYPE_OTHER !== $type ? 'master' : 'other';
			// 确定配置.
			$ensureConfig = $this->configs[$strType];
			if(empty($ensureConfig)) {
				throw new DbException('连接配置项设置错误或已都失效.');
			}
			// 确定配置项的 key.
			$key = NULL === $name ? array_rand($ensureConfig) : $name;
			if(!isset($ensureConfig[$key])) {
				throw new DbException('无效的连接配置名称.');
			}
			
			$hashKey = md5(implode('', $ensureConfig[$key]));
			try {
				if(isset($this->_links[$hashKey])) {
					// 有效的连接.
					if($this->_links[$hashKey]->ping()) {
						return $this->_linkId = $this->_links[$hashKey];
					} else {
						throw new DbException('无效的数据库连接.', 0, $ensureConfig[$key]);
					}
				}
				
				$this->_linkId = $this->createDbLink($ensureConfig[$key]);
				return $this->_links[$hashKey] = $this->_linkId;
			} catch(\Exception $e) {
				unset($this->configs[$strType][$key]);
				unset($ensureConfig[$key]);
				unset($this->_links[$hashKey]);
				if(empty($ensureConfig)) {
					throw $e;
				} else {
					return $this->getDbLink($name, $type); // 继续获取.
				}
			}
		}
		
		// 读写分离模式, 只能使用 master 和 slave 连接配置项.
		switch($type) {
			case self::DB_TYPE_MASTER:
				// 确定配置.
				$ensureConfig = $this->configs['master'];
				if($this->configs['masterStopUseSlave'] && empty($ensureConfig)) {
					$ensureConfig = $this->configs['slave'];
				}
				if(empty($ensureConfig)) {
					throw new DbException('连接配置项设置错误或已都失效.');
				}
				// 确定配置的 key.
				$key = array_rand($ensureConfig);
				$hashKey = md5(implode('', $ensureConfig[$key]));
				try {
					if(isset($this->_links[$hashKey])) {
						// 有效的连接.
						if($this->_links[$hashKey]->ping()) {
							return $this->_linkId = $this->_links[$hashKey];
						} else {
							throw new DbException('无效的数据库连接.', 0, $ensureConfig[$key]);
						}
					}
					
					$this->_linkId = $this->createDbLink($ensureConfig[$key]);
					return $this->_links[$hashKey] = $this->_linkId;
				} catch(\Exception $e) {
					// 删除从数据库配置.
					if($this->configs['masterStopUseSlave'] && empty($this->configs['master'])) {
						unset($this->configs['slave'][$key]);
					}
					
					unset($ensureConfig[$key]);
					unset($this->configs['master'][$key]);
					unset($this->_links[$hashKey]);
					if(empty($ensureConfig)) {
						throw $e;
					} else {
						return $this->getDbLink($name, $type);
					}
				}
				break;
			case self::DB_TYPE_SLAVE:
				// 确定配置.
				$ensureConfig = $this->configs['slave'];
				if($this->configs['slaveStopUseMaster'] && empty($ensureConfig)) {
					$ensureConfig = $this->configs['master'];
				}
				if(empty($ensureConfig)) {
					throw new DbException('连接配置项设置错误或已都失效.');
				}
				// 确定配置的 key.
				$key = array_rand($ensureConfig);
				$hashKey = md5(implode('', $ensureConfig[$key]));
				try {
					if(isset($this->_links[$hashKey])) {
						// 有效的连接.
						if($this->_links[$hashKey]->ping()) {
							return $this->_linkId = $this->_links[$hashKey];
						} else {
							throw new DbException('无效的数据库连接.', 0, $ensureConfig[$key]);
						}
					}
					
					$this->_linkId = $this->createDbLink($ensureConfig[$key]);
					return $this->_links[$hashKey] = $this->_linkId;
				} catch(\Exception $e) {
					// 删除从数据库配置.
					if($this->configs['slaveStopUseMaster'] && empty($this->configs['slave'])) {
						unset($this->configs['master'][$key]);
					}
						
					unset($ensureConfig[$key]);
					unset($this->configs['slave'][$key]);
					unset($this->_links[$hashKey]);
					if(empty($ensureConfig)) {
						throw $e;
					} else {
						return $this->getDbLink($name, $type);
					}
				}
				break;
			default:
				throw new DbException('错误的连接配置类型(主从模式时只能使用 MASTER 和 SLAVE).');
		}
	}
	
	/**
	 * 最新的数据库连接对象.
	 * 
	 * @access public
	 * @return \wuyuan\base\IDbDriver
	 */
	public function lastDb() {
		return $this->_linkId;
	}
	
	/**
	 * 获取本类的单例对象.
	 * 
	 * @access public
	 * @param array $configs 连接配置选项.
	 * @return \wuyuan\db\Connection
	 */
	public static function getInstance(array $configs = []) {
		static $conn = NULL;
		if(NULL === $conn) {
			$conn = new static($configs);
		}
		
		return $conn;
	}
	
	/**
	 * 设置配置项.
	 * 
	 * @access public
	 * @param array $config 配置项.
	 * @return void
	 */
	public function setConfig(array $config) {
		$this->configs = array_merge($this->configs, $config);
	}
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param array $configs 配置项, 为空使用配置文件中的配置.
	 * @return void
	 */
	public function __construct(array $configs = []) {
		if(!empty($configs)) {
			$configs = Config::get(__CLASS__);
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
		$this->_linkId = NULL;
		foreach($this->_links as $db) {
			$db->close();
		}
	}
	
}
