<?php

/**
 * 可配置项.
 * 具体在应用中, 以下的配置项需要放在 common 项中, 表示是公共配置; 若开启分组模式时, 用分组名作为子配置项, 
 * 在解析 URL 时, 会将分组的配置覆盖原有 common 同名的配置项. 
 */

return [
	// ---------------------------- 系统配置 -------------------------------------------------
	'enable_log' => FALSE,								// 开启日志.
	'default_charset' => 'utf-8',						// 默认输出字符集.
	'default_timezone' => 'Asia/Shanghai',				// 默认时区.
	'gzip_output' => FALSE,								// 开启 gzip 压缩输出.
	'model_suffix' => 'Model',							// 模型类后缀名.
	
	// ---------------------------- 参数变量名 -----------------------------------------------
	'var_pathinfo_name' => 'PATH_INFO',					// 解析 PATH_INFO 参数名.
	'var_is_ajax' => 'isAjax',							// 未识别 ajax 请求时识别 ajax 参数名.
	'var_page' => 'p',									// 分页参数名.
	'var_page_size' => 'rows',							// 分页大小参数名.
	
	// ---------------------------- URL 配置 -------------------------------------------------
	'wuyuan\base\Url' => [
		'urlMode' => 2,									// URL 模式, 1: 普通, 2: pathinfo.
		'rewrite' => FALSE,								// 开启 URL 重写.
		'pathinfoSplit' => '/',							// pathinfo 分隔符.
		'enablePathLetterSplit' => TRUE, 				// 开启 pathinfo 大写字符请求解析和生成 URL 的转换字符.
		'pathLetterSplit' => '-',						// pathinfo 大写字符请求解析和生成 URL 的转换字符.
		'urlSuffix' => '.html',							// URL 后缀名.
		'controllerNs' => 'app\controller',				// 控制器命名空间.
		'controllerSuffix' => 'Controller',				// 控制器后缀名.
		'actionSuffix' => 'Action',						// 动作后缀名.
		'groupMode' => FALSE,							// 开启分组模式.
		'groupList' => [],								// 分组列表.
		'defaultGroup' => '',							// 默认分组.
		'defaultController' => 'Index',					// 默认控制器.
		'defaultAction' => 'index',						// 默认动作.
		'varGroup' => 'g',								// 分组参数名.
		'varController' => 'c',							// 控制器参数名.
		'varAction' => 'a',								// 动作参数名.
		'enableRequestRule' => FALSE,					// 开启请求的路由规则.
		'requestRules' => [],							// 请求路由规则列表.
		'enableCreateRule' => FALSE,					// 开启创建 URL 路由规则.
		'createRules' => [],							// 创建 URL 规则列表.
	],
	
	// ---------------------------- session 配置 ---------------------------------------------
	'wuyuan\session\Session' => [
		'name' => 'WYSESSID',							// session 名字, 即 session.name.
		'prefix' => '',									// session 前缀.
		'auto_start' => TRUE,							// 自动开启 session.
		'max_lifetime' => 1440,							// session 有效期(秒), 即 session.gc_maxlifetime.
		'cookie_expire' => 0,							// 下面几项有关 cookie 跟 cookie 配置一样.
		'cookie_path' => '/',
		'cookie_domain' => '',
		'cookie_secure' => FALSE,
		'cookie_httpOnly' => TRUE,
	],
	
	// ---------------------------- cookie 配置 ----------------------------------------------
	'wuyuan\cookie\Cookie' => [
		'expire' => 0,									// cookie 有效期(秒), 0: 表示永久.
		'path' => '/',									// cookie 有效路径.
		'domain' => '',									// cookie 有效域名.
		'secure' => FALSE,								// 仅 HTTPS 有效.
		'httpOnly' => TRUE,								// 仅 HTTP 协议有效.
	],
	
	// ---------------------------- 默认模板引擎配置 -----------------------------------------
	'wuyuan\base\View' => [
		'leftDs' => '{',								// 模板左边定界符.
		'rightDs' => '}',								// 模板右边定界符.
		'tplSuffix' => '.html',							// 模板文件后缀名.
		'style' => 'default',							// 默认样式(主题)目录名.
		'tplDir' => WY_APP_VIEW_DIR,					// 模板文件存放目录路径.
		'compileDir' => WY_APP_RUNTIME_DIR . 'view_c/',	// 模板编译文件存放目录路径.
	],
	
	// ---------------------------- 数据库配置 -----------------------------------------------
	'wuyuan\db\Connection' => [
		'class' => '\wuyuan\db\driver\Mysqli',			// 驱动类名.
		'rwMode' => FALSE,								// 读写分离模式.
		'master' => [],									// 主数据库连接配置项.
		'slave' => [],									// 从数据库连接配置项.
		'other' => [],									// 其它数据库连接配置项.
		'masterStopUseSlave' => FALSE,					// 主数据库全部不可用时, 用从数据库代替.
		'slaveStopUseMaster' => FALSE,					// 从数据库全部不可用时, 用主数据库代替.
		'tablePrefix' => ''								// 数据表前缀, 主要用于模型自动生成表名.
	],
	
	// ---------------------------- 日志配置 -------------------------------------------------
	'wuyuan\base\Log' => [
		'savePath' => WY_APP_RUNTIME_DIR . 'logs/',		// 日志文件存放目录路径(以 / 结尾).
		'saveName' => date('Y-m-d'),					// 日志文件名称.
		'extension' => '.log',							// 日志文件后缀名.
		'maxSize' => 4096,								// 日志文件单个文件大小(字节).
	],
	
	// ---------------------------- 上传配置 -------------------------------------------------
	'wuyuan\upload\Upload' => [
		'rootPath' => '',								// 上传文件保存根目录.
		'savePath' => '',								// 上传文件分类目录名.
		'maxSize' => 0,									// 单个文件的大小(字节数), 0: 无限制.
		'maxFile' => 0,									// 每次最多允许上传文件个数, 0: 无限制.
		'mimes' => [],									// 允许上传的 mime 类型列表, 空: 无限制.
		'exts' => [],									// 允许上传的扩展名列表, 空: 无限制.
		'subName' => ['date', 'Y-m-d'],					// 子目录名[callable, 参数|[参数...]]; FALSE 表示不使用子目录.
		'saveName' => ['uniqid', ['uploads_', TRUE]],	// 文件名生成规则[callable, 参数|[参数...]]; FALSE 表示使用原文件名.
		'replace' => FALSE,								// 文件重名覆盖.
		'hash' => TRUE,									// 生成文件 hash 编码(hash 和 md5).
	],
	
	// ---------------------------- Page 分页配置 --------------------------------------------
	'wuyuan\page\Page' => [
		'header' => '<span class="header">共 %TOTAL_ROWS% 条记录</span>',
		'footer' => '<span class="footer">第 %PAGE% 页 / 共 %PAGE_COUNT% 页</span>',
		'first' => '|<',
		'last' => '>|',
		'prev' => '<<',
		'next' => '>>',
		// 数字链接第 1 个, fixed 固定显示.
		'numFirst' => ['text' => '1...', 'fixed' => FALSE],
		// 数字链接最后 1 个, fixed 同上.
		'numLast' => ['text' => '...%PAGE_COUNT%', 'fixed' => FALSE],
		// 显示设置每页显示数量的下拉选择框.
		'showPageSize' => FALSE,
		'pageSizeTpl' => '<span class="page-size-list">每页显示: %LIST_PAGE_SIZE% 条</span>',
		'pageSizeList' => [10, 20, 30, 40, 50],
		// 选择每页显示数量时, 是否保留在当前页, 否则都从第 1 页显示.
		'keepPage' => TRUE,
		// 显示所有页码的下拉选择框.
		'showPageList' => FALSE,
		'pageListTpl' => '<span class="page-list">跳转到: %LIST_PAGE% 页</span>',
		// 显示输入页码跳转到指定页.
		'showGoPage' => TRUE,
		// 输入页码跳转到指定页模板.
		'goPageTpl' => '<script>function goPage(page) {var url = window.location.href;var reg = /%VAR_PAGE%=\d+/;if(!reg.test(url)){url += \'?%VAR_PAGE%=%PAGE%\';} if(!/\d+/.test(page)){alert(\'请输入正确的页码!\');return false;} url = url.replace(reg, \'%VAR_PAGE%\' + \'=\' + page);window.location=url;}
</script><label class="label-go-page">跳转到: <input class="input-page" value="%PAGE%" onkeyup="var e = window.event || event;if(e.keyCode == 13) {goPage(this.value);}" /><input class="btn-go-page" type="button" value="GO" onclick="goPage(this.previousSibling.value)" /></label>',
		// 分页显示模板.
		'theme' => '%HEADER% %FIRST% %PREV% %NUM_LINK% %NEXT% %LAST% %FOOTER% %PAGE_LIST% %PAGE_SIZE_LIST% %GO_PAGE%'
	],
	
	// ---------------------------- Vcode 验证码配置 -----------------------------------------
	'wuyuan\vcode\Vcode' => [
		// 加密盐值.
		'salt' => 'wyphp.cn',
		// 英文验证码字符.
		'randEn' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
		// 验证码有效期(秒).
		'expire' => 600,
		// 开启中文验证码.
		'useZw' => FALSE,
		// 中文验证码字符.
		'randZw' => '电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗',
		// 开启噪点.
		'useNoise' => TRUE,
		// 开启使用背景图片输出验证码字符.
		'useBg' => FALSE,
		// 背景图片文件路径(默认为 vcode/imgs), jpg 文件.
		'bgPath' => WY_DIR . 'vcode/imgs/',
		// 图片背景颜色(未开启使用背景图片时使用).
		'bgColor' => [243, 251, 254],
		// 字体大小.
		'fontSize' => 20,
		// 字体文件路径(默认为 vcode/fonts), ttf 文件, 中文对应 cn 目录, 英文对应 en 目录.
		'fontPath' => WY_DIR . 'vcode/fonts/',
		// 图片宽度.
		'imgWidth' => 0,
		// 图片高度.
		'imgHeight' => 0,
		// 字符个数.
		'length' => 4,
		// 验证码验证后是否重置(ajax 请求建议设置为 FALSE).
		'reset' => TRUE,
		
	],
	
	// ---------------------------- Image 图像处理配置 ---------------------------------------
	'wuyuan\image\Image' => [
		'class' => '\wuyuan\image\driver\Gd',			// 图像处理驱动类名.
	],
	
	// ---------------------------- 缓存配置 -------------------------------------------------
	'wuyuan\cache\Cache' => [
		'class' => '\wuyuan\cache\driver\File',				// 缓存处理驱动类名.
		// 驱动类配置项, 具体配置项参考各驱动类.
		'opts' => [
			'prefix' => 'cache_',							// 缓存文件名前缀.
			'cacheDir' => WY_APP_RUNTIME_DIR . 'cache/',	// 缓存文件存放目录.
			'expire' => 0,									// 缓存时间(秒), 0: 表示永久.
			'lock' => [TRUE, 2, 100000],					// 锁配置, 0: 启用锁; 1: 尝试次数; 2: 失败时重新获取锁的间隔时间(微秒).
		],
	],
];
