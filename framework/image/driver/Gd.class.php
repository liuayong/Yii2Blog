<?php

namespace wuyuan\image\driver;

use wuyuan\base\IImageDriver;
use Exception;

/**
 * wuyuan 图像 GD 驱动类.
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Gd implements IImageDriver {
	
	/**
	 * 图像资源.
	 * 
	 * @var resource
	 */
	private $_resImg = NULL;
	
	/**
	 * GIF 图片处理类对象.
	 * 
	 * @var \wuyuan\image\driver\Gif
	 */
	private $_gif = NULL;
	
	/**
	 * 图像信息.
	 * 
	 * @var array
	 */
	private $_imgInfo = [];
	
	/**
	 * Gif 下一张图像.
	 * 
	 * @access private
	 * @return boolean|resource 出错返回 FALSE.
	 */
	private function gifNext() {
		ob_start();
		ob_implicit_flush(0);
		imagegif($this->_resImg);
		$img = ob_get_clean();
		
		$this->_gif->frame($img);
		$next = $this->_gif->next();
		
		if($next){
			$this->_resImg = imagecreatefromstring($next);
			return $next;
		}
		
		$this->_resImg = imagecreatefromstring($this->_gif->frame());
		return FALSE;
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::crop()
	 */
	public function crop($w, $h, $x = 0, $y = 0, $width = NULL, $height = NULL) {
		if(NULL === $this->_resImg) {
			throw new Exception('无效的图像资源.');
		}
		
		// 设置保存的宽度和高度.
		$width = NULL === $width ? $w : $width;
		$height = NULL === $height ? $h : $height;
		do {
			$img = imagecreatetruecolor($width, $height); // 创建一真彩色图像.
			$color = imagecolorallocate($img, 255, 255, 255); // 设置颜色.
			imagefill($img, 0, 0, $color);
			
			// 裁剪.
			imagecopyresampled($img, $this->_resImg, 0, 0, $x, $y, $width, $height, $w, $h);
			imagedestroy($this->_resImg); // 销毁原图.
			
			$this->_resImg = $img; // 设置新图.
		} while (NULL !== $this->_gif && $this->gifNext());
		
		$this->_imgInfo['width'] = $width;
		$this->_imgInfo['height'] = $height;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::open()
	 */
	public function open($imgPath) {
		$size = getimagesize($imgPath);
		if(FALSE === $size) {
			throw new Exception('非法的图片文件.');
		}
		
		$this->_imgInfo['width'] = $size[0]; // 宽度.
		$this->_imgInfo['height'] = $size[1]; // 高度.
		$this->_imgInfo['type'] = $size[2]; // 类型.
		$this->_imgInfo['mime'] = $size['mime']; // MIME 类型.
		$this->_imgInfo['extension'] = image_type_to_extension($size[2], FALSE); // 扩展名不包含点.
		
		if(NULL !== $this->_resImg) {
			imagedestroy($this->_resImg); // 销毁已存在的图像资源.
			$this->_resImg = NULL;
		}
		
		if(IMAGETYPE_GIF === $this->_imgInfo['type']) { // GIF 图像.
			$this->_gif = new Gif($imgPath);
			$this->_resImg = imagecreatefromstring($this->_gif->frame());
		} else {
			$func = 'imagecreatefrom' . $this->_imgInfo['extension'];
			$this->_resImg = $func($imgPath);
		}
		
		if(FALSE === $this->_resImg || NULL === $this->_resImg) {
			$this->_resImg = NULL;
			throw new Exception('非法的图片文件.');
		}
		
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::save()
	 */
	public function save($destPath, $type = NULL, $quality = 80, $interlace = TRUE) {
		if(NULL === $this->_resImg) {
			throw new Exception('无效的图像资源.');
		}
		
		$type = NULL === $type ? $this->_imgInfo['type'] : $type;
		$flag = FALSE; // 标识是否成功写入文件.
		if(IMAGETYPE_JPEG === $type) {
			imageinterlace($this->_resImg, ($interlace ? 1 : 0));
			$flag = imagejpeg($this->_resImg, $destPath, $quality);
		} elseif(IMAGETYPE_GIF === $type && NULL !== $this->_gif) {
			$flag = $this->_gif->save($destPath);
		} else {
			$ext = image_type_to_extension($type, FALSE);
			$func = 'image' . ($ext ? $ext : 'png');
			$flag = $func($this->_resImg, $destPath);
		}
		
		if(FALSE === $flag) {
			throw new Exception('保存图片时出错.');
		}
		
		return TRUE;
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::output()
	 */
	public function output($type = NULL, $quality = 80, $interlace = TRUE) {
		if(NULL === $this->_resImg) {
			throw new Exception('无效的图像资源.');
		}
		
		$type = NULL === $type ? $this->_imgInfo['type'] : $type;
		if(IMAGETYPE_JPEG === $type) {
			imageinterlace($this->_resImg, ($interlace ? 1 : 0));
			header('Content-type: ' . image_type_to_mime_type($type));
			return imagejpeg($this->_resImg, NULL, $quality);
		} elseif(IMAGETYPE_GIF === $type && NULL !== $this->_gif) {
			return $this->_gif->output();
		}
		
		$ext = image_type_to_extension($type, FALSE);
		$mime = image_type_to_mime_type($type);
		$func = 'image' . ($ext ? $ext : 'png');
		header('Content-type: ' . ($mime ? $mime : 'image/png'));
		if(FALSE === $func($this->_resImg)) {
			throw new Exception('输出图片时出错.');
		}
		
		return TRUE;
	}
	
	/**
	 * 获得水印位置.
	 * 
	 * @access private
	 * @param integer|array $pos 水印位置, 数组表示自定义位置(包含 2 个元素, 第 1 个是 x 坐标, 
	 * 第 2 个是 y 坐标), 默认为右下角.
	 * @param array $srcSize 原始图片尺寸, 包含 width, height.
	 * @param array $size 目标尺寸, 包含 width, height.
	 * @param integer $offsetX 偏移 x 坐标值, 默认 0.
	 * @param integer $offsetY 偏移 y 坐标值, 默认 0.
	 * @return array 返回计算后的坐标, 包含 x, y.
	 */
	private function _getPos($pos = self::IMG_WATER_BOTTOM_RIGHT, array $srcSize, 
		array $size, $offsetX = 0, $offsetY = 0) {
		$result = ['x' => 0, 'y' => 0]; // 返回的结果.
		
		$x = $y = 0;
		if(is_array($pos)) {
			list($x, $y) = $pos;
		} else {
			switch($pos) {
				case self::IMG_WATER_TOP_LEFT: // 左上角水印.
					$x = $y = 0;
					break;
				case self::IMG_WATER_TOP_CENTER: // 上居中水印.
					$x = ($srcSize['width'] - $size['width']) / 2;
					$y = 0;
					break;
				case self::IMG_WATER_TOP_RIGHT: // 右上角水印.
					$x = $srcSize['width'] - $size['width'];
					$y = 0;
					break;
				case self::IMG_WATER_LEFT: // 左居中水印.
					$x = 0;
					$y = ($srcSize['height'] - $size['height']) / 2;
					break;
				case self::IMG_WATER_CENTER: // 居中水印.
					$x = ($srcSize['width'] - $size['width']) / 2;
					$y = ($srcSize['height'] - $size['height']) / 2;
					break;
				case self::IMG_WATER_RIGHT: // 右居中水印.
					$x = $srcSize['width'] - $size['width'];
					$y = ($srcSize['height'] - $size['height']) / 2;
					break;
				case self::IMG_WATER_BOTTOM_LEFT: // 左下角水印.
					$x = 0;
					$y = $srcSize['height'] - $size['height'];
					break;
				case self::IMG_WATER_BOTTOM_CENTER: // 下居中水印.
					$x = ($srcSize['width'] - $size['width']) / 2;
					$y = $srcSize['height'] - $size['height'];
					break;
				case self::IMG_WATER_BOTTOM_RIGHT: // 右下角水印.
					$x = $srcSize['width'] - $size['width'];
					$y = $srcSize['height'] - $size['height'];
					break;
			}
		}
		
		// 设置偏移量.
		$x += $offsetX;
		$y += $offsetY;
		$result['x'] = $x;
		$result['y'] = $y;
		return $result;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::text()
	 */
	public function text($text, $fontPath, $fontSize, array $color = [0, 0, 0, 0], 
		$pos = self::IMG_WATER_BOTTOM_RIGHT, $angle = 0, $offsetX = 0, $offsetY = 0) {
		if(NULL === $this->_resImg) {
			throw new Exception('无效的图像资源.');
		}
		if(!is_file($fontPath)) {
			throw new Exception('无效的字体文件.');
		}
		
		// 文字信息. 0: 左下角 X; 1: 左下角 Y; 2: 右下角 X; 3: 右下角 Y; 4: 右上角 X; 5: 右上角 Y; 6: 左上角 X; 7: 左上角 Y.
		$fontInfo = imagettfbbox($fontSize, $angle, $fontPath, $text);
		// 计算文字的宽度和高度.
		$minX = min($fontInfo[0], $fontInfo[2], $fontInfo[4], $fontInfo[6]);
		$maxX = max($fontInfo[0], $fontInfo[2], $fontInfo[4], $fontInfo[6]);
		$minY = min($fontInfo[1], $fontInfo[3], $fontInfo[5], $fontInfo[7]);
		$maxY = max($fontInfo[1], $fontInfo[3], $fontInfo[5], $fontInfo[7]);
		// 计算文字的初始位置和大小.
		$x = abs($minX);
		$y = abs($minY);
		$width = $maxX - $minX;
		$height = $maxY - $minY;
		
		// 计算文字的 x, y 坐标值.
		$srcSize = ['width' => $this->_imgInfo['width'], 'height' => $this->_imgInfo['height']];
		$newSize = ['width' => $width, 'height' => $height];
		$arrPos = $this->_getPos($pos, $srcSize, $newSize, $offsetX, $offsetY);
		$x += $arrPos['x'];
		$y += $arrPos['y'];
		unset($arrPos);
		// 设置文字颜色.
		$red = $green = $blue = $alpha = 0;
		list($red, $green, $blue) = $color;
		if(isset($color[3])) {
			$alpha = (integer)$color[3];
		}
		
		// 写入文字.
		do{
			$textColor = imagecolorallocatealpha($this->_resImg, $red, $green, $blue, $alpha);
			imagettftext($this->_resImg, $fontSize, $angle, $x, $y, $textColor, $fontPath, $text);
		} while(NULL !== $this->_gif && $this->gifNext());
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::thumb()
	 */
	public function thumb($width, $height, $type = self::IMG_THUMB_SCALE) {
		if(NULL === $this->_resImg) {
			throw new Exception('无效的图像资源.');
		}
		
		// 原图的宽度和高度.
		$srcW = $this->_imgInfo['width'];
		$srcH = $this->_imgInfo['height'];
		$x = $y = 0;	// 初始化 x, y 坐标值.
		
		switch($type) {
			case self::IMG_THUMB_SCALE: // 等比缩略.
				// 原图宽度和高度都小于待缩略的宽高, 程序直接认为是处理成功了.
				if($srcW < $width && $srcH < $height) {
					return $this;
				}
				// 计算最小缩放比例.
				$scale = min($width / $srcW, $height / $srcH);
				// 设置缩略图的 x, y 坐标和缩略的宽高.
				$x = $y = 0;
				$width = $srcW * $scale;
				$height = $srcH * $scale;
				break;
			case self::IMG_THUMB_TOP_LEFT: // 左上角裁剪.
				// 计算最大缩放比例.
				$scale = max($width / $srcW, $height / $srcH);
				// 设置缩略图的 x, y 坐标和缩略的宽高.
				$x = $y = 0;
				$srcW = $width / $scale;
				$srcH = $height / $scale;
				break;
			case self::IMG_THUMB_BOTTOM_RIGHT: // 右下角裁剪.
				// 计算最大缩放比例.
				$scale = max($width / $srcW, $height / $srcH);
				// 设置缩略图的 x, y 坐标和缩略的宽高.
				$srcW = $width / $scale;
				$srcH = $height / $scale;
				$x = $this->_imgInfo['width'] - $srcW;
				$y = $this->_imgInfo['height'] - $srcH;
				break;
			case self::IMG_THUMB_CENTER: // 居中裁剪.
				// 计算最大缩放比例.
				$scale = max($width / $srcW, $height / $srcH);
				// 设置缩略图的 x, y 坐标和缩略的宽高.
				$srcW = $width / $scale;
				$srcH = $height / $scale;
				$x = ($this->_imgInfo['width'] - $srcW) / 2;
				$y = ($this->_imgInfo['height'] - $srcH) / 2;
				break;
			case self::IMG_THUMB_FIXED: // 固定尺寸.
				$x = $y = 0;
				break;
			case self::IMG_THUMB_FILLED: // 填充缩略图.
				// 计算最小缩放比例.
				if($srcW < $width && $srcH < $height){
					$scale = 1;
				} else {
					$scale = min($width / $srcW, $height / $srcH);
				}
				
				// 设置缩略图的 x, y 坐标和缩略的宽高.
				$newW = $srcW * $scale;
				$newH = $srcH * $scale;
				$posX = ($width - $newW) / 2;
				$posY = ($height - $newH) / 2;

				do{
					// 创建新图像.
					$img = imagecreatetruecolor($width, $height);
					// 调整默认颜色.
					$color = imagecolorallocate($img, 255, 255, 255);
					imagefill($img, 0, 0, $color);
				
					// 裁剪.
					imagecopyresampled($img, $this->_resImg, $posX, $posY, $x, $y, $newW, $newH, $srcW, $srcH);
					
					imagedestroy($this->_resImg); // 释放原图资源.
					$this->_resImg = $img; // 设置原图为新图.
				} while(!empty($this->_gif) && $this->gifNext());
				
				$this->info['width']  = $width;
				$this->info['height'] = $height;
				return $this;
				break;
		}
		
		return $this->crop($srcW, $srcH, $x, $y, $width, $height); // 裁剪.
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::watermark()
	 */
	public function watermark($waterImgPath, $pos = self::IMG_WATER_BOTTOM_RIGHT, 
		$alpha = 80, $offsetX = 0, $offsetY = 0) {
		if(NULL === $this->_resImg) {
			throw new Exception('无效的图像资源.');
		}
		
		$size = getimagesize($waterImgPath); // 水印图片信息.
		if(FALSE === $size) {
			throw new Exception('无效的水印图片.');
		}
		
		// 计算水印位置.
		$srcSize = ['width' => $this->_imgInfo['width'], 'height' => $this->_imgInfo['height']];
		$newSize = ['width' => $size[0], 'height' => $size[1]];
		$arrPos = $this->_getPos($pos, $srcSize, $newSize, $offsetX, $offsetY);
		$x = $arrPos['x'];
		$y = $arrPos['y'];
		$imgType = $size[2];
		unset($size, $arrPos);
		
		// 创建水印图像资源.
		$func = 'imagecreatefrom' . image_type_to_extension($imgType, FALSE);
		$resWater = $func($waterImgPath);
		// 设置水印图片的混色模式.
		imagealphablending($resWater, TRUE);
		
		do{
			//添加水印.
			$srcImg = imagecreatetruecolor($newSize['width'], $newSize['height']);
			// 分配一个颜色.
			$color = imagecolorallocate($srcImg, 255, 255, 255);
			imagefill($srcImg, 0, 0, $color);
		
			imagecopy($srcImg, $this->_resImg, 0, 0, $x, $y, $newSize['width'], $newSize['height']);
			imagecopy($srcImg, $resWater, 0, 0, 0, 0, $newSize['width'], $newSize['height']);
			imagecopymerge($this->_resImg, $srcImg, $x, $y, 0, 0, $newSize['width'], $newSize['height'], $alpha);
			
			imagedestroy($srcImg); // 释放图像资源.
		} while(NULL !== $this->_gif && $this->_gif->next());
		
		//销毁水印资源
		imagedestroy($resWater);
		return $this;
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::getInfo()
	 */
	public function getInfo() {
		return $this->_imgInfo;
	}
	
	/**
	 * {@inheritDoc}
	 * 
	 * @see \wuyuan\base\IImageDriver::close()
	 */
	public function close() {
		$this->_gif = $this->_resImg = NULL;
		$this->_imgInfo = [];
	}
	
}
