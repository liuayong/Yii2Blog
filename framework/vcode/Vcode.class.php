<?php

namespace wuyuan\vcode;

use wuyuan\base\Config;
use wuyuan\session\Session;
use wuyuan\base\FileException;
use Exception;

/**
 * wuyuan 验证码类.
 * 
 * @property string $salt 加密盐值.
 * @property string $randEn 英文验证码字符.
 * @property integer $expire 验证码有效期(秒), 默认 600.
 * @property boolean $useZw 开启中文验证码, 默认 FALSE.
 * @property string $randZw 中文验证码字段.
 * @property boolean $useNoise 开启噪点, 默认 TRUE.
 * @property boolean $useBg 开启使用背景图片输出验证码字符, 默认 FALSE.
 * @property string $bgPath 背景图片文件路径(jpg 文件), 默认 vcode/imgs.
 * @property array $bgColor 图片背景颜色(未开启使用背景图片时使用).
 * @property string $fontPath 字体文件路径(ttf 文件), 默认 vcode/fonts; 中文对应 cn 目录, 英文对应 en 目录.
 * @property integer $fontSize 字体大小, 默认 20.
 * @property integer $imgWidth 图片宽度, 默认 0.
 * @property integer $imgHeight 图片高度, 默认 0.
 * @property integer $length 字符个数, 默认 4.
 * @property boolean $reset 验证后是否重置, 默认 TRUE; ajax 请求建议设置为 FALSE. 
 * 
 * @author Liuping <xiaofengwz@163.com>
 */
class Vcode {
	
	/**
	 * 可配置项.
	 * 
	 * @var array
	 */
	private $_configs = [
		'salt' => 'wyphp.cn',													// 加密盐值.
		'randEn' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY', 	// 英文验证码字符.
		'expire' => 600, 														// 验证码有效期(秒).
		'useZw' => FALSE, 														// 开启中文验证码.
		'randZw' => '电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗',	// 中文验证码字符.
		'useNoise' => TRUE,														// 开启噪点.
		'useBg' => FALSE,														// 开启使用背景图片输出验证码字符.
		'bgPath' => '',															// 背景图片文件路径(默认为 vcode/imgs), jpg 文件.
		'bgColor' => [243, 251, 254],											// 图片背景颜色(未开启使用背景图片时使用).
		'fontSize' => 20,														// 字体大小.
		'fontPath' => '',														// 字体文件路径(默认为 vcode/fonts), ttf 文件, 中文对应 cn 目录, 英文对应 en 目录.
		'imgWidth' => 0,														// 图片宽度.
		'imgHeight' => 0,														// 图片高度.
		'length' => 4,															// 字符个数.
		'reset' => TRUE,														// 验证码验证后是否重置(ajax 请求建议设置为 FALSE).
	];
	
	/**
	 * 验证码值标识.
	 * 
	 * @var string
	 */
	private $_code = 'code';
	
	/**
	 * 验证码生成时间标识.
	 * 
	 * @var string
	 */
	private $_time = 'time';
	
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
		
		$this->_configs = array_merge($this->_configs, $configs);
		if(empty($this->_configs['bgPath'])) {
			$this->_configs['bgPath'] = __DIR__ . '/imgs/';
		}
		if(empty($this->_configs['fontPath'])) {
			$this->_configs['fontPath'] = __DIR__ . '/fonts/';
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
	 * 保存到 session 的 key.
	 * 
	 * @access private
	 * @param string $id 验证码标识, 默认 NULL.
	 * @return string
	 */
	private function _saveKey($id = NULL) {
		$key = $this->_configs['salt'] . (NULL === $id ? '' : $id);
		return md5($key, FALSE);
	}
	
	/**
	 * 验证验证码.
	 * 
	 * @access public
	 * @param string $code 用户验证码.
	 * @param string $id 验证码标识, 默认 NULL.
	 * @return boolean 验证成功返回 TRUE; 否则返回 FALSE.
	 */
	public function validate($code, $id = NULL) {
		$saveKey = $this->_saveKey($id);
		$sessInfo = Session::get($saveKey);
		
		if(NULL === $sessInfo) {
			return FALSE;
		}
		
		// 验证码已过期.
		if((time() - $sessInfo[$this->_time]) > $this->_configs['expire']) {
			return FALSE;
		}
		
		if(strcasecmp($code, $sessInfo[$this->_code]) !== 0) {
			return FALSE;
		}
		
		// 是否重置.
		if($this->_configs['reset']) {
			Session::remove($saveKey);
		}
		
		return TRUE;
	}
	
	/**
	 * 添加背景.
	 * 从图片文件添加背景.
	 * 
	 * @access private
	 * @param resource $img 图像资源, 传址.
	 * @param integer $imgW 图像宽度.
	 * @param integer $imgH 图片高度.
	 * @return void
	 */
	private function _addBg(& $img, $imgW, $imgH) {
		try {
			$dirIterator = new \DirectoryIterator($this->_configs['bgPath']);
		} catch(\Exception $e) {
			throw new FileException('打开验证码背景图片目录('. $this->_configs['bgPath'] .')时出错.', FileException::READ_FAILD);
		}
		
		$bgPaths = [];
		foreach($dirIterator as $item) {
			if($item->isFile()) {
				$bgPaths[] = $item->getRealPath();
			}
		}
		
		if(empty($bgPaths)) {
			throw new Exception('验证码背景图片目录下没有找到有效的文件.');
		}
		
		// 随机背景图片文件.
		$bgFile = $bgPaths[array_rand($bgPaths)];
		$sizeInfo = getimagesize($bgFile);
		if(FALSE === $sizeInfo) {
			throw new Exception('非法的验证码背景图片文件.');
		}
		
		list($width, $height) = $sizeInfo;
		$resImg = imagecreatefromjpeg($bgFile);
		imagecopyresampled($img, $resImg, 0, 0, 0, 0, $imgW, $imgH, $width, $height);
		imagedestroy($resImg);
	}
	
	/**
	 * 添加噪点.
	 * 
	 * @access private
	 * @param resource $img 图像资源, 传址.
	 * @return void
	 */
	private function _addNoise(& $img) {
		$imgW = imagesx($img);
		$imgH = imagesy($img);
		
		for($i = 0, $step = $imgW + $imgH; $i < $step; ++$i) {
			$rand = mt_rand(50, 200);
			// $noiseColor = imagecolorallocate($img, mt_rand(150,225), mt_rand(150,225), mt_rand(150,225));
			$noiseColor = imagecolorallocate($img, $rand + 5, $rand + 25, $rand + 45);
			imagesetpixel($img, mt_rand(0, $imgW), mt_rand(0, $imgH), $noiseColor);
		}
	}
	
	/**
	 * 生成验证码.
	 * 
	 * @access public
	 * @param string $id 验证码标识, 默认 NULL.
	 * @return void
	 */
	public function generate($id = NULL) {
		$length = $this->_configs['length'];
		$fontSize = $this->_configs['fontSize'];
		// 图片宽度.
		$imgWidth = $this->_configs['imgWidth'];
		if(! (boolean)$imgWidth) {
			$imgWidth = $length * $fontSize * 1.5 + $length * $fontSize / 2;
		}
		// 图片高度.
		$imgHeight = $this->_configs['imgHeight'];
		if(! (boolean)$imgHeight) {
			$imgHeight = $fontSize * 2.5;
		}
		
		// 创建一幅 $imgWidth * $imgHeight 的图像.
		$resImg = imagecreate($imgWidth, $imgHeight);
		// 设置背景色.
		list($red, $green, $blue) = $this->_configs['bgColor'];
		imagecolorallocate($resImg, $red, $green, $blue);
		
		// 获取到全部的 ttf 字体文件路径.
		$ttfPaths = [];
		try {
			$fontDirPath = $this->_configs['fontPath'] . ($this->_configs['useZw'] ? 'cn/' : 'en/');
			$dirIterator = new \DirectoryIterator($fontDirPath);
		} catch(\Exception $e) {
			throw new FileException('打开验证码字体目录('. $fontDirPath .')时出错.', FileException::READ_FAILD);
		}
		
		foreach($dirIterator as $item) {
			if($item->isFile()) {
				$ttfPaths[] = $item->getRealPath();
			}
		}
		
		if(empty($ttfPaths)) {
			throw new Exception('验证码字体目录下没有找到有效的文件.');
		}
		// 随机分配一个字体文件.
		$fontPath = $ttfPaths[array_rand($ttfPaths)];
		// 使用背景图片.
		if($this->_configs['useBg']) {
			$this->_addBg($resImg, $imgWidth, $imgHeight);
		}
		// 设置杂点.
		if($this->_configs['useNoise']) {
			$this->_addNoise($resImg);
		}
		
		// 生成验证码.
		$codes = []; // 验证码.
		// 中文验证码.
		if($this->_configs['useZw']) {
			$strLen = mb_strlen($this->_configs['randZw'], 'utf-8');
			$_offset = 0;
			for($i = 0; $i < $this->_configs['length']; ++$i) {
				$codes[$i] = mb_substr($this->_configs['randZw'], floor(mt_rand(0, ($strLen - 1))), 1, 'utf-8');
				$_offset += mt_rand($fontSize * 1.2, $fontSize * 1.6);
				// 分配字体颜色.
				$fontColor = imagecolorallocate($resImg, mt_rand(1,255), mt_rand(1,255), mt_rand(1,255));
				imagettftext($resImg, $fontSize, mt_rand(-40, 40), $_offset, $fontSize * 1.6, $fontColor, $fontPath, $codes[$i]);
			}
		} else { // 英文验证码.
			$strLen = strlen($this->_configs['randEn']);
			$_tmpCode = substr(str_shuffle($this->_configs['randEn']), 0, $this->_configs['length']);
			$_offset = 0;
			for($i = 0; $i < $this->_configs['length']; ++$i) {
				$codes[$i] = $_tmpCode[$i];
				$_offset += mt_rand($fontSize * 1.2, $fontSize * 1.6);
				// 分配字体颜色.
				$fontColor = imagecolorallocate($resImg, mt_rand(1,255), mt_rand(1,255), mt_rand(1,255));
				imagettftext($resImg, $fontSize, mt_rand(-40, 40), $_offset, $fontSize * 1.6, $fontColor, $fontPath, $codes[$i]);
			}
		}
		
		// 将验证码写入 session.
		$saveKey = $this->_saveKey($id);
		$strCode = implode('', $codes);
		$saveValues = [
			$this->_code => $strCode, 	// 验证码字符串.
			$this->_time => time(),		// 生成时间.
		];
		Session::set($saveKey, $saveValues);
		
		// 将验证图像输出到浏览器.
		header('Content-Type: image/png');
		imagepng($resImg);
		imagedestroy($resImg);
	}

}
