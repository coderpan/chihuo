<?php
@ob_start();
@set_time_limit(3600);
require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_Keyword');
if(empty($dojob)) $dojob = '';
if($dojob=='')
{
	include DedeInclude("templets/article_description_main.htm");
	exit();
}
else
{
	if(empty($startdd))
	{
		$startdd = 0;
	}
	if(empty($pagesize))
	{
		$pagesize = 100;
	}
	if(empty($totalnum))
	{
		$totalnum = 0;
	}
	if(empty($sid))
	{
		$sid = 0;
	}
	if(empty($eid))
	{
		$eid = 0;
	}
	if(empty($dojob))
	{
		$dojob = 'des';
	}
	$table = ereg_replace("[^0-9a-zA-Z_#@]","",$table);
	$field = ereg_replace("[^0-9a-zA-Z_\[\]]","",$field);
	$channel = intval($channel);
	if($dsize>250)
	{
		$dsize = 250;
	}

	$tjnum = 0;

	//获取自动摘要
	if($dojob=='des')
	{
		if(empty($totalnum))
		{
			$addquery  = "";
			if($sid!=0)
			{
				$addquery  = " And id>='$sid' ";
			}
			if($eid!=0)
			{
				$addquery  = " And id<='$eid' ";
			}
			$tjQuery = "Select count(*) as dd From #@__archives where channel='{$channel}' $addquery";
			$row = $dsql->GetOne($tjQuery);
			$totalnum = $row['dd'];
		}
		if($totalnum > 0)
		{
			$addquery  = "";
			if($sid!=0)
			{
				$addquery  = " And #@__archives.id>='$sid' ";
			}
			if($eid!=0)
			{
				$addquery  = " And #@__archives.id<='$eid' ";
			}
			$fquery = "Select #@__archives.id,#@__archives.title,#@__archives.description,{$table}.{$field}
          From #@__archives left join {$table} on {$table}.aid=#@__archives.id
          where #@__archives.channel='{$channel}' $addquery limit $startdd,$pagesize ; ";
			$dsql->SetQuery($fquery);
			$dsql->Execute();
			while($row=$dsql->GetArray())
			{
				$body = $row[$field];
				$description = $row['description'];
				if(strlen($description)>10 || $description=='-')
				{
					continue;
				}
				$bodytext = preg_replace("/#p#|#e#|副标题|分页标题/isU","",Html2Text($body));
				if(strlen($bodytext) < $msize)
				{
					continue;
				}
				$des = trim(addslashes(cn_substr($bodytext,$dsize)));
				if(strlen($des)<3)
				{
					$des = "-";
				}
				$dsql->ExecuteNoneQuery("Update #@__archives set description='{$des}' where id='{$row['id']}';");
			}

			//返回进度信息
			$startdd = $startdd + $pagesize;
			if($totalnum > $startdd)
			{
				$tjlen = ceil( ($startdd/$totalnum) * 100 );
			}else
			{
				$tjlen=100;
				echo "完成所有任务！";
				exit();
			}
			$dvlen = $tjlen * 2;
			$tjsta = "<div style='width:200;height:15;border:1px solid #898989;text-align:left'><div style='width:$dvlen;height:15;background-color:#829D83'></div></div>";
			$tjsta .= "<br/>完成处理文档总数的：$tjlen %，继续执行任务...";
			$nurl = "article_description_main.php?totalnum=$totalnum&startdd={$startdd}&pagesize=$pagesize&table={$table}&field={$field}&dsize={$dsize}&msize={$msize}&channel={$channel}&dojob={$dojob}";
			ShowMsg($tjsta,$nurl,0,500);
			exit();
		}else
		{
			echo "完成所有任务！";
			exit();
		}
	}//获取自动摘要代码结束
	
	
	
	//获取首图为缩略图
	if($dojob=='spic')
	{
	    require_once(DEDEADMIN."/inc/inc_archives_functions.php");
		
		if(empty($totalnum))
		{
			$addquery  = "";
			$addquery2  = "";
			if($sid!=0)
			{
				$addquery  = " and id>=$sid ";
			}
			if($eid!=0)
			{
				$addquery2  = " and id<=$eid ";
			}
			$tjQuery = "Select count(*) as dd From #@__archives where channel='{$channel}' $addquery $addquery2";
			$row = $dsql->GetOne($tjQuery);
			$totalnum = $row['dd'];
		}
		if($totalnum > 0)
		{
			$addquery  = "";
			$addquery2  = "";
			if($sid!=0)
			{
				$addquery  = " and #@__archives.id>=$sid ";
			}
			if($eid!=0)
			{
				$addquery2  = " and #@__archives.id<=$eid ";
			}
			$fquery = "Select #@__archives.id,#@__archives.litpic,{$table}.{$field} From #@__archives left join {$table} on {$table}.aid=#@__archives.id where #@__archives.channel='{$channel}' $addquery $addquery2 limit $startdd,$pagesize; ";
			$dsql->SetQuery($fquery);
			$dsql->Execute();
			while($row=$dsql->GetArray())
			{
				//$tid=$row['id'];
				$body = $row[$field];
				$litpic = GetDDImgFromBody($body);
				$dsql->ExecuteNoneQuery("Update #@__archives set litpic='$litpic' where id='{$row['id']}';");
			}
			//返回进度信息
			$startdd = $startdd + $pagesize;
			if($totalnum > $startdd)
			{
				$tjlen = ceil( ($startdd/$totalnum) * 100 );
			}else
			{
				$tjlen=100;
				echo "完成所有任务！";
				exit();
			}
			$dvlen = $tjlen * 2;
			$tjsta = "<div style='width:200;height:15;border:1px solid #898989;text-align:left'><div style='width:$dvlen;height:15;background-color:#829D83'></div></div>";
			$tjsta .= "<br/>$tid...完成处理文档总数的：$tjlen %，继续执行任务...";
			$nurl = "article_description_main.php?totalnum=$totalnum&startdd={$startdd}&sid=$sid&eid=$eid&pagesize=$pagesize&table={$table}&field={$field}&dsize={$dsize}&msize={$msize}&channel={$channel}&dojob={$dojob}";
			ShowMsg($tjsta,$nurl,0,500);
			exit();
		}else
		{
			echo "没有满足条件的操作记录！";
			exit();
		}
	}//首图缩略图结束
	

	//更新自动分页
	if($dojob=='page')
	{
		require_once(DEDEADMIN."/inc/inc_archives_functions.php");

		//统计记录总数
		if($totalnum==0)
		{
			$addquery  = " where aid>0 ";
			if($sid!=0)
			{
				$addquery  = " where aid>='$sid' ";
			}
			if($eid!=0)
			{
				$addquery  = " where aid<='$eid' ";
			}
			$row = $dsql->GetOne("Select count(*) as dd From $table $addquery");
			$totalnum = $row['dd'];
		}

		//获取记录，并分析
		if($totalnum > $startdd+$pagesize)
		{
			$limitSql = " limit $startdd,$pagesize";
		}
		else if(($totalnum-$startdd)>0)
		{
			$limitSql = " limit $startdd,".($totalnum - $startdd);
		}
		else
		{
			$limitSql = "";
		}
		$tjnum = $startdd;
		if($limitSql!="")
		{
			$addquery  = " where aid>0 ";
			if($sid!=0)
			{
				$addquery  = " where aid>='$sid' ";
			}
			if($eid!=0)
			{
				$addquery  = " where aid<='$eid' ";
			}
			$fquery = "Select aid,$field From $table $addquery $limitSql ;";
			$dsql->SetQuery($fquery);
			$dsql->Execute();
			while($row=$dsql->GetArray())
			{
				$tjnum++;
				$body = $row[$field];
				$aid = $row['aid'];
				if(strlen($body) < $msize)
				{
					continue;
				}
				if(!preg_match("/#p#/iU",$body))
				{
					$body = SpLongBody($body,$cfg_arcautosp_size*1024,"#p#分页标题#e#");
					$body = addslashes($body);
					$dsql->ExecuteNoneQuery("Update $table set $field='$body' where aid='$aid' ; ");
				}
			}
		}//end if limit

		//返回进度提示
		if($totalnum>0)
		{
			$tjlen = ceil( ($tjnum/$totalnum) * 100 );
		}
		else
		{
			$tjlen=100;
		}

		$dvlen = $tjlen * 2;

		$tjsta = "<div style='width:200;height:15;border:1px solid #898989;text-align:left'><div style='width:$dvlen;height:15;background-color:#829D83'></div></div>";
		$tjsta .= "<br/>完成处理文档总数的：$tjlen %，继续执行任务...";

		if($tjnum < $totalnum)
		{
			$nurl = "article_description_main.php?totalnum=$totalnum&startdd=".($startdd+$pagesize)."&pagesize=$pagesize&table={$table}&field={$field}&dsize={$dsize}&msize={$msize}&channel={$channel}&dojob={$dojob}";
			ShowMsg($tjsta,$nurl,0,500);
			exit();
		}else
		{
			echo "完成所有任务！";
			exit();
		}
	}//更新自动分页处理代码结束
	
	
	
	//批量更改成动态浏览
	if($dojob=='cismake')
	{
		$addquery  = "";
		if($sid!=0)
		{
			$addquery  = " and id>='$sid' ";
		}
		if($eid!=0)
		{
			$addquery  = " and id<='$eid' ";
		}
		$dsql->ExecuteNoneQuery("Update dede_archives set ismake=-1 where channel='{$channel}' $addquery");
		echo "完成批量更改任务！";
	}//批量更改成动态浏览代码结束
}
?>