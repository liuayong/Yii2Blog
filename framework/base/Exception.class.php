<?php

namespace wuyuan\base;

use Exception;

/**
 * wuyuan Http 异常.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class HttpException extends Exception {

	/**
	 * 页面不存在.
	 * 
	 * @var integer
	 */
	const NOT_FOUND = 404;

	/**
	 * 拒绝访问.
	 * 
	 * @var integer
	 */
	const ACCESS_DENIED = 403;

	/**
	 * 路由错误.
	 * 
	 * @var integer
	 */
	const INVALID_ROUTE = 2001;

}

/**
 * wuyuan 文件异常.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class FileException extends Exception {

	/**
	 * 文件或目录不存在.
	 * 
	 * @var integer
	 */
	const NOT_FOUND = 1001;

	/**
	 * 文件或目录已存在.
	 * 
	 * @var integer
	 */
	const ALREADY_EXIST = 1002;

	/**
	 * 读取失败.
	 * 
	 * @var integer
	 */
	const READ_FAILD = 1003;

	/**
	 * 创建失败.
	 * 
	 * @var integer
	 */
	const CREATE_FAILD = 1004;

	/**
	 * 写入失败.
	 * 
	 * @var integer
	 */
	const WRITE_FAILD = 1005;

}

/**
 * wuyuan Db 异常.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class DbException extends Exception {
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param string $message 错误信息.
	 * @param integer $code 错误代码.
	 * @param array $extraData 额外信息.
	 * @param Exception $previous 前一个异常.
	 * @return void
	 */
	public function __construct($message, $code = 0, array $extraData = [], Exception $previous = NULL) {
		parent::__construct($message . PHP_EOL . json_encode($extraData), $code, $previous);
	}
}

/**
 * wuyuan 缓存错误异常.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class CacheException extends Exception {
	
	/**
	 * 锁失败.
	 * 
	 * @var integer
	 */
	const LOCK_FAILD = 1;
	
	/**
	 * 锁成功.
	 * 
	 * @var integer
	 */
	const LOCK_SUCCESS = 2;
	
}
