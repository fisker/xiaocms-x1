<?php
/**
 * 安装页面
 */
class InstallController  {

	public function __construct() {
		if (!is_writable(DATA_DIR)) {
		    exit('系统缓存目录（/data/）没有读写权限，安装程序无法进行！');
		}
		if (file_exists(DATA_DIR . 'install.lock')) {
		    exit('您已经安装过 XiaoCMS ,如果需要重新安装，请删除  ./data/install.lock 文件');
        }
	}
	
	public function indexAction() {
	    $step = trim($_REQUEST['step']) ? trim($_REQUEST['step']) : 1;
		switch($step) {
			case '1'://安装许可协议
		        include $this->install_tpl('1');
			break;
			case '2'://环境检测 填写配置信息
		        if (PHP_VERSION < '5.2.0') {
                    $error = 'PHP版本低于5.2，无法进行安装！';
  		        }
  		        if (!function_exists("session_start")) {
                    $error = '系统不支持session，无法进行安装！';
  		        }
  		        if (!extension_loaded('mysql')) {
                    $error = '不支持mysql，无法进行安装！';
  		        }
  		        if (!function_exists('imagejpeg')) {
                    $error = '不支持GD库，无法进行安装！';
  		        }
  		        if (!function_exists('imagegif')) {
                    $error = '不支持GD库，无法进行安装！';
  		        }
  		        if (!function_exists('imagepng')) {
                    $error = '不支持GD库，无法进行安装！';
  		        }
  		        if (!function_exists('json_decode')) {
                    $error = '不支持JSON，无法进行安装！';
  		        }
  		        if (!is_writable(DATA_DIR)) {
                    $error = 'data 目录没有写入权限，无法进行安装！';
  		        }
  		        if (!is_writable(XIAOCMS_PATH . 'upload')) {
                    $error = 'upload目录没有写入权限，无法进行安装！';
  		        }
			
                include $this->install_tpl('2');
			break;
			case '3'://安装
			
                function dexit($msg) {
                	echo '<script>alert("' . $msg . '");window.history.back();</script>';
					exit;
                }
                $tdb_host = $_POST['db_host'];
                $tdb_user = $_POST['db_user'];
                $tdb_pass = $_POST['db_pass'];
                $tdb_name = $_POST['db_name'];
                $ttb_pre  = $_POST['tb_pre'];
                $import   = $_POST['import'];
                $username = $_POST['username'];
                $password = $_POST['password'];

                if (!preg_match('/^[a-z0-9]+$/i', $username) || strlen($password) < 5) dexit('请填写正确的后台帐号');
                if (strlen($password) < 5) dexit('后台密码最少5位');
                if (!@mysql_connect($tdb_host, $tdb_user, $tdb_pass)) dexit('无法连接到数据库，请检查数据库配置信息');
                $tdb_name or dexit('连接正常\n\n不过您没有填写数据库名');
                if (!mysql_select_db($tdb_name)) {
                    if (!mysql_query("CREATE DATABASE " . $tdb_name)) dexit('无法创建数据库\n\n请通过其他方式建立数据库');
                }
                mysql_query('SET NAMES utf8');

                //保存数据库配置文件
                $content  = "<?php" . PHP_EOL . "if (!defined('IN_XIAOCMS')) exit();" . PHP_EOL . PHP_EOL .  "return array(" . PHP_EOL . PHP_EOL;
                $content .= "	'host'     => '" . $tdb_host . "', " . PHP_EOL;
                $content .= "	'username' => '" . $tdb_user . "', " . PHP_EOL;
                $content .= "	'password' => '" . $tdb_pass . "', " . PHP_EOL;
                $content .= "	'dbname'   => '" . $tdb_name . "', " . PHP_EOL;
                $content .= "	'prefix'   => '" . $ttb_pre . "', " . PHP_EOL;
                $content .= "	'charset'  => 'utf8', " . PHP_EOL;
                $content .= "	'port'     => '', " . PHP_EOL;
                $content .= PHP_EOL . ");";
                if (!file_put_contents(DATA_DIR .  'config' . DIRECTORY_SEPARATOR . 'database.ini.php', $content)) dexit('数据库配置文件保存失败，请检查文件权限！');

     			//保存数据库配置文件
                $admincontent  = "<?php" . PHP_EOL . "if (!defined('IN_XIAOCMS')) exit();" . PHP_EOL . PHP_EOL . "return array(" . PHP_EOL . PHP_EOL;
                $admincontent .= "	'ADMIN_NAME'     => '" . $username . "', " . PHP_EOL;
                $admincontent .= "	'ADMIN_PASS' => '" . md5(md5($password)) . "', " . PHP_EOL;
                $admincontent .= PHP_EOL . ");";
                if (!file_put_contents(DATA_DIR .  'config' . DIRECTORY_SEPARATOR . 'admin.ini.php', $admincontent)) dexit('数据库配置文件保存失败，请检查文件权限！');

     			//导入表结构
                $sql      = file_get_contents(DATA_DIR . 'install/xiaocms.sql');
                $sql      = str_replace('xiao_', $ttb_pre, $sql);
                $this->installsql($sql);
                //导入演示数据
     			if ($import) {
			    $sql  = file_get_contents(DATA_DIR . 'install/xiaocms_data.sql');
			    $sql      = str_replace('xiao_', $ttb_pre, $sql);
			    $this->installsql($sql);
				}

                include $this->install_tpl('3');
			break;
			case 'db_test'://测试连接
			    $tdb_host = $_POST['tdb_host'];
				$tdb_user = $_POST['tdb_user'];
				$tdb_pass = $_POST['tdb_pass'];
				$tdb_name = $_POST['tdb_name'];
				$ttb_pre  = $_POST['ttb_pre'];
				$ttb_test = $_POST['ttb_test'];
				if (!mysql_connect($tdb_host, $tdb_user, $tdb_pass)) exit("<script>alert('无法连接到数据库，请检查数据库配置信息');</script>");
				if (!mysql_select_db($tdb_name)) {
					if (!mysql_query("CREATE DATABASE " . $tdb_name)) exit("<script>alert('无法创建数据库\n\n请通过其他方式建立数据库');</script>");
					mysql_select_db($tdb_name);
				}
				$tables   = array();
				$query    = mysql_list_tables($tdb_name);
				while ($r = mysql_fetch_row($query)) {
					$tables[] = $r[0];
				}
				if (is_array($tables) && in_array($ttb_pre . 'content', $tables)) {
					if($ttb_test) {
						exit('<script>alert("注意：系统检测到已有相同前缀的数据表\n\n如果继续安装将会清空现有数据\n\n如果需要保留现有数据，请修改数据表前缀");</script>');
					} else {
						exit('<script>alert("注意：系统检测到已有相同前缀的数据表\n\n如果继续安装将会清空现有数据\n\n如果需要保留现有数据，请修改数据表前缀");</script>');
					}			
				}
				if ($ttb_test) exit('<script>alert("数据库连接正常");</script>');
			break;
		}
	} 
	
	//执行sql语句
	private function installsql($sql) {
		$sql = str_replace("\r", "\n", $sql);
		$ret  = array(); 
		$num  = 0;
		$queriesarray = explode(";\n", trim($sql));

		foreach($queriesarray as $query){
			$queries = explode('\n', trim($query)); 
			foreach($queries as $query) {
				$ret[$num] .= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query; 
			}
			$num++; 
		}
		unset($sql); 
		foreach($ret as $query) {  
			if(trim($query) != '')
			{ 
				mysql_query($query) or die(exit('数据导入出错<hr>' . mysql_error() . '<br>SQL语句：<br>' . $query));
			} 
		}
		file_put_contents(DATA_DIR . 'install.lock', time());

	}
    /**
     * 加载安装模板
     * @param string $file 文件名
     * @param string $m 模型名
     */
    protected function install_tpl($file) {
        return  XIAOCMS_PATH.'data/install'.DIRECTORY_SEPARATOR.$file.'.tpl.php';
    }
}