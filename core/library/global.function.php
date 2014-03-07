<?php

if (!defined('IN_XIAOCMS')) exit();

/**
 * 提取关键字
 */
function getKw($data) {
    $data = gethttp('http://keyword.discuz.com/related_kw.html?ics=utf-8&ocs=utf-8&title=' . rawurlencode($data) . '&content=' . rawurlencode($data));
	if ($data) {
	    $parser = xml_parser_create();
		$kws    = array();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, $data, $values, $index);
		xml_parser_free($parser);
		foreach ($values as $valuearray) {
		    $kw = trim($valuearray['value']);
		    if($valuearray['tag'] == 'kw' || $valuearray['tag'] == 'ekw') $kws[]  = $kw;
		}
		return implode(',', $kws);
	}
}
/**
 * 调用远程数据
 */
function gethttp($url) {
    if (substr($url, 0, 7) != 'http://') return file_get_contents($url);
    if (ini_get('allow_url_fopen')) {
	    return file_get_contents($url);
	} elseif (function_exists('curl_init') && function_exists('curl_exec')) {
	    $data = '';
	    $ch   = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
}

/**
 * URL函数
 */
function url($route, $params = null) {
	if (!$route) return false;
	$arr   = explode('/', $route);
	$arr   = array_diff($arr, array(''));
	$count = count($arr);
	$url   = ENTRY_SCRIPT_NAME;
	if (is_dir(CONTROLLER_DIR . $arr[0])) {
		$url .= '?s=' . strtolower($arr[0]);
		if (isset($arr[1]) && $arr[1]) {
		    $url .= '&c=' . strtolower($arr[1]);
			if (isset($arr[2]) && $arr[2] && $arr[2] != 'index') {
			    $url .= '&a=' . strtolower($arr[2]);
			}
		}
	} else {
	    if (isset($arr[0]) && $arr[0]) {
		    $url .= '?c=' . strtolower($arr[0]);
				if (isset($arr[1]) && $arr[1] && $arr[1] != 'index') {
			    $url .= '&a=' . strtolower($arr[1]);
			}
		}
	}
	//参数$params变量的键(key),值(value)的URL组装
	if (!is_null($params) && is_array($params)) {						
		$params_url = array();							
		foreach ($params as $key=>$value) {
			$params_url[] = trim($key) . '=' . trim($value);
		}
		$url .= '&' . implode('&', $params_url);
	}
	$url = str_replace('//', '/', $url);	
	return Controller::get_base_url() . $url;
}

/**
 * 程序执行时间
 */
function runtime() {
	$temptime = explode(' ', SYS_START_TIME);
	$time     = $temptime[1] + $temptime[0];
	$temptime = explode(' ', microtime());
	$now      = $temptime[1] + $temptime[0];
	return number_format($now - $time, 6);
}

	/**
	 * 获取客户端IP
	 * 
	 * @access public
	 * @return string
	 */
function get_user_ip() {
	if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
		$onlineip = getenv('HTTP_CLIENT_IP');
	} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
		$onlineip = getenv('HTTP_X_FORWARDED_FOR');
	} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
		$onlineip = getenv('REMOTE_ADDR');
	} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
		$onlineip = $_SERVER['REMOTE_ADDR'];
	}
	return preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $onlineip) ? $onlineip : '';
}
	
/**
 * 获取系统信息
 */
function get_sysinfo() {
		$sys_info['os']             = PHP_OS;
		$sys_info['zlib']           = function_exists('gzclose');//zlib
		$sys_info['safe_mode']      = (boolean) ini_get('safe_mode');//safe_mode = Off
		$sys_info['safe_mode_gid']  = (boolean) ini_get('safe_mode_gid');//safe_mode_gid = Off
		$sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : '没有设置';
		$sys_info['socket']         = function_exists('fsockopen') ;
		$sys_info['web_server']     = strpos($_SERVER['SERVER_SOFTWARE'], 'PHP')===false ? $_SERVER['SERVER_SOFTWARE'].'PHP/'.phpversion() : $_SERVER['SERVER_SOFTWARE'];
		$sys_info['phpv']           = phpversion();	
		$sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
		return $sys_info;
}

/**
 * 完整文件的路径
 */
function getfile($url) {
	if (empty($url)) return null;
    if (substr($url, 0, 7) == 'http://') return $url;
    if (strpos($url, SITE_PATH) !== false && SITE_PATH != '/') return $url;
    if (substr($url, 0, 1) == '/') $url = substr($url, 1);
    return SITE_PATH . $url;
}

/**
 * 完整的图片路径
 */
function image($url) {
    if (empty($url) || strlen($url) == 1) return SITE_PATH . 'upload/nopic.gif';
    if (substr($url, 0, 7) == 'http://') return $url;
    if (strpos($url, SITE_PATH) !== false && SITE_PATH != '/') return $url;
    if (substr($url, 0, 1) == '/') $url = substr($url, 1);
    return SITE_PATH . $url;
}

/**
 * 图片缩略图地址
 */
function thumb($img, $width=null, $height=null) {
	$config = xiaocms::load_config('config');
    if (empty($img) || strlen($img) == 1) return SITE_PATH . 'upload/nopic.gif';
    if (strpos($img, SITE_PATH) === 0) $img = substr($img, strlen(SITE_PATH));
    if (file_exists($img)) {
        $ext = substr(strrchr(trim($img), '.'), 1);
		if ($width && $height && file_exists($img)) {
		    $thumb = $img . '.thumb.' . $width . 'x' . $height . '.' . $ext;
			if (!file_exists($thumb)) {
		        $image = xiaocms::load_class('image_lib');
		        $image->set_image_size($width, $height)->make_limit_image($img, $thumb);
			}
			return $thumb;
		}
		if ($config['SITE_THUMB_WIDTH'] && $config['SITE_THUMB_HEIGHT']) {
		    $thumb = $img . '.thumb.' . $config['SITE_THUMB_WIDTH'] . 'x' . $config['SITE_THUMB_HEIGHT'] . '.' . $ext;
			if (file_exists($thumb)) return image($thumb);
		}
    }
    return image($img);
}

/**
 * 字符截取 支持UTF8/GBK
 */
function strcut($string, $length, $dot = '') {
	if (strlen($string) <= $length) return $string;
	$string  = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);
	$strcut  = '';
		$n   = $tn = $noc = 0;
		while ($n < strlen($string)) {
			$t = ord($string[$n]);
			if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1; $n++; $noc++;
			} elseif (194 <= $t && $t <= 223) {
				$tn = 2; $n += 2; $noc += 2;
			} elseif (224 <= $t && $t <= 239) {
				$tn = 3; $n += 3; $noc += 2;
			} elseif (240 <= $t && $t <= 247) {
				$tn = 4; $n += 4; $noc += 2;
			} elseif (248 <= $t && $t <= 251) {
				$tn = 5; $n += 5; $noc += 2;
			} elseif ($t == 252 || $t == 253) {
				$tn = 6; $n += 6; $noc += 2;
			} else {
				$n++;
			}
			if($noc >= $length) break;
		}
		if ($noc > $length) $n -= $tn;
		$strcut = substr($string, 0, $n);

	$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
	return $strcut . $dot;
}

/**
 * 正则表达式验证email格式
 */
function is_email($str) {
	if (!$str) return false;
	return preg_match('#[a-z0-9&\-_.]+@[\w\-_]+([\w\-.]+)?\.[\w\-]+#is', $str) ? true : false;
}

/**
 * 清除HTML标记
 */
function clearhtml($str) {
    $str = str_replace(array('&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array(' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $str);
    $str = preg_replace("/\<[a-z]+(.*)\>/iU", "", $str);
    $str = preg_replace("/\<\/[a-z]+\>/iU", "", $str);
    $str = str_replace(array(' ','	', chr(13), chr(10), '&nbsp;'), array('', '', '', '', ''), $str);
    return $str;
}

/**
 * 栏目面包屑导航 当前位置
 * @param $catid  栏目id
 * @param $symbol 栏目间隔符
 * @return NULL|string
 */
function position($catid, $symbol=' > ') {
    $cats    = get_cache('category');
    $catids  = catposids($catid, '', $cats);
    if (empty($catids)) return null;
    if (substr($catids, -1) == ',') $catids = substr($catids, 0, -1);
    $ids     = explode(',', $catids);
    krsort($ids);
    $str     = '';
    foreach ($ids as $cid) {
        $cat = $cats[$cid];
		$str.= "<a href=\"" . $cat['url'] . "\" title=\"". $cat['catname'] . "\">" . $cat['catname'] . "</a>";
        if ($catid != $cid) $str .= $symbol;
    }
    return $str;
}

/**
 * 栏目上级ID集合
 * @param  $catid
 * @param  $catids
 * @return string 返回栏目所有上级ID
 */
function catposids($catid, $catids='', $category) {
    if (empty($catid)) return false;
    $row    = $category[$catid];
    $catids = $catid . ','; 
    if ($row['parentid']) $catids .= catposids($row['parentid'], $catids, $category);
    return $catids;
}

/**
 * 栏目下级ID集合
 * @param  $catid
 * @param  $catids
 * @return string 返回栏目所有下级ID
 */
function _catposids($catid, $catids='', $category) {
    if (empty($catid)) return false;
    $row    = $category[$catid];
    $catids = $catid . ','; 
    if ($row['child'] && $row['arrchildid']) {
		$id = explode(',', $row['arrchildid']);
		foreach ($id as $t) {
			$catids .= _catposids($t, $catids, $category);
		}
	}
    return $catids;
}

/**
 * 当前栏目同级菜单
 * @param  $catid
 */
function getCatNav($catid) {
    $cats   = get_cache('category');
    $cat    = $cats[$catid];
    if (!$cat['child'] && !$cat['parentid']) return array();
    //当前栏目有子菜单时，同级栏目则是所有子菜单；否则为其父级同级菜单
    $catids = $cat['child'] ? $cat['arrchildid'] : $cat['arrparentid'];
    if (empty($catids)) return array();
    $ids    = explode(',', $catids);
    $data   = array();
    foreach ($ids as $cid) {
        $data[] = $cats[$cid];
    }
    return $data;
}

/**
 * 递归查询所有父级栏目信息
 * @param  int $catid  当前栏目ID
 * @return array
 */
function getParentData($catid) {
    $cats  = get_cache('category');
    $cat   = $cats[$catid];
    if ($cat['parentid']) $cat = getParentData($cat['parentid']);
    return $cat;
}

/**
 * 递归查询所有父级栏目名称
 * @param  int    $catid   当前栏目ID
 * @param  string $prefix  分隔符
 * @param  int    $sort    排序方式 1正序，0反序
 * @return string          返回格式：顶级栏目[分隔符]一级栏目[分隔符]二级栏目...[分隔符]当前栏目
 */
function getParentName($catid, $prefix, $sort=1) {
    $cats  = get_cache('category');
	$prefix= empty($prefix) ? ' - ' : $prefix;
	$cids  = catposids($catid, null, $cats);
	$ids   = explode(',', $cids);
    if ($sort) krsort($ids);
	$str   = '';
    foreach ($ids as $cid) {
        if ($cid) $str .= $cats[$cid]['catname'] . $prefix;
    }
	return substr($str, -1) == $prefix ? substr($str, 0, -1) : $str;
}

	
/**
 * 删除目录及文件
 */
function delDir($filename) {
        if (empty($filename)) return false;
        if (is_file($filename) && file_exists($filename)) {
            unlink($filename);
        } else if ($filename != '.' && $filename!='..' && is_dir($filename)) {
            $dirs = scandir($filename);
            foreach ($dirs as $file) {
                if ($file != '.' && $file != '..') $this->delDir($filename . '/' . $file);
            }
            rmdir($filename);
        }
}
	
/**
 * 内容页URL地址
 */

function getUrl($data, $page = 0) {
	$config = xiaocms::load_config('config');
    $cats = get_cache('category');
	$cat  = $cats[$data['catid']];
	$url  = url('index/show', array('id' => $data['id']));
	unset($cats);
	if (isset($config['DIY_URL']) && $config['DIY_URL'] == 1 && $config['SHOW_URL']) {
		$data['dir']	= $cat['catdir'];
		$data['page']	= $page;
		$url = !is_numeric($page) || $page > 1 ? preg_replace('#{([a-z_0-9]+)}#Uei', "\$data[\\1]", $config['SHOW_PAGE_URL']) :preg_replace('#{([a-z_0-9]+)}#Uei', "\$data[\\1]", $config['SHOW_URL']);
		$url = preg_replace('#{([a-z_0-9]+)\((.*)\)}#Uie', "\\1(safe_replace('\\2'))", $url);
		return SITE_PATH . $url;
	}
	if ($page) $url = url('index/show', array('id' => $data['id'], 'page' => $page));
	return $url;
}

/**
 * 栏目URL
 */
function getCaturl($data, $page=0) {
	if (is_numeric($data)) {
		$cats   = get_cache('category');
		$data = $cats[$data];
		unset($cats);
	}
	$catid  = is_numeric($data) ? $data : $data['catid'];
	$config = xiaocms::load_config('config');
	if ($data['typeid'] == 3) return $data['http'];
	$url    = url('index/list', array('catid'=>$data['catid']));
	
	if (isset($config['DIY_URL']) && $config['DIY_URL'] == 1 && $config['LIST_URL']) {
		$data['id']   = $data['catid'];
		$data['dir']  = $data['catdir'];
		$data['page'] = $page;
		$url = !is_numeric($page) || $page > 1 ? preg_replace('#{([a-z_0-9]+)}#Uei', "\$data[\\1]", $config['LIST_PAGE_URL']) : preg_replace('#{([a-z_0-9]+)}#Uei', "\$data[\\1]", $config['LIST_URL']);
		$url = preg_replace('#{([a-z_0-9]+)\((.*)\)}#Uie', "\\1(safe_replace('\\2'))", $url);
		return SITE_PATH . $url;
	}

	if ($page) $url = url('index/list', array('catid' => $data['catid'], 'page' => $page));
	return $url;
}



/**
 * 栏目页SEO信息
 * @param int    $cat
 * @param int    $page
 * @param string $kw
 * @return array
 */
function listSeo($cat, $page=1, $kw=NULL) {
	$config = xiaocms::load_config('config');

    $seo_title = $seo_keywords = $seo_description = '';
    if ($kw) {
	    $seo_title = (empty($cat) ? '搜索 ' . $kw : '搜索 '. $kw ). ' - ' . $config['SITE_NAME'];
		$seo_title = $page > 1 ? '第' . $page . '页' . '-' . $seo_title : $seo_title;
	} else {
	    $seo_title = empty($cat['seo_title']) ? getParentName($cat['catid'], ' - ', 0) : $cat['seo_title']. ' - ' ;
		$seo_title = $page > 1 ? '第' . $page . '页' . ' - ' . $seo_title . $config['SITE_NAME'] : $seo_title . $config['SITE_NAME'];
		$seo_keywords    = empty($cat['seo_keywords'])    ? getParentName($cat['catid'], ',', 0) . ',' . $config['SITE_KEYWORDS'] : $cat['seo_keywords'];
		$seo_description = empty($cat['seo_description']) ? $config['SITE_DESCRIPTION'] : $cat['seo_description'];
	}
    return array('site_title'=>$seo_title, 'site_keywords'=>$seo_keywords, 'site_description'=>$seo_description);
}

/**
 * 内容页SEO信息
 * @param int $data
 * @param int $page
 * @return array
 */
function showSeo($data, $page=1) {
	$config = xiaocms::load_config('config');
    $cats             = get_cache('category');
    $seo_title       = $seo_keywords = $seo_description = '';
    $cat              = $cats[$data['catid']];
    $listseo          = listSeo($cat);
    $seo_title       = $data['title'] . ' - ' . ($page > 1 ? '第' . $page . '页' . ' - ' : '') . $listseo['site_title'];
	
    $seo_keywords    = empty($data['keywords'])    ? $listseo['site_keywords']    : $data['keywords'] . ',' . $listseo['seo_keywords'];
    $seo_description = empty($data['description']) ? $listseo['site_description'] : $data['description'];
    return array('site_title'=>$seo_title, 'site_keywords'=>$seo_keywords, 'site_description'=>$seo_description);
}

/**
 * 格式SQL查询IN(ID序列)
 * @param  $str
 * @param  $glue
 * @return boolean|string
 */
function formatStr($str, $glue=',') {
    $arr = explode($glue, $str);
    if (!is_array($arr)) return false;
    $arr = array_unique($arr);
    $ids = '';
    foreach ($arr as $id) { if ($id) $ids .= ',' . $id; }
    return substr($ids, 1);
}

/**
 * 返回经addslashes处理过的字符串或数组
 * @param $string 需要处理的字符串或数组
 * @return mixed
 */
function new_addslashes($string){
	if(!is_array($string)) return addslashes($string);
	foreach($string as $key => $val) $string[$key] = new_addslashes($val);
	return $string;
}

/**
 * 返回经stripslashes处理过的字符串或数组
 * @param $string 需要处理的字符串或数组
 * @return mixed
 */
function new_stripslashes($string) {
	if(!is_array($string)) return stripslashes($string);
	foreach($string as $key => $val) $string[$key] = new_stripslashes($val);
	return $string;
}

/**
 * 返回经addslashe处理过的字符串或数组
 * @param $obj 需要处理的字符串或数组
 * @return mixed
 */
function new_html_special_chars($string) {
	if(!is_array($string)) return htmlspecialchars($string);
	foreach($string as $key => $val) $string[$key] = new_html_special_chars($val);
	return $string;
}

/**
 * 安全过滤函数
 * @param $string
 * @return string
 */
function safe_replace($string) {
	$string = str_replace('%20','',$string);
	$string = str_replace('%27','',$string);
	$string = str_replace('%2527','',$string);
	$string = str_replace('*','',$string);
	$string = str_replace('"','&quot;',$string);
	$string = str_replace("'",'',$string);
	$string = str_replace('"','',$string);
	$string = str_replace(';','',$string);
	$string = str_replace('<','&lt;',$string);
	$string = str_replace('>','&gt;',$string);
	$string = str_replace("{",'',$string);
	$string = str_replace('}','',$string);
	return $string;
}

/**
* 将字符串转换为数组
* @param	string	$data	字符串
* @return	array	返回数组格式，如果，data为空，则返回空数组
*/
function string2array($data) {
	if ($data == '') return array();
	if (is_array($data)) return $data;
	if (strpos($data, 'array') !== false && strpos($data, 'array') === 0) {
	    @eval("\$array = $data;");
		return $array;
	}
	return unserialize($data);
}

/**
* 将数组转换为字符串
* @param	array	$data		数组
* @param	bool	$isformdata	如果为0，则不使用new_stripslashes处理，可选参数，默认为1
* @return	string	返回字符串，如果，data为空，则返回空
*/
function array2string($data, $isformdata = 1) {
	if($data == '') return '';
	if($isformdata) $data = new_stripslashes($data);
	return serialize($data);
}


/**
 * 格式化输出文件大小
 */
function formatFileSize($fileSize, $round=2) {
    if (empty($fileSize)) return 0;
	$unit = array(' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
	$i    = 0;
	$inv  = 1 / 1024;
	while($fileSize >= 1024 && $i < 8) {
		$fileSize *= $inv;
		++$i;
	}
	$fileSizeTmp = sprintf("%.2f", $fileSize);
	$value = $fileSizeTmp - (int)$fileSizeTmp ? $fileSizeTmp : $fileSize;
	return round($value, $round) . $unit[$i];
}


/**
 * 汉字转为拼音
 */
function word2pinyin($word) {
    if (empty($word)) return '';
    $pin = xiaocms::load_class('pinyin');
	return str_replace('/', '', $pin->output($word));
}


	/**
	 * 写入缓存
	 * 
	 * @param string $key
	 * @param string $value
	 * @return boolean
	 */
	function set_cache($cache_file, $value) {
		if (!$cache_file) return false;
		//缓存文件
		$cache_file = XIAOCMS_PATH . 'data/data' . DIRECTORY_SEPARATOR . $cache_file . '.cache.php';
		//分析缓存内容
		$value = (!is_array($value)) ? serialize(trim($value)) : serialize($value);
		//分析缓存目录
		if (!is_dir(XIAOCMS_PATH . 'data/data' . DIRECTORY_SEPARATOR)) {
			mkdir(XIAOCMS_PATH . 'data/data' . DIRECTORY_SEPARATOR, 0777);
		} else {
			if (!is_writeable(XIAOCMS_PATH . 'data/data' . DIRECTORY_SEPARATOR)) {
				chmod(XIAOCMS_PATH . 'data/data' . DIRECTORY_SEPARATOR, 0777);
			}
		}
		return file_put_contents($cache_file, $value, LOCK_EX) ? true : false;
	}
	
	/**
	 * 获取缓存
	 * 
	 * @param string $key
	 * @return string
	 */
	function get_cache($cache_file) {
		if (!$cache_file) return false;
		//缓存文件
		$cache_file = XIAOCMS_PATH . 'data/data' . DIRECTORY_SEPARATOR . $cache_file . '.cache.php';
		return is_file($cache_file) ? unserialize(file_get_contents($cache_file)) : false;
	}
	
	/**
	 * 删除缓存
	 * 
	 * @param string $key
	 * @return void
	 */
	function delete_cache($cache_file) {
		if (!$cache_file) return true;
		//缓存文件
		$cache_file = XIAOCMS_PATH . 'data/data' . DIRECTORY_SEPARATOR . $cache_file . '.cache.php';
		return is_file($cache_file) ? unlink($cache_file) : true;
	}
	
	
	
	/**
	 * 判断客服端是否是手机客服端
	 */
	function is_mobile() {
		if (isset($_SERVER['HTTP_VIA'])) return stristr($_SERVER['HTTP_VIA'],'wap') ? true : false;
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$client = array(
				'nokia',
				'sony',
				'ericsson',
				'mot',
				'samsung',
				'htc',
				'sgh',
				'lg',
				'sharp',
				'sie-',
				'philips',
				'panasonic',
				'alcatel',
				'lenovo',
				'iphone',
				'ipod',
				'blackberry',
				'meizu',
				'android',
				'netfront',
				'symbian',
				'ucweb',
				'windowsce',
				'palm',
				'operamini',
				'operamobi',
				'openwave',
				'nexusone',
				'cldc',
				'midp',
				'wap',
				'mobile',
			);
			if (preg_match("/(" . implode('|', $client) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) return true;
		}
		if (isset($_SERVER['HTTP_ACCEPT'])) {
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
				return true;
			}
		}
		return false;
	}


	