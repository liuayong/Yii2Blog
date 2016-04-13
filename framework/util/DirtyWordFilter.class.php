<?php

namespace wuyuan\util;

use Exception;

/**
 * 脏词过滤处理类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class DirtyWordFilter {
	
	/**
	 * 脏词字典.
	 * 
	 * @var array
	 */
	private $_wordDic = [];
	
	/**
	 * 脏词组合字典(脏词解析后的字典库).
	 * 
	 * @var array
	 */
	private $_wordLib = [];
	
	/**
	 * 已找到的脏词.
	 * 
	 * @var array
	 */
	private $_findWord = [];
	
	/**
	 * 待搜索的内容.
	 * 
	 * @var string
	 */
	private $_findInContent = NULL;
	
	/**
	 * 解析字典库.
	 * 
	 * @access private
	 * @return void
	 */
	private function _parseDic() {
		foreach($this->_wordDic as $k => $v) {
			if(strpos($k, '+')) { // 有副词.
				list($pri, $detail) = explode('+', $k);
				$detail = explode('|', $detail); // 分隔成数组.
				foreach($detail as $w) {
					$this->_wordLib[] = $pri . $w;
				}
			} else {
				$this->_wordLib[] = $k;
			}
		}
	}
	
	/**
	 * 添加脏词字典.
	 * $word 格式: 主词+词1|词2|...n; 或 主词.
	 * 
	 * @access public
	 * @param string|array $word 脏词组; 为数组时, 批量添加.
	 * @return \wuyuan\util\DirtyWordFilter
	 */
	public function add($word) {
		if(is_array($word)) {
			foreach($word as $v) {
				if(!isset($this->_wordDic[$v])) {
					$this->_wordDic[$v] = TRUE;
				}
			}
		} elseif(!isset($this->_wordDic[$word])) {
			$this->_wordDic[$word] = TRUE;
		}
		
		$this->_parseDic(); // 解析字典库.
		return $this;
	}
	
	/**
	 * 删除脏词.
	 * 
	 * @access public
	 * @param string|array $word 脏词组; 为数组时, 批量删除.
	 * @return \wuyuan\util\DirtyWordFilter
	 */
	public function remove($word) {
		if(is_array($word)) {
			foreach($word as $w) {
				unset($this->_wordDic[$w]);
			}
		} else {
			unset($this->_wordDic[$word]);
		}
		
		$this->_parseDic(); // 解析字典库.
		return $this;
	}
	
	/**
	 * 从文件加载脏词字典.
	 * 每一行表示一个脏词组.
	 * 
	 * @access public
	 * @param string $filePath 待加载的文件路径.
	 * @param array $lock 加独占锁, 包含 2 个参数, 第 1 个为 boolean, TRUE 表示加锁, FALSE 表示不加锁;
	 * 第 2 个参数为尝试次数, 默认为 1, 表示在加锁失败时, 每隔多少微秒再尝试获取锁的次数; 第 3 个参数表示微秒数, 默认为 0.1 秒.
	 * 在获取锁的过程中, 在尝试完所有的次数后, 还是没有获取到锁, 会返回 FALSE.
	 * @return boolean 加载成功返回 TRUE, 出错时抛异常.
	 */
	public function load($filePath, array $lock = [FALSE, 1, 100000]) {
		if(!is_file($filePath) || !is_readable($filePath)) {
			throw new Exception('字典文件不存在或无读取权限.');
		}
		
		set_time_limit(0); // 设置脚本超时时间为直到执行结束.
		list($isLock, $times, $timeout) = $lock; // 获取参数.
		// 创建文件资源.
		$fp = fopen($filePath, 'rb+');
		if(FALSE === $fp) {
			throw new Exception('打开字典文件('. $filePath .')时出错.');
		}
		
		$flag = TRUE;
		if($isLock) {
			for($i = 1; $i <= $times; ++$i) {
				if(flock($fp, LOCK_SH)) {
					$flag = TRUE;
					break;
				}
		
				$flag = FALSE;
				usleep($timeout); // 暂停获取锁.
			}
		}
		
		// 获取锁失败.
		if(FALSE === $flag) {
			throw new Exception('获取读锁时出错.');
		}
		
		$flag = TRUE;
		while(!feof($fp)) {
			$res = fgets($fp);
			if(FALSE === $res) { // 读取出错, 不再继续读取.
				$flag = FALSE;
				break;
			} else {
				$res = trim($res, PHP_EOL);
				$this->_wordDic[$res] = TRUE;
			}
		}
		
		flock($fp, LOCK_UN); // 释放锁.
		fclose($fp); // 释放资源.
		if(!$flag) {
			throw new Exception('加载字典文件('. $filePath .')时出错.');
		}
		
		$this->_parseDic(); // 解析字典库.
		return TRUE;
	}
	
	/**
	 * 将脏词字典写入文件.
	 * 
	 * @access public
	 * @param string $filePath 待写入的文件路径.
	 * @param array $lock 加独占锁, 包含 2 个参数, 第 1 个为 boolean, TRUE 表示加锁, FALSE 表示不加锁;
	 * 第 2 个参数为尝试次数, 默认为 1, 表示在加锁失败时, 每隔多少微秒再尝试获取锁的次数; 第 3 个参数表示微秒数, 默认为 0.1 秒.
	 * 在获取锁的过程中, 在尝试完所有的次数后, 还是没有获取到锁, 会返回 FALSE.
	 * @return boolean 写入成功返回 TRUE, 出错时抛异常.
	 */
	public function save($filePath, array $lock = [FALSE, 1, 100000]) {
		if(!is_writable(dirname($filePath))) {
			throw new Exception('文件目录不存在或无写入权限');
		}
		
		set_time_limit(0); // 设置脚本超时时间为直到执行结束.
		list($isLock, $times, $timeout) = $lock; // 获取参数.
		// 创建文件资源.
		$fp = fopen($filePath, 'wb+');
		$flag = TRUE;
		if($isLock) {
			for($i = 1; $i <= $times; ++$i) {
				if(flock($fp, LOCK_EX)) {
					$flag = TRUE;
					break;
				}
				
				$flag = FALSE;
				usleep($timeout); // 暂停获取锁.
			}
		}
		
		// 获取锁失败.
		if(FALSE === $flag) {
			throw new Exception('获取写锁失败');
		}
		
		$flag = TRUE;
		$len = count($this->_wordDic);
		$i = 1;
		$content = '';
		foreach($this->_wordDic as $k => $v) {
			$content .= ($i === $len ? $k : $k . PHP_EOL);
			$i++;
		}
		
		// 写入出错.
		if(FALSE === fwrite($fp, $content)) {
			$flag = FALSE;
		}
		
		flock($fp, LOCK_UN); // 释放锁.
		fclose($fp); // 释放资源.
		if(!$flag) {
			throw new Exception('写入字典文件('. $filePath .')时出错.');
		}
		
		return TRUE;
	}
	
	/**
	 * 在指定的内容中搜索脏词.
	 * $recursive 为 TRUE 将找出全部的脏词; 否则在找到第一个时将停止搜索.
	 * 
	 * @access public
	 * @param string $content 待搜索的内容.
	 * @param string $recursive 递归搜索, 默认 TRUE.
	 * @return array 返回搜索到的脏词.
	 */
	public function search($content, $recursive = TRUE) {
		$result = [];
		foreach($this->_wordLib as $v) {
			if(FALSE !== strpos($content, $v)) {
				$result[] = $v;
				if(FALSE === $recursive) {
					break;
				}
			}
		}
		
		$this->_findInContent = $content;
		return $this->_findWord = $result;
	}
	
	/**
	 * 替换脏词.
	 * 将搜索到的脏词字用 $str 替换, 每一个字用 $str 替换.
	 * 使用前必须调用 search 方法; 否则返回 NULL. 
	 * 
	 * @access public
	 * @param string $str 替换字符.
	 * @return string|NULL 返回替换后的内容.
	 */
	public function replace($str) {
		if(empty($this->_findWord) || NULL === $this->_findInContent) {
			return NULL;
		}
		
		return str_replace($this->_findWord, array_fill(0, count($this->_findWord), $str), $this->_findInContent);
	}
	
	/**
	 * 析构方法.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		$this->_wordLibrary = $this->_wordDic = $this->_findWord = [];
		$this->_findInContent = NULL;
	}
	
}
