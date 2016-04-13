<?php

namespace wuyuan\download;

use Exception;

/**
 * wuyuan 文件下载类.
 *
 * @author Liuping <xiaofengwz@163.com>
 */
class Download {

	/**
	 * 待下载文件路径.
	 *
	 * @var string
	 */
	private $_downFilePath = NULL;

	/**
	 * 每次读取的字节数, 默认 8192.
	 *
	 * @var integer
	 */
	private $_cache = 8192;

	/**
	 * 执行下载.
	 *
	 * @access public
	 * @param string $saveName 保存的文件名, 默认空串.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	public function execute($saveName = '') {
		if(empty($this->_downFilePath) || !is_file($this->_downFilePath)) {
			throw new Exception('没有下载的文件或文件无效.');
		}

		$fileSize = filesize($this->_downFilePath);
		if(empty($saveName)) {
			$saveName = pathinfo($this->_downFilePath, PATHINFO_BASENAME);
		} else {
			$saveName .= '.' . pathinfo($this->_downFilePath, PATHINFO_EXTENSION);
		}

		set_time_limit(0);
		ob_start();
		// 设置下载文件头.
		header('Content-Description: File Transfer');
		header('Cache-Control: max-age=0');		// IE 兼容处理.
		header("Content-type: application/octet-stream");
		header('Content-Transfer-Encoding:binary');
		header("Accept-Ranges: bytes");
		header("Accept-Length: {$fileSize}");
		header("Content-Disposition: attachment; filename={$saveName}");

		// 打开文件.
		$fp = fopen($this->_downFilePath, 'rb');
		if(!$fp) {
			throw new Exception('无法打开文件('. $this->_downFilePath .').');
		}

		// 读取文件内容.
		while(!feof($fp)) {
			echo fread($fp, $this->_cache);
			ob_flush();
			flush();
		}

		// 关闭文件.
		fclose($fp);
		ob_end_clean();
		return TRUE;
	}

	/**
	 * 设置每次读取文件的字节数.
	 *
	 * @access public
	 * @param integer $cache
	 * @return \wuyuan\download\Download
	 */
	public function setCache($cache) {
		$this->_cache = $cache;
		return $this;
	}

	/**
	 * 设置待下载的文件路径.
	 *
	 * @access public
	 * @param string $filePath
	 * @return \wuyuan\download\Download
	 */
	public function setFilePath($filePath) {
		$filePath = iconv('utf-8', 'gbk', $filePath);
		$this->_downFilePath = $filePath;
		return $this;
	}

	/**
	 * 构造方法.
	 *
	 * @access public
	 * @param string $filePath 待下载的文件路径, 默认 NULL.
	 * @param integer $cache 每次读取文件字节数, 默认 8192.
	 * @return void
	 */
	public function __construct($filePath = NULL, $cache = 8192) {
		if(NULL !== $filePath) {
			$this->setFilePath($filePath);
		}
		
		$this->_cache = $cache;
	}

}
