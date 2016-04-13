<?php

namespace wuyuan\image;

use wuyuan\base\IImageDriver;
use wuyuan\base\Config;

/**
 * wuyuan 图片处理类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Image implements IImageDriver {
	
	/**
	 * 可配置项.
	 * 
	 * @var array $_configs
	 */
	private $_configs = [
		'class' => '\wuyuan\image\dirver\Gd',		// 图像处理驱动类.
	];
	
	/**
	 * 驱动类对象.
	 * 
	 * @var \wuyuan\base\IImageDriver
	 */
	private $_driver = NULL;
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::close()
	 */
	public function close() {
		$this->_driver->close();
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::getInfo()
	 */
	public function getInfo() {
		return $this->_driver->getInfo();
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::output()
	 */
	public function output($type = NULL, $quality = 80, $interlace = TRUE) {
		return $this->_driver->output($type, $quality, $interlace);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::crop()
	 */
	public function crop($w, $h, $x = 0, $y = 0, $width = NULL, $height = NULL) {
		return $this->_driver->crop($w, $h, $x, $y, $width, $height);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::open()
	 */
	public function open($imgPath) {
		$driverClass = $this->_configs['class'];
		$this->_driver = new $driverClass();
		return $this->_driver->open($imgPath);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::save()
	 */
	public function save($destPath, $type = NULL, $quality = 80, $interlace = TRUE) {
		return $this->_driver->save($destPath, $type, $quality, $interlace);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::text()
	 */
	public function text($text, $fontPath, $fontSize, array $color = [0, 0, 0, 0], 
		$pos = self::IMG_WATER_BOTTOM_RIGHT, $angle = 0, $offsetX = 0, $offsetY = 0) {
		return $this->_driver->text($text, $fontPath, $fontSize, $color, $pos, $offsetX, $offsetY, $angle);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::thumb()
	 */
	public function thumb($width, $height, $type = self::IMG_THUMB_SCALE) {
		return $this->_driver->thumb($width, $height, $type);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::watermark()
	 */
	public function watermark($waterImgPath, $pos = self::IMG_WATER_BOTTOM_RIGHT,
		$alpha = 80, $offsetX = 0, $offsetY = 0) {
		return $this->_driver->watermark($waterImgPath, $pos, $alpha, $offsetX, $offsetY);
	}
	
	/**
	 * 设置驱动类类名.
	 * 
	 * @access public
	 * @param string $className 驱动类类名.
	 * @return \wuyuan\base\IImageDriver
	 */
	public function setDriver($className) {
		$this->_configs['class'] = $className;
		return $this;
	}
	
	/**
	 * 构造方法.
	 * 
	 * @access public
	 * @param array $configs 可配置项, 为空使用配置文件中的配置.
	 * @return void
	 */
	public function __construct(array $configs = []) {
		if(empty($configs)) {
			$configs = Config::get(__CLASS__);
		}
		
		$this->_configs = array_merge($this->_configs, $configs);
	}
	
}
