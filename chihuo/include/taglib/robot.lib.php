<?php

/**
 *
 * @name           caijixia for dedecms
 * @version        V2.5 2011/09/01 00:00 qjpemai $
 * @copyright      Copyright (c) 2011，caijixia.com.
 * @license        This is NOT a freeware, use is subject to license terms
 */
 
if(!defined('DEDEINC')) exit('Request Error!');
	
function lib_robot(&$ctag,&$refObj)
{
	global $cfg_cmsurl;
	$copyright = $ctag->GetAtt('copyright');
	if(preg_match('/qjpemail/',$copyright))
	{	
		$jsvar = '<script type="text/javascript" src="'.$cfg_cmsurl.'/Plugins/apps/CaiJiXia/cjx.js"></script>';
	}else
	{
		$jsvar = '<script type="text/javascript">alert("\u667A\u80FD\u91C7\u96C6\u6A21\u5757\u5F02\u5E38\uFF0C\u8BF7\u68C0\u67E5\u8C03\u7528\u4EE3\u7801\u662F\u5426\u6B63\u786E\uFF0C\u6216\u662F\u5426\u4F7F\u7528\u4E86\u975E\u6CD5\u7248\u672C");</script>';
	}
	return $jsvar;
}
?>