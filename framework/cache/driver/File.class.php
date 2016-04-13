<?php

namespace wuyuan\cache\driver;

use wuyuan\base\ICacheDriver;
use wuyuan\cache\Cache;
use wuyuan\base\Log;
use wuyuan\base\CacheException;

/**
 * 文件缓存驱动类.
 * 
 * @property string $prefix 缓存文件名前缀.
 * @property string $cacheDir 缓存文件存放目录.
 * @property integer $expire 缓存时间(秒), 0: 表示永久.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class File extends Cache implements ICacheDriver {

	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	protected $configs = [
		'prefix' => 'cache_',			// 缓存文件名前缀.
		'cacheDir' => '',				// 缓存文件存放目录.
		'expire' => 0,					// 缓存时间(秒), 0: 表示永久.
		'lock' => [TRUE, 2, 100000],	// 锁配置, 0: 启用锁; 1: 尝试次数; 2: 失败时重新获取锁的间隔时间(微秒).
	];
	
	/**
	 * 文件句柄.
	 * 
	 * @var resource
	 */
	private $_handle = NULL;
	
	/**
	 * 读取文件内容时的 buffer.
	 * 
	 * @var integer
	 */
	private $_buffer = 4096;
	
	/**
	 * 设置锁.
	 * 
	 * @access private
	 * @param integer $lockType 锁类型, LOCK_SH(读), LOCK_EX(写), LOCK_UN.
	 * @return boolean 成功返回 TRUE; 否则返回 FALSE.
	 */
	private function _lock($lockType) {
		$opts = $this->configs['lock']; // 锁配置项.
		list($isLock, $times, $timeout) = $opts;
		// 未启用锁直接返回 TRUE.
		if(FALSE === $isLock) {
			return TRUE;
		}
		// 文件句柄无效, 记录日志.
		if(NULL === $this->_handle) {
			Log::record('获取文件缓存的锁时出错(无效的文件句柄).');
			return FALSE;
		}
		
		$flag = FALSE; // 获取锁成功标记.
		for($i = 0; $i < $times; ++$i) {
			if(flock($this->_handle, $lockType)) {
				$flag = TRUE;
				break;
			}
			
			usleep($timeout); // 暂停获取锁.
		}
		
		return $flag;
	}
	
	/**
	 * 生成缓存文件路径.
	 * 
	 * @access private
	 * @param string $name 缓存名称.
	 * @return string
	 */
	private function _makeFilePath($name) {
		$prefix = $this->configs['prefix'];
		return $this->configs['cacheDir'] . $prefix . md5($name) . '.txt';
	}
	
	/**
	 * 打开缓存文件.
	 * 
	 * @access private
	 * @param string $fileName 缓存文件路径.
	 * @param string $mode 文件打开方式, 参考 fopen.
	 * @return \wuyuan\base\ICache
	 */
	private function _open($fileName, $mode) {
		$dirname = dirname($fileName);
		// 创建缓存目录.
		if(!is_dir($dirname)) {
			if(!mkdir($dirname, 0755, TRUE)) {
				throw new CacheException('无法创建缓存目录(' . $dirname . ').');
			}
		}
		
		$this->_handle = fopen($fileName, $mode);
		if(!$this->_handle) {
			$this->_handle = NULL;
			throw new CacheException('打开或创建缓存文件(' . $fileName . ')时出错.');
		}
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::close()
	 */
	public function close() {
		if(NULL !== $this->_handle) {
			flock($this->_handle, LOCK_UN);
			fclose($this->_handle);
		}
		
		$this->_handle = NULL;
		return parent::close();
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::connect()
	 */
	public function connect() {
		if(empty($this->configs['cacheDir'])) {
			throw new CacheException('请指定缓存目录.');
		}
		
		return TRUE;
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::flush()
	 */
	public function flush() {
		if(empty($this->configs['cacheDir'])) {
			throw new CacheException('请指定缓存目录.');
		}
		
		try {
			\wuyuan\wy::removeAllFile($this->configs['cacheDir'], TRUE);
		} catch(\Exception $e) {
			throw new CacheException($e->getMessage());
		}
		
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::get()
	 */
	public function get($name) {
		if(NULL === $this->_handle) {
			$this->connect();
		}
		
		$fileName = $this->_makeFilePath($name); // 确定缓存文件.
		if(!is_file($fileName)) {
			throw new CacheException('无效的缓存文件(' . $fileName . ').');
		}

		$this->_open($fileName, 'rb+'); // 打开缓存文件.
		// 获取锁.
		if(!$this->_lock(LOCK_SH)) {
			throw new CacheException('获取文件缓存读锁失败.', CacheException::LOCK_FAILD);
		}
		// 读取文件内容.
		$content = '';
		while(!feof($this->_handle)) {
			$content .= fread($this->_handle, $this->_buffer);
		}
		
		// 释放锁.
		$this->_lock(LOCK_UN);
		// 缓存文件为空.
		if(empty(trim($content))) {
			return NULL;
		}
		
		// expire.
		$expire = (integer)substr($content, 0, 8); // 前 8 位表示 expire.
		// 缓存已失效.
		if($expire !== 0 && (time() - filemtime($fileName)) > $expire) {
			return NULL;
		}
		
		$content = unserialize(substr($content, 9)); // 数据.
		return FALSE === $content ? NULL : $content;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::remove()
	 */
	public function remove($name) {
		if(NULL === $this->_handle) {
			$this->connect();
		}
		
		$fileName = $this->_makeFilePath($name); // 确定缓存文件.
		$this->_open($fileName, 'ab+'); // 打开缓存文件.
		// 获取锁.
		if(!$this->_lock(LOCK_EX)) {
			throw new CacheException('获取文件缓存读锁失败.', CacheException::LOCK_FAILD);
		}
		
		fwrite($this->_handle, ''); // 清空文件内容.
		$this->_lock(LOCK_UN); // 释放锁.
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\ICacheDriver::set()
	 */
	public function set($name, $value, $expire = NULL) {
		if(NULL === $this->_handle) {
			$this->connect();
		}
		
		$fileName = $this->_makeFilePath($name); // 确定缓存文件.
		$this->_open($fileName, 'wb+'); // 打开缓存文件.
		// 获取锁.
		if(!$this->_lock(LOCK_EX)) {
			throw new CacheException('获取文件缓存读锁失败.', CacheException::LOCK_FAILD);
		}
		
		$expire = NULL === $expire ? (integer)$this->configs['expire'] : (integer)$expire; // 过期时间.
		$expire = sprintf("%08d", $expire); // 前 8 位表示过期时间.
		$content = $expire . "\n" . serialize($value); // 序列化存放数据.
		$strLen = strlen($content);
		$times = ceil($strLen / $this->_buffer);
		// 分多次写入.
		for($i = 0; $i < $times; ++$i) {
			fwrite($this->_handle, substr($content, $i * $this->_buffer, $this->_buffer));
		}
		
		$this->_lock(LOCK_UN); // 释放锁.
		return $this;
	}
	
}
