<?php

namespace wuyuan\base;

/**
 * wuyuan 错误日志类.
 * 主要用于非调试模式下记录错误日志.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Log {

	/**
	 * 日志记录.
	 * 
	 * @var array
	 */
	private static $_logs = [];
	
	/**
	 * 日志文件路径.
	 * 
	 * @var string
	 */
	private static $_logFile = '';

	/**
	 * 错误类型.
	 * 
	 * @var array
	 */
	private static $_errTypes = [
		1 => 'System', 
		2 => 'Database', 
		8000 => 'User Custom'
	];
	
	/**
	 * 系统相关错误.
	 * 
	 * @var integer
	 */
	const SYSTEM = 1;

	/**
	 * Db 相关错误.
	 * 
	 * @var integer
	 */
	const DATABASE = 2;

	/**
	 * 自定义的相关错误.
	 * 
	 * @var integer
	 */
	const USER_CUSTOM = 8000;

	/**
	 * 格式化错误信息.
	 * 
	 * @access private
	 * @param string $content 错误内容.
	 * @param integer $type 错误类型.
	 * @return string
	 */
	private static function format($content, $type) {
		$eol = PHP_EOL;
		$datetime = date('Y-m-d H:i:s');
		// 默认 系统相关错误.
		$type = isset(self::$_errTypes[$type]) ? self::$_errTypes[$type] : self::$_errTypes[self::SYSTEM];
		$content = is_string($content) ? $content : var_export($content, TRUE);
		
		$return = '------------------------------------------------------------------------' . $eol;
		$return .= '时间: ' . $datetime . $eol;
		$return .= '类型: ' . $type . $eol;
		$return .= '描述: ' . $content . $eol;
		
		return $return;
	}

	/**
	 * 记录日志.
	 * 
	 * @access public
	 * @param string $content 日志内容.
	 * @param integer $type 错误类型, SYSTEM.
	 * @return void
	 */
	public static function record($content, $type = self::SYSTEM) {
		if(empty($content)) {
			return ;
		}
		
		self::$_logs[] = self::format($content, $type);
	}

	/**
	 * 将日志写入日志文件.
	 * 
	 * @access public
	 * @return void
	 */
	public static function save() {
		$configs = Config::get(__CLASS__);
		$savePath = $configs['savePath'];
		$saveName = $configs['saveName'];
		$extension = $configs['extension'];
		$maxSize = $configs['maxSize'];
		unset($configs);
		
		if(empty(self::$_logFile)) {
			if(!is_dir($savePath) && FALSE === mkdir($savePath, 0777, TRUE)) {
				throw new FileException('创建日志目录('. $savePath .')时出错.', FileException::CREATE_FAILD);
			}
			
			self::$_logFile = $savePath . $saveName . $extension;
		}
		
		// 将日志记录写入日志文件.
		foreach(self::$_logs as $content) {
			// 检测日志文件是否超过 maxSize, 超过就重命名.
			$fileSize = is_file(self::$_logFile) ? filesize(self::$_logFile) : FALSE;
			if(FALSE !== $fileSize && $fileSize > $maxSize) {
				self::$_logFile = $savePath . $saveName . '_' . time() . $extension;
			}
			
			if(is_file(self::$_logFile)) { // 存在追加模式写入内容.
				if(FALSE === file_put_contents(self::$_logFile, $content, FILE_APPEND)) {
					throw new FileException('写入日志文件('. self::$_logFile .')内容时出错.', FileException::WRITE_FAILD);
				}
			} else { // 不存在新建.
				if(FALSE === file_put_contents(self::$_logFile, $content)) {
					throw new FileException('写入日志文件('. self::$_logFile .')内容时出错.', FileException::WRITE_FAILD);
				}
			}
		}
		
		// 清空日志记录.
		self::$_logs = [];
	}
}
