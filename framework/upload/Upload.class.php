<?php

namespace wuyuan\upload;

use wuyuan\base\Config;
use Exception;

/**
 * wuyuan 文件上传类.
 * 
 * @property string $rootPath 上传根目录.
 * @property string $savePath 上传文件分类目录名.
 * @property integer $maxSize 单个文件的大小(字节数), 0: 无限制.
 * @property integer $maxFile 每次最多允许上传文件个数, 0: 无限制.
 * @property array $mimes 允许上传的 mime 类型列表, 空: 无限制.
 * @property array $exts 允许上传的扩展名列表, 空: 无限制.
 * @property string $subName 子目录名[callable, 参数|[参数...]]; FALSE 表示不使用子目录.
 * @property string $saveName 文件名生成规则[callable, 参数|[参数...]]; FALSE 表示使用原文件名.
 * @property boolean $replace 文件重名覆盖.
 * @property boolean $hash 生成文件 hash 编码(hash 和 md5).
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Upload {
	
	/**
	 * 非法的上传文件.
	 * 
	 * @var integer
	 */
	const ERR_ILLEGAL_FILE = -1;
	
	/**
	 * 上传的文件超过了指定的大小.
	 * 
	 * @var integer
	 */
	const ERR_SPEC_MAX_SIZE = -2;
	
	/**
	 * 上传的文件的 MIME 类型非法.
	 * 
	 * @var integer
	 */
	const ERR_TYPE_MIME = -3;
	
	/**
	 * 上传的文件的扩展名非法.
	 * 
	 * @var integer
	 */
	const ERR_TYPE_EXT = -4;
	
	/**
	 * 上传成功.
	 * 
	 * @var integer
	 */
	const ERR_OK = 0;
	
	/**
	 * 上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值.
	 * 
	 * @var integer
	 */
	const ERR_INI_MAX_SIZE = 1;
	
	/**
	 * 上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值.
	 * 
	 * @var integer
	 */
	const ERR_FORM_MAX_SIZE = 2;
	
	/**
	 * 文件只有部分被上传.
	 * 
	 * @var integer
	 */
	const ERR_PARTIAL = 3;
	
	/**
	 * 没有文件被上传.
	 * 
	 * @var integer
	 */
	const ERR_NO_FILE = 4;
	
	/**
	 * 找不到临时文件夹.
	 * 
	 * @var integer
	 */
	const ERR_NOT_FOUND_TEMP_DIR = 5;
	
	/**
	 * 文件写入失败.
	 * 
	 * @var integer
	 */
	const ERR_CANT_WRITE = 6;
	
	/**
	 * 未知上传错误.
	 * 
	 * @var integer
	 */
	const ERR_UNKNOWN = 7;
	
	/**
	 * 移动到目标位置时出错.
	 * 
	 * @var integer
	 */
	const ERR_MOVE_DEST = 8;
	
	/**
	 * 配置项.
	 * 
	 * @var array
	 */
	private $_configs = [
		'rootPath' => '',								// 上传根目录.
		'savePath' => '',								// 上传文件分类目录名.
		'maxSize' => 0,									// 单个文件的大小(字节数), 0: 无限制.
		'maxFile' => 0,									// 每次最多允许上传文件个数, 0: 无限制.
		'mimes' => [],									// 允许上传的 mime 类型列表, 空: 无限制.
		'exts' => [],									// 允许上传的扩展名列表, 空: 无限制.
		'subName' => ['date', 'Y-m-d'],					// 子目录名[callable, 参数|[参数...]]; FALSE 表示不使用子目录.
		'saveName' => ['uniqid', ['uploads_', TRUE]],	// 文件名生成规则[callable, 参数|[参数...]]; FALSE 表示使用原文件名.
		'replace' => FALSE,								// 文件重名覆盖.
		'hash' => TRUE									// 生成文件 hash 编码(hash 和 md5).
	];
	
	/**
	 * 错误状态.
	 * 
	 * @var array
	 */
	private $_statusMsg = [
		-1 => '非法的上传文件',
		-2 => '上传的文件超过了指定的大小',
		-3 => '上传的文件的 MIME 类型非法',
		-4 => '上传的文件的扩展名非法',
		0 => '上传成功',
		1 => '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值',
		2 => '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值',
		3 => '文件只有部分被上传',
		4 => '没有文件被上传',
		5 => '找不到临时文件夹',
		6 => '文件写入失败',
		7 => '未知上传错误',
		8 => '移动到目标位置时出错'
	];
	
	/**
	 * 上传的错误信息.
	 * 
	 * @var array
	 */
	private $_upload_error = [];
	
	/**
	 * 上传成功的文件信息.
	 * 
	 * @var array
	 */
	private $_uploadInfo = [];
	
	/**
	 * 生成子目录名.
	 * subName 为 FALSE, 表示不使用子目录; [0: 回调函数(callable), 1: 参数(多个用数组)].
	 * 
	 * @access private
	 * @return string
	 */
	private function _makeSubName() {
		// 不使用子目录存放.
		if(FALSE === $this->_configs['subName']) {
			return '';
		}
		
		$callback = '';
		$args = '';
		// subName 不支持回调参数.
		if(is_string($this->_configs['subName'])) {
			$callback = $this->_configs['subName'];
		} else {
			list($callback, $args) = $this->_configs['subName'];
		}
		
		if(is_array($args)) {
			return call_user_func_array($callback, $args);
		}
		
		return call_user_func($callback, $args);
	}
	
	/**
	 * 生成保存的文件名.
	 * saveName 为 FALSE, 表示使用原文件名; [0: 回调函数(callable), 1: 参数(多个用数组)].
	 * 
	 * @access private
	 * @return string
	 */
	private function makeSaveName() {
		// 保持原文件名.
		if(FALSE === $this->_configs['saveName']) {
			return '';
		}
		
		$callback = '';
		$args = '';
		// saveName 不支持回调参数.
		if(is_string($this->_configs['saveName'])) {
			$callback = $this->_configs['saveName'];
		} else {
			list($callback, $args) = $this->_configs['saveName'];
		}
		
		if(is_array($args)) {
			return call_user_func_array($callback, $args);
		}
		
		return call_user_func($callback, $args);
	}
	
	/**
	 * 将 $_FILES 转换成统一的三维数组.
	 * [
	 * 		[第 1 个文件信息],
	 * 		[第 2 个文件信息]
	 * ]
	 * 
	 * @access private
	 * @param array $files $_FILES 数组.
	 * @return array
	 */
	private function _resolveFiles(array $files) {
		$result = [];
		$total = 0;
		$finfo = NULL; // finfo_open 的资源.
		if(function_exists('finfo_open')) {
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
		}
		
		// $field 是文件域名称, $info 是每一个文件的信息.
		foreach($files as $field => $info) {
			// form 表单域是 name="photo[]" 的情况(name 是一个数组).
			if(is_array($info['name'])) {
				foreach($info['name'] as $k => $v) {
					$tmp = [];
					$tmp['name'] = $v;
					$tmp['type'] = $info['type'][$k];
					$tmp['tmp_name'] = $info['tmp_name'][$k];
					$tmp['error'] = $info['error'][$k];
					$tmp['size'] = $info['size'][$k];
					$tmp['ext'] = pathinfo($v, PATHINFO_EXTENSION);
					$tmp['field'] = $field; // 文件域名称.
					// FLASH上传的文件获取到的mime类型都为application/octet-stream.
					if(isset($finfo)) {
						$tmp['type'] = finfo_file($finfo, $tmp['tmp_name']);
					}
					
					$result[] = $tmp;
					$total++;
				}
			} else {
				$info['ext'] = pathinfo($info['name'], PATHINFO_EXTENSION);
				$info['field'] = $field; // 文件域名称.
				// FLASH上传的文件获取到的mime类型都为application/octet-stream.
				if(isset($finfo)) {
					$tmp['type'] = finfo_file($finfo, $info['tmp_name']);
				}
				
				$result[] = $info;
				$total++;
			}
		}
		
		unset($files);
		if(isset($finfo)) { // 释放 finfo 资源.
			finfo_close($finfo);
		}
		// 每次允许上传的最大文件数限制已开启时, 只处理已限制的个数.
		if(0 !== $this->_configs['maxFile']) {
			$maxFiles = (integer)$this->_configs['maxFile'];
			$total = $total >= $maxFiles ? $maxFiles : $total;
			$result = array_slice($result, 0, $total); // 若上传的文件个数已超过限制的文件个数, 只取前 $total 个.
		}
		
		return $result;
	}
	
	/**
	 * 检测上传文件.
	 * 
	 * @access private
	 * @param array $file 待检测的文件信息.
	 * @return integer.
	 */
	private function _checkFile(array $file) {
		$result = self::ERR_OK;
		if(!is_uploaded_file($file['tmp_name'])) {
			$result = self::ERR_ILLEGAL_FILE;
		} elseif(0 !== $this->_configs['maxSize'] && $file['size'] > $this->_configs['maxSize']) {
			$result = self::ERR_SPEC_MAX_SIZE;
		} elseif(!empty($this->_configs['mimes']) && !in_array(strtolower($file['type']), $this->_configs['mimes'], TRUE)) {
			// FLASH上传的文件获取到的mime类型都为application/octet-stream.
			$result = self::ERR_TYPE_MIME;
		} elseif(!empty($this->_configs['exts']) && !in_array(strtolower($file['ext']), $this->_configs['exts'], TRUE)) {
			$result = self::ERR_TYPE_EXT;
		} else {
			switch($file['error']) {
				case UPLOAD_ERR_OK : // 0
					$result = self::ERR_OK;
					break;
				case UPLOAD_ERR_INI_SIZE : // 1
					$result = self::ERR_INI_MAX_SIZE;
					break;
				case UPLOAD_ERR_FORM_SIZE : // 2
					$result = self::ERR_FORM_MAX_SIZE;
					break;
				case UPLOAD_ERR_PARTIAL : // 3
					$result = self::ERR_PARTIAL;
					break;
				case UPLOAD_ERR_NO_FILE : // 4
					$result = self::ERR_NO_FILE;
					break;
				case UPLOAD_ERR_NO_TMP_DIR : // 6
					$result = self::ERR_NOT_FOUND_TEMP_DIR;
					break;
				case UPLOAD_ERR_CANT_WRITE : // 7
					$result = self::ERR_CANT_WRITE;
					break;
				default:
					$result = self::ERR_UNKNOWN;
			}
		}
		
		return $result;
	}
	
	/**
	 * 执行上传.
	 * 
	 * @access public
	 * @param array $files 上传的文件, 默认 [], 表示使用 $_FILES.
	 * @return \wuyuan\upload\Upload 出错时抛异常.
	 */
	public function execute(array $files = []) {
		if(empty($files)) {
			$files = $_FILES;
		} else {
			$files = [$files];
		}
		
		if(empty($files)) {
			throw new Exception('没有被上传的文件.');
		}
		
		// 检查上传文件存放目录.
		$rootPath = $this->_configs['rootPath'];
		$savePath = empty($this->_configs['savePath']) ? '' : $this->_configs['savePath'] . '/';
		$subName = FALSE !== $this->_configs['subName'] ? $this->_makeSubName() . '/' : '';
		$destDir = $rootPath . $savePath . $subName; // 目标文件存放目录.
		
		// 创建上传目录和检查是否有写入权限.
		if(!is_dir($destDir) && FALSE === mkdir($destDir, 0777, TRUE)) {
			throw new Exception('无法创建上传目录(' . $destDir . ').');
		}
		if(!is_writable($destDir)) {
			throw new Exception('上传目录(' . $destDir . ')没有写入权限.');
		}
		
		// 将文件数组处理成统一的格式.
		$files = $this->_resolveFiles($files);
		// 返回的结果, 包含: rootPath, savePath, subName, saveName, ext, md5, hash, oriFileName, field 
		$result = [];
		foreach($files as $k => $_file) {
			$res = $this->_checkFile($_file);
			if(self::ERR_OK === $res) {
				// 之前没有考虑到文件已存在且 replace 为 false 的情况 2015-12-4.
				do {
					// 保存的文件名, 配置为 FALSE 将使用原始文件名.
					$saveName = FALSE === $this->_configs['saveName'] ? $_file['name'] : $this->makeSaveName() . '.' . $_file['ext'];
					$destFile = $destDir . $saveName;
					// 目标文件存在是否替换.
					if($this->_configs['replace'] && file_exists($destFile)) {
						unlink($destFile);
					}
					
					clearstatcache();
				} while(file_exists($destFile));
				
				// 将上传的临时文件移到目标目录并重命名.
				if(move_uploaded_file($_file['tmp_name'], $destFile)) {
					$tmp = [];
					$tmp['rootPath'] = $rootPath;
					$tmp['savePath'] = $savePath;
					$tmp['subName'] = $subName;
					$tmp['saveName'] = $saveName;
					$tmp['oriFileName'] = $_file['name'];
					$tmp['field'] = $_file['field'];
					$tmp['type'] = $_file['type'];
					$tmp['ext'] = $_file['ext'];
					$tmp['size'] = $_file['size'];
					if($this->_configs['hash']) {
						$tmp['md5'] = md5_file($destFile);
						$tmp['sha1'] = sha1_file($destFile);
					}
					
					$this->_uploadInfo[] = $tmp; // 记录成功上传的文件信息.
				} else {
					// 记录错误状态.
					$this->_setError($_file['field'], self::ERR_MOVE_DEST);
				}
			} else {
				// 记录错误状态.
				$this->_setError($_file['field'], $res);
			}
		}
		
		return $this;
	}
	
	/**
	 * 记录错误信息.
	 * 
	 * @access private
	 * @param string $field 字段名.
	 * @param integer $status 错误状态.
	 * @return void
	 */
	private function _setError($field, $status) {
		// 字段名是数组的情况.
		if(isset($this->_upload_error[$field])) {
			$error = $this->_upload_error[$field];
			if(is_array($error)) {
				$error[] = $status;
			} else {
				$error = [$error];
				$error[] = $status;
			}
			
			$this->_upload_error[$field] = $error;
			return ;
		}
		
		$this->_upload_error[$field] = $status;
	}
	
	/**
	 * 是否有上传错误.
	 * 
	 * @access public
	 * @return boolean 返回 TRUE 表示有错误, 否则返回 FALSE.
	 */
	public function hasError() {
		return !empty($this->_upload_error);
	}
	
	/**
	 * 获取上传成功的文件信息.
	 * 
	 * @access public
	 * @return array
	 */
	public function getUploadInfo() {
		return $this->_uploadInfo;
	}
	
	/**
	 * 获取上传时的出错信息.
	 *
	 * @access public 
	 * @param string $field 字段名, 默认 NULL, 表示获取全部.
	 * @return array|string 无 $field 错误信息时, 返回 NULL.
	 */
	public function getError($field = NULL) {
		if(NULL === $field) { // 获取全部.
			$result = [];
			foreach($this->_upload_error as $k => $r) {
				$result[$k] = $this->getCodeMsg($r);
			}
			
			return $result;
		}
		
		return $this->getCodeMsg($this->getCode($field));	
	}
	
	/**
	 * 获取上传时的出错状态信息.
	 *
	 * @access public 
	 * @param string $field 字段名, 默认 NULL, 表示获取全部.
	 * @return array|integer 无 $field 错误状态时, 返回 NULL.
	 */
	public function getCode($field = NULL) {
		if(NULL === $field) {
			return $this->_upload_error;
		}
		
		return isset($this->_upload_error[$field]) ? $this->_upload_error[$field] : NULL;
	}
	
	/**
	 * 获取错误状态对应的错误信息.
	 * 
	 * @access public
	 * @param integer|array $code 错误状态.
	 * @return string|array 无效的错误状态时, 返回 NULL.
	 */
	public function getCodeMsg($code) {
		$result = NULL;
		if(NULL === $code) {
			return $result;
		}
		
		if(is_array($code)) {
			$result = [];
			foreach($code as $v) {
				$result[] = isset($this->_statusMsg[$v]) ? $this->_statusMsg[$v] : NULL;
			}
		} elseif(isset($this->_statusMsg[$code])) {
			$result = $this->_statusMsg[$code];
		}
		
		return $result;
	}
	
	/**
	 * 设置错误状态与错误信息对应关系.
	 * 用于自定义错误信息.
	 * 
	 * @access public
	 * @param array $statusMsg
	 * @return \wuyuan\upload\Upload
	 */
	public function setStatusMsg(array $statusMsg) {
		if(!empty($statusMsg) && is_array($statusMsg)) {
			$this->_statusMsg = array_merge($this->_statusMsg, $statusMsg);
		}
		
		return $this;
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
	 * 获取属性值.
	 * 
	 * @access public
	 * @param string $name 属性名.
	 * @return mixed $name 无效, 返回 NULL.
	 */
	public function __get($name) {
		return isset($this->_configs[$name]) ? $this->_configs[$name] : NULL;
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
			$configs = Config::get(__CLASS__);
		}
		
		// 合并配置.
		$this->_configs = array_merge($this->_configs, $configs);
		// 处理 mimes.
		if(!empty($this->_configs['mimes'])) {
			$this->_configs['mimes'] = array_map('strtolower', $this->_configs['mimes']);
		} else {
			$this->_configs['mimes'] = [];
		}
		// 处理 exts.
		if(!empty($this->_configs['exts'])) {
			$this->_configs['exts'] = array_map('strtolower', $this->_configs['exts']);
		} else {
			$this->_configs['exts'] = [];
		}
	}
	
	/**
	 * 析构方法.
	 * 
	 * @access public
	 * @return void
	 */
	public function __destruct() {
		$this->_upload_error = NULL;
		$this->_uploadInfo = NULL;
	}
	
}
