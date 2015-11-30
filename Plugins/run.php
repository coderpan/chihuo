<?php
//$_BeginTime = microtime(TRUE);
define('PLUGINS', str_replace('\\', '/', dirname(__FILE__) ) );
define('P_APPS', PLUGINS.'/apps');
define('DS', '/');

if(!defined('DEDEINC'))
{
	require PLUGINS.'/../include/common.inc.php';
}

//升级原配置，兼容5.7
if(!function_exists('GetIP'))
{
    update_to_57();
}

//新安装
if(!defined('P_RUN'))
{
    app_start();
}

$_do = str_replace('/', '.', substr(preg_replace('/^\s*'.str_replace('/','\\/',$cfg_cmspath).'/', '', $_SERVER["PHP_SELF"]) , 1, -4) );
$_dh = dir(P_APPS);
while(($_file = $_dh->read()) !== false)
{
	if($_file!="." && $_file!=".." && is_file(P_APPS.DS.$_file.'/index.php'))
	{
		if(is_file(P_APPS.DS.$_file.'/sql.txt'))
		{
			app_install(P_APPS.DS.$_file);
		}
        require P_APPS.DS.$_file.'/index.php';
	}
}

function app_start()
{
    if(!is_dir(P_APPS)) MkdirAll(P_APPS,777);
	$extpath = DEDEINC.'/common.inc.php';
	$fp = @fopen($extpath, 'r');
	$content = @fread($fp, filesize($extpath));
	@fclose($fp);
	$content = $content?$content:'<'."?php\r\n";
    $content = substr($content, -2) == '?>' ? substr($content, 0, -2) : $content;
	$content .= "\r\n\r\n\r\n//Add by qjpemail\r\n";
    $content .= "define('P_RUN', 1);\r\n";
    $content .= "if(!defined('PLUGINS')) \r\n";
    $content .= "@include DEDEROOT.'/Plugins/run.php';\r\n";
	if($fp = @fopen($extpath, 'w'))
	{
		@fwrite($fp, trim($content));
		@fclose($fp);
	}else{
		echo '安装失败！请设置'.$extpath.'可写权限';
		exit();
	}
}

function update_to_57()
{
    $extendfile = DEDEINC.'/extend.func.php';
    $fp = @fopen($extendfile, 'r');
	$run_content = @fread($fp, filesize($extendfile));
	@fclose($fp);
    if(preg_match('/\/\/Add by qjpemail(.*)$/is',$run_content))
    {
        $run_content = preg_replace('/\/\/Add by qjpemail(.*)$/is','',$run_content);
    	if($fp = @fopen($extendfile, 'w'))
    	{
    		@fwrite($fp, $run_content);
    		@fclose($fp);
            app_start();
    	}else{
    		echo '升级失败！请设置'.$extendfile.'可写权限';
    		exit();
    	}
    }
}

function app_install($f)
{
	global $dsql,$cfg_db_language;
	$fp = @fopen($f.'/sql.txt','r');
	$sql = @fread($fp, filesize($f.'/sql.txt'));	
	@fclose($fp);
	if(is_file($f.'/sql.txt.bak'))
	{
		@unlink($f.'/sql.txt.bak');
	}
	@rename($f.'/sql.txt', $f.'/sql.txt.bak');
	$mysql_version = $dsql->GetVersion(true);
	$sql = eregi_replace('ENGINE=MyISAM','TYPE=MyISAM',$sql);
    $sql41tmp = 'ENGINE=MyISAM DEFAULT CHARSET='.$cfg_db_language;
	if($mysql_version >= 4.1)
	{
		$sql = eregi_replace('TYPE=MyISAM',$sql41tmp,$sql);
	}
	$sql = ereg_replace("[\r\n]{1,}","\n",$sql);
	$sqlarr = split(";[ \t]{0,}\n", $sql);
	foreach($sqlarr as $_s)
	{
		if(trim($_s)!='') $dsql->executenonequery($_s);
	}
}

//$_EndTime = microtime(TRUE);
//$_LoadTime = $_EndTime - $_BeginTime;
?>