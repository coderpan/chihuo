<?php

/**
 * @version    $Id cjx.php 1001 2011-8-14 qjp $
 * @copyright  Copyright (c) 2010-2011,qjp
 * @license    This is NOT a freeware, use is subject to license terms
 * @link       http://www.qjp.name
 */

require(dirname(__FILE__)."/config.php");

// start
$fuck = DEDEINC.'/inc_sql_query.php';
if(!empty($dopost) && $dopost=='delfuck'){
    @unlink($fuck) or die('无法删除，请检查权限');
    showmsg("删除成功",1,2);
    exit;
}
if(is_file($fuck)){
    $msg = "采集侠发现您程序内安装有其它不安全模块&插件 [<a href='http://bbs.dedecms.com/400797.html' target='_blank'>查看公告</a>]<br>";
    $msg .= "为了您系统的安全，需要清除才能继续<br>";
    $msg .= "是否清除？ <a href='?dopost=delfuck'>自动清除</a> <a href='index_body.php'>我要保留</a> <a href='http://service.dedecms.com/tools/safecheck/index.php' target='_blank'>官方检测</a>";
    showmsg($msg,"index_body.php",2);
    exit;
}
// end start

if(!defined('PLUGINS')){
    header("Location: ".$cfg_cmsurl."/Plugins/run.php");
    exit;
}

require DEDEADMIN.'/apps/CaiJiXia/cjx.class.php';

$allow_version = array('V55','V56','V57');
if(!in_array(substr($cfg_version,0,3),$allow_version))
{
	Showmsg('很抱歉，本插件只支持dedecms V5.5 V5.6 V5.7 版本',1,2);
	exit;
}

$action = 'ac_'.($ac = empty($ac)?'index':$ac);
$instance = new admin_cjx;
if (method_exists ( $instance, $action ) === TRUE)
	$instance->$action();
else
	Showmsg('没有此操作',1,2);

?>