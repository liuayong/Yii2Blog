<?php

namespace wuyuan\base;

/**
 * 数据库驱动接口类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
Interface IDbDriver {
	
	/**
	 * 连接到数据库.
	 *
	 * @access public
	 * @return boolean 连接成功返回 TRUE, 出错时抛异常.
	 */
	public function connect();
	
	/**
	 * 开始事务.
	 *
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	*/
	public function startTransaction();
	
	/**
	 * 回滚事务.
	 *
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	*/
	public function rollback();
	
	/**
	 * 提交事务.
	 *
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	*/
	public function commit();
	
	/**
	 * 执行有数据返回 SQL 语句.
	 * 使用 fetchAll 获取返回的数据.
	 * 
	 * @access public
	 * @param string $strSql SQL 语句.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	*/
	public function query($strSql);
	
	/**
	 * 执行无数据返回 SQL 语句.
	 *
	 * @access public
	 * @param string $strSql SQL 语句.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	*/
	public function execute($strSql);
	
	/**
	 * 转义特殊字符.
	 *
	 * @access public
	 * @param string $str 待转义的字符串.
	 * @return string 返回转义后的字符串, 出错时抛异常.
	*/
	public function quote($str);
	
	/**
	 * 切换数据库.
	 *
	 * @access public
	 * @param string $dbName 数据库名称, 默认 NULL, 表示当前数据库.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	*/
	public function selectDb($dbName = NULL);
	
	/**
	 * 获取全部的数据.
	 *
	 * @access public
	 * @return array
	*/
	public function fetchAll();
	
	/**
	 * 获取当前数据库的全部表名.
	 *
	 * @access public
	 * @param string $dbName 数据库名称, 默认 NULL, 表示当前数据库.
	 * @return array 出错时抛异常.
	*/
	public function fetchTables($dbName = NULL);
	
	/**
	 * 获取表的全部字段.
	 *
	 * @access public
	 * @param string $tableName 表名.
	 * @return array 出错时抛异常.
	*/
	public function fetchFields($tableName);
	
	/**
	 * 释放结果集对象.
	 *
	 * @access public
	 * @return void
	*/
	public function freeResult();
	
	/**
	 * 检查一个连接是否有效.
	 * 
	 * @access public
	 * @return boolean 返回 TRUE 表示有效, 否则返回 FALSE.
	 */
	public function ping();
	
	/**
	 * 关闭释放资源.
	 *
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	*/
	public function close();
	
}

/**
 * 图像处理驱动接口类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
interface IImageDriver {
	
	/**
	 * 等比缩放缩略图.
	 *
	 * @var integer
	 */
	const IMG_THUMB_SCALE = 1;
	
	/**
	 * 缩放填充缩略图.
	 *
	 * @var integer
	 */
	const IMG_THUMB_FILLED = 2;
	
	/**
	 * 居中裁剪缩略图.
	 *
	 * @var integer
	 */
	const IMG_THUMB_CENTER = 3;
	
	/**
	 * 左上角裁剪缩略图.
	 *
	 * @var integer
	 */
	const IMG_THUMB_TOP_LEFT = 4;
	
	/**
	 * 右下角裁剪缩略图.
	 *
	 * @var integer
	 */
	const IMG_THUMB_BOTTOM_RIGHT = 5;
	
	/**
	 * 固定尺寸缩略图.
	 *
	 * @var integer
	 */
	const IMG_THUMB_FIXED = 6;
	
	/**
	 * 左上角水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_TOP_LEFT = 1;
	
	/**
	 * 上居中水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_TOP_CENTER = 2;
	
	/**
	 * 右上角水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_TOP_RIGHT = 3;
	
	/**
	 * 左居中水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_LEFT = 4;
	
	/**
	 * 居中水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_CENTER = 5;
	
	/**
	 * 右居中水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_RIGHT = 6;
	
	/**
	 * 左下角水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_BOTTOM_LEFT = 7;
	
	/**
	 * 下居中水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_BOTTOM_CENTER = 8;
	
	/**
	 * 右下角水印.
	 *
	 * @var integer
	 */
	const IMG_WATER_BOTTOM_RIGHT = 9;
	
	/**
	 * 打开一个图片资源.
	 *
	 * @access public
	 * @param string $imgPath 图片路径.
	 * @return \wuyuan\base\IImageDriver 出错时抛异常.
	 */
	public function open($imgPath);
	
	/**
	 * 生成水印图.
	 *
	 * @access public
	 * @param string $waterImgPath 水印图片文件路径.
	 * @param integer|array $pos 水印位置, 数组表示自定义位置(包含 2 个元素, 第 1 个是 x 坐标, 第 2 个是 y 坐标);
	 * 默认为右下角.
	 * @param integer $alpha 透明度, 默认 80.
	 * @param integer $offsetX 偏移 x 坐标值, 默认 0.
	 * @param integer $offsetY 偏移 y 坐标值, 默认 0.
	 * @return \wuyuan\base\IImageDriver 出错时抛异常.
	 */
	public function watermark($waterImgPath, $pos = self::IMG_WATER_BOTTOM_RIGHT, 
		$alpha = 80, $offsetX = 0, $offsetY = 0);
	
	/**
	 * 生成缩略图.
	 *
	 * @access public
	 * @param integer $width 最大宽度.
	 * @param integer $height 最大高度.
	 * @param integer $type 缩略类型, 默认等比缩略图.
	 * @return \wuyuan\base\IImageDriver 出错时抛异常.
	 */
	public function thumb($width, $height, $type = self::IMG_THUMB_SCALE);
	
	/**
	 * 裁剪图片.
	 *
	 * @access public
	 * @param integer $w 裁剪的宽度.
	 * @param integer $h 裁剪的高度.
	 * @param integer $x x 坐标值.
	 * @param integer $y y 坐标值
	 * @param integer $width 保存的宽度, 默认 NULL, 表示跟裁剪的宽度一致.
	 * @param integer $height 保存的高度, 默认 NULL, 表示跟裁剪的高度一致.
	 * @return \wuyuan\base\IImageDriver 出错时抛异常.
	 */
	public function crop($w, $h, $x = 0, $y = 0, $width = NULL, $height = NULL);
	
	/**
	 * 添加文字.
	 *
	 * @access public
	 * @param string $text 待写入的文字.
	 * @param string $fontPath ttf 字体文件路径.
	 * @param integer $fontSize 字体大小.
	 * @param array $color 文字颜色, 默认 [0, 0, 0, 0], 代表红, 绿, 蓝, 透明度.
	 * @param integer|array $pos 文字位置, 数组表示自定义位置(包含 2 个元素, 第 1 个是 x 坐标, 
	 * 第 2 个是 y 坐标); 默认 右下角.
	 * @param integer $angle 倾斜角度, 默认 0.
	 * @param integer $offsetX 偏移 x 坐标值, 默认 0.
	 * @param integer $offsetY 偏移 y 坐标值, 默认 0.
	 * @return \wuyuan\base\IImageDriver 出错时抛异常.
	 */
	public function text($text, $fontPath, $fontSize, array $color = [0, 0, 0, 0], 
		$pos = self::IMG_WATER_BOTTOM_RIGHT, $angle = 0, $offsetX = 0, $offsetY = 0);
	
	/**
	 * 保存图片.
	 *
	 * @access public
	 * @param string $destPath 目标图片文件路径.
	 * @param integer $type 图片类型, IMAGETYPE_xxx 常量.
	 * @param integer $quality 图像质量, 默认 80.
	 * @param boolean $interlace JPEG 图片开启隔行扫描, 默认 TRUE.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	public function save($destPath, $type = NULL, $quality = 80, $interlace = TRUE);
	
	/**
	 * 输出图片.
	 *
	 * @access public
	 * @param integer $type 图片类型, IMAGETYPE_xxx 常量.
	 * @param integer $quality 图像质量, 默认 80.
	 * @param boolean $interlace JPEG 图片开启隔行扫描, 默认 TRUE.
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	public function output($type = NULL, $quality = 80, $interlace = TRUE);
	
	/**
	 * 获取图像信息.
	 *
	 * @access public
	 * @return array 无相关信息时, 返回空数组; 否则返回包含 width, height, type, mime, extension 的一维数组.
	 */
	public function getInfo();
	
	/**
	 * 释放资源.
	 *
	 * @access public
	 * @return void
	 */
	public function close();
	
}

/**
 * 缓存处理驱动接口类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
interface ICacheDriver {
	
	/**
	 * 获取缓存驱动类的单例对象.
	 * 
	 * @access public
	 * @param array $configs 配置项, 默认 [].
	 * @return \wuyuan\base\ICache
	 */
	public static function getInstance(array $configs = []);
	
	/**
	 * 设置配置项.
	 * 
	 * @access public
	 * @param array $configs 配置项, 具体配置选项参见各驱动类.
	 * @return \wuyuan\base\ICache
	 */
	public function setConfig(array $configs);
	
	/**
	 * 连接服务器.
	 * 
	 * @access public
	 * @return boolean 连接成功返回 TRUE, 出错时抛异常.
	 */
	public function connect();
	
	/**
	 * 读取缓存值.
	 * 
	 * @access public
	 * @param string $name 缓存名称.
	 * @return mixed 出错时抛异常.
	 */
	public function get($name);
	
	/**
	 * 设置缓存值.
	 * 
	 * @access public
	 * @param string $name 缓存名称.
	 * @param mixed $value 缓存值.
	 * @param integer $expire 过期时间(秒), 0: 表示永不过期; 默认 NULL, 表示使用配置文件中的配置.
	 * @return \wuyuan\base\ICache, 出错时抛异常.
	 */
	public function set($name, $value, $expire = NULL);
	
	/**
	 * 删除缓存.
	 * 
	 * @access public
	 * @param string $name 缓存名称.
	 * @return \wuyuan\base\ICache, 出错时抛异常.
	 */
	public function remove($name);
	
	/**
	 * 清空全部缓存.
	 * 
	 * @access public
	 * @return \wuyuan\base\ICache, 出错时抛异常.
	 */
	public function flush();
	
	/**
	 * 关闭.
	 * 
	 * @access public
	 * @return boolean 成功返回 TRUE, 出错时抛异常.
	 */
	public function close();
	
}
