<?php

/**
 *
 * @name           caijixia for dedecms
 * @version        V2.5 2011/09/01 00:00 qjpemai $
 * @copyright      Copyright (c) 2011，caijixia.com.
 * @license        This is NOT a freeware, use is subject to license terms
 */

if(!defined('DEDEINC'))
{
	exit("Request Error!");
}

@set_time_limit(30);
@ignore_user_abort(true);

/**
 * CaiJiXia for DeDecms
 * @version   V2.5 2011/09/01 00:00 qjpemai $
 * @copyright Copyright (c) 2011，caijixia.com.
 * @license   This is NOT a freeware, use is subject to license terms
 *
 * @param     NULL
 * @return    NULL
 */

class cjxdbclass{
    
    var $table,
        $where = '',
        $order = '',
        $fields = '*',
        $limit = '',
        $sql,
        $db;

    function __construct($table){
        global $dsql;
        $this->table = '#@__'.$table;
        $this->db = $dsql;
    }

    function cjxdbclass($table){
        $this->__construct($table);
    }

    function find(){
        $this->sql = 'SELECT '.$this->fields.' FROM `'.$this->table.'`'.$this->where.$this->order;
        $rs = $this->db->GetOne($this->sql);
        return $rs;
    }

    function count(){
        $this->fields = "count({$this->fields}) as num";
        $rs = $this->find();
        return $rs['num'];
    }

    function select(){
        $this->sql = 'SELECT '.$this->fields.' FROM `'.$this->table.'`'.$this->where.$this->order.$this->limit;
        $datalist = array();
        $this->db->Execute('me',$this->sql);
        while($rs = $this->db->GetArray()){
            $datalist[] = $rs;
        }
        return $datalist;
    }

    function update($data){
        if(empty($this->where)) return false;
        $fields = $this->getfields();
        $udata = array();
        foreach($data as $k => $v){
            if(isset($fields[$k])){
                $udata[] = "`$k`='".addslashes($v)."'";
            }
        }
        $udata = join(',',$udata);
        $this->sql = 'UPDATE `'.$this->table.'` SET '.$udata.$this->where;
        return $this->db->ExecuteNoneQuery($this->sql);
    }

    function delete(){
        $this->sql = 'DELETE FROM `'.$this->table.'`'.$this->where;
        return $this->db->ExecuteNoneQuery($this->sql);
    }

    function insert($data,$returnid=false){
        $fields = $this->getfields();
        $field = $value = '';
        foreach($data as $k => $v){
            if(isset($fields[$k])){
                $field .= "`$k`,";
                $value .= "'".addslashes($v)."',";
            }
        }
        $this->sql = 'INSERT INTO'.' `'.$this->table.'` ('.trim($field,',').') VALUES ('.trim($value,',').')';
        $rs = $this->db->ExecuteNoneQuery($this->sql);
        return $returnid?$this->db->GetLastID():$rs;
    }

    function where($where){
        $this->where = ' WHERE '.(is_array($where)?$this->arr2sql($where):$where);
        return $this;
    }

    function order($order){
        $this->order = ' ORDER BY '.$order;
        return $this;
    }

    function fields($fields){
        $this->fields = $fields;
        return $this;
    }

    function limit($limit){
        $this->limit = ' LIMIT '.$limit;
        return $this;
    }

    function arr2sql($where){
        $sql = '';
        foreach ($where as $key=>$val){
            $val = addslashes($val);
            $sql .= $sql ? " AND `$key` = '$val' " : " `$key` = '$val'";
        }
        return $sql;
    }

    function getfields(){
		//SHOW COLUMNS //debug
        $this->db->Execute('me' , "show create table `{$this->table}`");
		$r = $this->db->GetArray('me');
		$text = $r['Create Table'];
		$l = explode('PRIMARY KEY',$text);
		preg_match_all('/`[a-z0-9_]*?`[^`]*?,[\r\n]/i',$l[0],$mt);
		$fields = array();
		foreach($mt[0] as $r){
			preg_match('/`([a-z0-9]*)`/i',$r,$rs);
			$fields[$rs[1]] = $r;
		}
		/*while($r = $this->db->GetArray('me')){
            $fields[$r['Field']] = $r['Type'];
        }*/
        return $fields;
    }
}

function cjxdb($table){
    return new cjxdbclass($table);
}

class CaiJiXia
{
	var $cr;
	var $tl;
	var $rl;
	var $pc;
	var $db;
	var $aid;
	var $rs;
	var $nw;
	var $mx;
    var $dtp;
	var $html;
	var $vo = array();
    var $us = array();

	function __construct()
	{
		$this->tl = DEDEDATA.'/time.lock.inc';
		$this->rl = P_APPS.'/CaiJiXia/cjx_base_data.rule';
		$this->cjxxml();
	}

    function CaiJiXia()
    {
        $this->__construct();
    }
	
	function avc(){
		$id = $this->gv("id");
		if(empty($id)){
			$t = cjxdb("kwavc")->order("rand()")->find();
			$id = $t['id'];
		}else{
			$t = cjxdb("kwavc")->where("id='$id'")->find();
		}
		if(empty($t)) return false;
		$avcdata = cjxdb("kwavcdata")->where("tid='$id' AND finish=0")->order("rand()")->find();
		if($avcdata){
			cjxdb("kwavcdata")->where("id='{$avcdata['id']}'")->update(array("finish"=>1));
			$t['siteurl'] = $avcdata['url'];
		}
		$html = $this->hd($t['siteurl']);
		$dhtml = new DedeHtml2();
		$dhtml->SetSource($html,$t['siteurl'],'link');
		$purl = @parse_url($t['siteurl']);
		$host = strtolower(@$purl['host']);
		$coun[0] = $coun[1] = 0;
		foreach($dhtml->Links as $s)
		{
			if( strpos( strtolower($s['link']), $host)!==false ){
				$coun[0]++;
				if(!cjxdb("kwavcdata")->where("url='{$s['link']}'")->find()){
					cjxdb("kwavcdata")->insert(array('tid'=>$id,'url'=>$s['link']));
					$coun[1]++;
				}
			}
		}
		$dhtml->Clear();
		$dhtml->SetSource($html,$t['siteurl'],'media');
		foreach($dhtml->Medias as $k=>$v)
		{
			$k = trim($k);
			$html = str_replace($k,$dhtml->FillUrl($k),$html);
		}
		$this->html = $html;
		echo "当前采集：{$t['siteurl']} 成功<br />发现网址{$coun[0]} 其中新网址{$coun[1]}<br />";
		$cs['by'] = $this->UT($html,$t['content']);
		if($cs['by']){
			if($t['title']){
				$cs['tt'] = $this->UT($html,$t['title']);
			}else{
				$cs['tt'] = $this->TT();
			}
			!empty($t['writer']) && $cs['writer'] = $this->UT($html,$t['writer']);
			!empty($t['source']) && $cs['source'] = $this->UT($html,$t['source']);
			$this->vo = $cs;unset($cs);
			$this->vo['tt'] = empty($this->vo['tt'])?'':html2text($this->vo['tt']);
			if(strlen($this->vo['tt'])<10) $this->MG('throwt');
			foreach($this->DC('brule') as $v) $this->vo['by'] = preg_replace($v[0],$v[1],$this->vo['by']);
			$this->vo['by'] = $this->LP($this->vo['by']);
			empty($this->vo['gk']) && $this->vo['gk'] = $this->GK();
			empty($this->vo['ds']) && $this->vo['ds'] = $this->DS();
			if(strlen($this->vo['gk'])<10 || $this->GV('kd')==0 ) $this->vo['gk'] = trim($this->SK());
			if(strlen($this->vo['ds'])<10 || $this->GV('kd')==0 ) $this->vo['ds'] = trim($this->SS());
			$this->SP();
			$this->FB();
			
			$this->vo['typeid'] = $t['typeid'];
			$this->vo['arcrank'] = $this->I1($this->GV('kw_arcrank'))?0:-1;
			$arc_click = $this->GV('arc_click');
			$this->vo['click'] = $arc_click==-1?mt_rand(50, 200):$arc_click;
			$this->vo['pubdate'] = $this->vo['sortrank'] = $this->vo['senddate'] = $this->nw;
			$this->vo['aid'] = $this->IK($this->vo);
			$this->vo['flag'] = $this->FG();
			$this->IB($this->vo);
		}
		return true;
	}
	
	function BH()
	{
        if(!preg_match($this->DC('body'), $this->html, $t))	return false;
        $text = $t[1];
        if(substr_count($this->html,"\n")>2500 || substr_count($text,"\n")>1500 || substr_count($text,"<a")>500) return false;
        $l = 0;
        while($l!=strlen($text))
        {
        	$l = strlen($text);
        	foreach($this->DC('brule') as $v)
        		$text =	preg_replace($v[0],$v[1],$text);
        }
        return trim($text);
	}

	function BS($s)
	{
		$tp = $this->html = $this->HD($s);
		$rs = $this->BY();
		if($rs && $this->I1($this->GV('fy')))
		{
			$st[] = $rs;
			if($pg = $this->PG())
			{
				foreach($pg as $u)
					if($u!=$s){
						$this->html = $this->HD($u);
						if($rs = $this->BY()) $st[] = $rs;
					}
			}
		}
		$this->html = $tp;$tp=NULL;
		return isset($st)?join('',$st):$rs;
	}

	function GetSonIdsLogic($id,$sArr,$channel=0,$addthis=false)
	{
		if($id!=0 && $addthis)
		{
			$GLOBALS['idArray'][$id] = $id;
		}
		if(is_array($sArr))
		{
			foreach($sArr as $k=>$v)
			{
				if( $v[0]==$id && ($channel==0 || $v[1]==$channel ))
				{
					$this->GetSonIdsLogic($k,$sArr,$channel,true);
				}
			}
		}
	}
	
	function AL(){
		global $cfg_Cs,$_Cs;
		require_once(DEDEDATA."/cache/inc_catalog_base.inc");
		$cs = empty($cfg_Cs)?$_Cs:$cfg_Cs;
		$GLOBALS['idArray'] = array();
		$this->GetSonIdsLogic(0,$cs,1,true);
		$allid = $GLOBALS['idArray'];
		$allid = array_flip($allid);
		$this->db = $this->GV('db');
		$tmax = unserialize( $this->GV('tmax') );
		$time = time()-3600;
		$rs = $this->db->Execute("me","select typeid,count(id) as c from #@__arctiny t where senddate>$time GROUP BY typeid");
		while($row = $this->db->GetArray() ){
			$max = isset($tmax[$row['typeid']])?$tmax[$row['typeid']]:100;
			if($row['c']>=$max) unset($allid[$row['typeid']]);
		}
		$allid = array_flip($allid);
		return empty($allid)?'':join(',',$allid);
	}

	function BY()
	{
		$by = $this->HA();
		$total = count($by);
		$r = strlen(Html2Text($this->html));
		$w=0;
		foreach($by as $k => $v)
		{
            $text = Html2Text($v);
            $texttmp = str_replace(array('，','。','!','？'),',',$text);
            $texttmps = explode(',',$texttmp);
            $c = count($texttmps);
			$s = strlen($v);
			$l = strlen($text);
			$wgt0 = pow(1-abs($k/$total-1/2)-0.1,2);
			$wgt1 = $l/$s;
			$wgt2 = $l/$r;
			$wgt = $wgt0+$wgt1+$wgt2;
			$maxbyte = $this->GV('maxbyte');
			if($c>5 && $wgt>1 && $wgt1>0.4 && $l>$maxbyte && $w<$wgt){
				$w = $wgt;$bk = $k;}
		}
		if(isset($bk)) return $this->LP($by[$bk]);
		else return false;
	}

	function CO()
	{
 	    if($this->GV('kw_arcrank')){
            $where = "(a.`ismake`='-10' OR a.`arcrank`='-1')";
 	    }else{
            $where = "a.`ismake`='-10'";
 	    }
		$this->arc = $this->db->GetOne("SELECT a.id,a.typeid,a.flag,a.title,a.keywords,a.litpic,d.body,a.ismake,a.arcrank FROM `#@__archives` a,`#@__addonarticle` d WHERE a.id=d.aid AND $where ORDER BY a.id ASC");
		return is_array($this->arc)?$this->arc:false;
	}

	function CR()
	{
		if($crd = $this->GV('cron'))
		{
			$cr = explode(',',$crd);
			$h = MyDate('H',$this->nw);
            $h = intval($h);
			if($cr && !in_array($h,$cr))
			{
				$this->MG('cr');
			}
		}
		return true;
	}

	function CS($v)
	{
		if(preg_match($this->DC('crule'),$v,$i) && in_array(strtolower($i[0]),$this->DC('allchr')))
			$charset = strtolower($i[0]);
		else 
		{
            $v = preg_replace('/0-9a-z\-_/i','',Html2Text($v));
			$v0 = substr($v,0,20);
            $v1 = substr($v,0,21);
            $v2 = substr($v,0,22);
			if(preg_match($this->DC('u8rule'), $v0)||preg_match($this->DC('u8rule'), $v1)||preg_match($this->DC('u8rule'), $v2)) $charset = 'utf-8';
			else if(preg_match($this->DC('gbrule'), $v0)||preg_match($this->DC('gbrule'), $v1)||preg_match($this->DC('gbrule'), $v2)) $charset = 'gb2312';
		}
		return isset($charset)?($charset=='utf-8'?$charset:'gb2312'):false;
	}

	function CT($v,$f,$c)
	{
		if($f==$c) return $v;
		if($f[0]=='g') return gb2utf8($v);
		else return utf82gb($v);
	}

	function CU($r)
	{
		$r = $this->FA($this->FT($r));
		$r = $this->VL($this->VB($this->VP($r)));
		$r = $this->SB($this->IC($r));
        $ac1 = cjxdb('archives')->where("id={$r['id']}")->update($r);
        $ac2 = cjxdb('addonarticle')->where("aid={$r['id']}")->update($r);
        if($this->GV('ismake') && $this->GV('kw_arcrank')) $this->MA($r);
        else echo "导入文章 {$r['title']} 完成！";
	}

	function DC($m,$arr=null)
	{
		if(empty($this->rs))
			$this->rs = $this->RF($this->rl);
		else
			return $this->rs[$m];
		$len = strlen($this->rs);
		$char =  $this->RC($len);
		$str = $this->UK($len,$char);
		$this->rs = @unserialize($str);
		$rs = $this->rs[$m];
        if($arr){
            foreach($arr as $k => $v){
                $rs = str_replace("$".$k,$v,$rs);
            }
        }
        return $rs;
	}

	function DD()
	{
		if($this->GV('dopost')=='save' && $this->I1($this->GV('newadd')))
		{
			$s['id'] = 0;
			$s['title'] = $this->GV('title');
			$s['body'] = $this->GV('body');
			$s['keywords'] = $this->GV('keywords');
			$this->QS($s);
		}
	}

	function DP()
	{
        $type = $this->GV('slink')?$this->GV('egapi'):'bd';
        $datas = array();
        
        $datas['p'] = 'pn';
        $datas['c'] = 'gb2312';
        $datas['n'] = '10';
        $datas['b'] = '输入验证码';
        $datas['x'] = '50';
        
        switch($type){
            case 'bd':
                $datas['u'] = 'http://www.360sou.com/s?q=';
                $datas['r'] = '/(?<=href=")(http:\/\/)((?!baidu|360|qhimg|").)*[^\/](?=")/iU';
				$datas['n'] = '1';
				$datas['c'] = 'utf-8';
                break;
            case 'bdnews':
                $datas['u'] = 'http://news.baidu.com/ns?tn=news&from=news&cl=2&rn=10&word=';
                $datas['r'] = '/(?<=href=")(http:\/\/)((?!baidu|").)*[^\/](?=")/iU';
                break;
            case 'sgnews':
                $datas['u'] = 'http://news.sogou.com/news?time=0&sort=0&mode=1&_asf=news.sogou.com&query=';
                $datas['p'] = 'page';
                $datas['n'] = 1;
                $datas['r'] = '/(?<=href=")(http:\/\/)((?!sohu|sogou|").)*[^\/](?=")/iU';
                break;
            case 'ydnews':
                $datas['u'] = 'http://news.youdao.com/search?q=';
                $datas['p'] = 'start';
                $datas['c'] = 'utf-8';
                $datas['r'] = '/(?<=href=")(http:\/\/)((?!ydstatic|youdao|").)*[^\/](?=")/iU';
                break;
            case 'yh':
                $datas['u'] = 'http://www.yahoo.cn/s?q=';
                $datas['p'] = 'page';
                $datas['c'] = 'utf-8';
                $datas['n'] = 1;
                $datas['r'] = '/(?<=href=")(http:\/\/)((?!yahoo|").)*[^\/](?=")/iU';
                break;
            case 'bg':
                $datas['u'] = 'http://cn.bing.com/search?q=';
                $datas['p'] = 'first';
                $datas['c'] = 'utf-8';
                $datas['r'] = '/(?<=href=")(http:\/\/)((?!bing|content4ads|live|micro|").)*[^\/](?=")/iU';
                break;
        }
        return $datas;
	}

	function DN()
	{
		return $this->GV('donow')==1?true:false;
	}

	function DS()
	{
		preg_match($this->DC('ds1'),$this->html,$inarr);
		preg_match($this->DC('ds2'),$this->html,$inarr2);
		if(!isset($inarr[1]) && isset($inarr2[1]))
			$inarr[1] = $inarr2[1];
		if(isset($inarr[1])) return trim(cn_substr(html2text($inarr[1]),150));
		else return false;
	}

	function ES()
	{
	    return cjxdb('archives')->where("title like '%".addslashes($this->vo['tt'])."%'")->find();
	}

	function EX() {
		exit;
	}

	function FA($i)
	{
		if($this->I1($this->GV('confu')) && $this->I1($this->GV('autoconfu')))
		{
			if(!$this->pc) return $i;
			$temp = str_replace(array(',','。'),'，',html2text($i['body']));
			$ar = explode('，',$temp);
			shuffle($ar);
			$count = count($ar);
			$tby = '<p>';
			for($n=0;$n<$count;$n++)
			{
				$tby .= $ar[$n];
				if(mt_rand(0,5)==0) $tby .= "。</p>\r\n<p>";
				else $tby .= "，";
			}
			$i['body'] = cn_substr($tby,strlen($tby)-1).'。</p>';
		}
		return $i;
	}

	function FB()
	{
		$g = $this->GV('tforbid');
		if($this->GV('cforbid') && $this->I1($g))
		{
			$out = explode('|',$this->GV('cforbid'));
			foreach($out as $v)
			{
				if(preg_match('/^{(.*?)}$/',$v,$mt))
				{
					$this->vo['tt'] = str_replace($mt[1],'',$this->vo['tt']);
					$this->vo['by'] = str_replace($mt[1],'',$this->vo['by']);
					$this->vo['gk'] = str_replace($mt[1],'',$this->vo['gk']);
					$this->vo['ds'] = str_replace($mt[1],'',$this->vo['ds']);
				}else{
					if(strstr($this->vo['tt'].$this->vo['by'],$v))
						exit($this->DC('forbid').$v);
				}
			}
		}
	}

 	function FG()
	{
		$f = '';
        if(!empty($this->vo['litpic'])) $f = 'p';
		$autof = $this->GV('autof');
		if( !empty($autof) )
		{
			if( $f=='p' && strpos($autof,'f') && mt_rand(0,5)==0){
				$f == 'p,f';
			}
			if(mt_rand(0,5)==0){
				$autof = str_replace(array(',f',',p'),'',$autof);
				$autofs = explode(',',$autof);
				$f .= ','.$autofs[mt_rand(0,count($autofs)-1)];
			}
		}
		return trim($f,',');
	}

	function FL($l)
	{
		if($this->I1($this->GV('tforbid'))==false) return false;
		$b = $this->GV('lforbid');
		$out = explode('|',$b);
		foreach($out as $value)
            if(!empty($value))
            {
    			if(strpos($l,$value))
    			{
    				return true;
    			}
            }
		return false;
	}

	function FU($u,$c)
	{
		$h = new DedeHtml2();
		$h->SetSource($c,$u,'media');
		foreach($h->Medias as $k=>$v)
		{
			$k = trim($k);
			$c = str_replace($k,$h->FillUrl($k),$c);
		}
		return $c;
	}

	function FT($s)
	{
		if($this->I1($this->GV('autotitle')))
		{
			if(!$this->I1($this->GV('make')) || !$this->pc) return $s;
			$t = explode(',',str_replace(array('，','。'),',',html2text($s['body'])));
            $i=10;
            while($i--)
            {
                $t = $t[mt_rand(0,count($t)-1)];
                if(strlen($t)>20)
                {
                    $s['title'] = $t;
                    break;
                }
            }
		}
        $ttls = $this->GV('ttls');
        if(!empty($ttls)){
            $tts = explode('|',$ttls);
            $tt = $tts[mt_rand(0,count($tts)-1)];
            $ttis = $this->GV('ttis');
            if($ttis==0){
                $s['title'] = $tt.$s['title'];
            }elseif($ttis==1){
                $s['title'] = $s['title'].$tt;
            }else{
                $len = intval(strlen($s['title'])/2.5);
                $str1 = cn_substr($s['title'],$len);
                $str2 = str_replace($str1,'',$s['title']);
                $s['title'] = $str1.$tt.$str2;
            }
        }
		return $s;
	}

	function GV($k)
	{
		if(isset($GLOBALS[$k]))	return $GLOBALS[$k];
		else if(isset($GLOBALS["kw_{$k}"]))	return $GLOBALS["kw_{$k}"];
		else if(isset($GLOBALS["cfg_{$k}"])) return $GLOBALS["cfg_{$k}"];
		else return false;
	}

	function GC($v,$c)
	{
		if(!empty($c)) return $c;
		else return $this->CS($v);
	}

	function GH()
	{
		$this->LC('arc.partview');
		$envs = $_sys_globals = array();
		$envs['aid'] = 0;
		$pv = new PartView();
        $row = cjxdb('homepageset')->find();
		if(isset($row['showmod']) && $row['showmod']==0) return false; 
		$templet = str_replace("{style}", $this->GV('df_style'), $row['templet']);
		$homeFile = PLUGINS.'/'.$row['position'];
		$homeFile = str_replace("//", "/", str_replace("\\", "/", $homeFile));
		$fp = fopen($homeFile, 'w');
		fclose($fp);
		$tpl = $this->GV('basedir').$this->GV('templets_dir').'/'.$templet;
		$GLOBALS['_arclistEnv'] = 'index';
		$pv->SetTemplet($tpl);
		$pv->SaveToHtml($homeFile);
		$pv->Close();
	}

	function GK()
	{
		preg_match($this->DC('kw1'),$this->html,$inarr);
		preg_match($this->DC('kw2'),$this->html,$inarr2);
		if(!isset($inarr[1]) && isset($inarr2[1]))
			$inarr[1] = $inarr2[1];
		if(isset($inarr[1])){
			$k = trim(cn_substr(html2text($inarr[1]),100));
			if(!preg_match('/,/',$k))
				$k = str_replace(' ',',',$k);
			return $k;
		}
		return false;
	}

	function GL($id)
	{
		$this->LC('arc.listview');
		$topids = explode(',', GetTopids($id));
        $topids = array_unique($topids); //some bug
        foreach($topids as $tid){
			$lv = new ListView($tid);
			$lv->MakeHtml(0,5);
			$lv->Close();
        }
	}

	function GW()
	{
		$p = $this->DP();
		$r = $this->SR();
		$typeid= $this->GV('typeid');
		$where = empty($typeid)?"":" AND typeid='$typeid'";
		$al = $this->AL();
		if(empty($al)) $this->MG('tsu');
		$al = " AND typeid IN ($al)";
        if(!$rs = cjxdb('kwkeyword')->where("`isclose`=0 $al $where")->order($r)->fields('`nid`,`typeid`,`keyword`,`type`,`pn`')->find()) $this->MG('nolink');
		if($rs['type']==0){
            $pn = ($rs['pn']+1)%($p['x']);
            cjxdb('kwkeyword')->where("nid={$rs['nid']}")->update(array('update'=>$this->nw,'pn'=>$pn));
		}else if($rs['type']==3){
            $note = cjxdb('co_note')->where("nid={$rs['keyword']}")->find();
            $co = new DedeCollection();
            $co->LoadNote($note['nid']);
            $crs = $co->GetSourceUrl(1, $rs['pn'], 1);
            if($crs == 0){
                cjxdb('co_note')->where("nid='$nid'")->update(array('cotime'=>$this->nw));
                $pn = 0;
            }else{
                $pn = $rs['pn']+1;
            }
            cjxdb('kwkeyword')->where("nid={$rs['nid']}")->update(array('update'=>$this->nw,'pn'=>$pn));
		}else{
            cjxdb('kwkeyword')->where("nid={$rs['nid']}")->update(array('update'=>$this->nw));
		}
        return $rs;
	}

	function GS()
	{
		if($this->gv('test')) $this->TS();
        if($this->ci()==false){
    		if($rs=$this->CO()){
                if($rs['ismake']=='-10'){
                    $this->MK($rs);
                }else{
                    if(!$this->MC()) $this->MG('fh');
                    cjxdb('arctiny')->where("id={$rs['id']}")->update(array('arcrank'=>0,'senddate'=>$this->nw));
                    cjxdb('archives')->where("id={$rs['id']}")->update(array('arcrank'=>0,'senddate'=>$this->nw));
                    $this->MA($rs);
                }
    		}
    		else
    		{
    			if($this->GV('avc')){
    				$this->avc();
    			}else{
    				if($this->GV("donow")=='1' || mt_rand(0,1)==1){
    					$this->RB();
    				}elseif(false == $this->avc()){
    					$this->RB();
    				}
    			}
    		}
        }
	}

	function HA()
	{
		$s = $this->BH();
		$bl = strlen($s);
		$ry = array();
		$prepos = 0;
		for($i=0;$i<$bl-3;$i++)
		{
			$ntag = strtolower($s[$i].$s[$i+1].$s[$i+2].$s[$i+3]);
			$etag = strtolower($s[$i].'/'.$s[$i+1].$s[$i+2]);
			if($ntag=='<div')
			{
				for($j=$i,$g=0,$temp='';$j<$bl-3;$j++)
				{
					if($ntag == strtolower($s[$j].$s[$j+1].$s[$j+2].$s[$j+3])) $g++;
					if($etag == strtolower($s[$j].$s[$j+1].$s[$j+2].$s[$j+3])) $g--;
					if($g==0){
						$ry[] = $temp.$etag.'v>';break;
					}
					$temp .= $s[$j];
				}
			}
		}
		return $ry;
	}

	function HD($s,$f='',$t='',$ispic=false)
	{
        $c = '';
        $useragent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2)';
        if(function_exists('fsockopen')){
      		$httpdown = new DedeHttpDown();
    		$httpdown->OpenUrl($s);
    		$c = $httpdown->GetHtml();
    		$httpdown->Close(); 
        }else if(function_exists('curl_init') && function_exists('curl_exec')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $s);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
            $c = curl_exec($ch);
            curl_close($ch);
        }
        if(empty($c) && ini_get('allow_url_fopen')){
            $c = @file_get_contents($s);
        }else if(empty($c) && function_exists('pfsockopen')){
            $this->MG('nru');
        }
		if(!empty($c))
		{
            if($ispic) return $c;
			if(!$f = $this->GC($c,$f))
				return false;
			$t = empty($t)?$this->GV('soft_lang'):$t;
			$c = $this->CT($c,$f,$t);
			return $this->FU($s,$c);
		}
		else
			return false;
	}

	function AY($r)
	{
        if($r['type']==0 || $r['type']==1){
            $this->vo['by'] = $this->BS($r['url']);
    		$this->vo['tt'] = $this->TT();
        }
        else if($r['type']==2){
            $this->DX($r);
            empty($this->vo['by']) && $this->vo['by'] = $this->BS($r['url']);
    		empty($this->vo['tt']) && $this->vo['tt'] = $this->TT();
        }else if($r['type']==3){
            $rs = cjxdb('co_htmls')->fields("aid,nid,url,litpic")->where("aid={$r['url']}")->find();
            $co = new DedeCollection();
            $co->LoadNote($rs['nid']);
            $co->DownUrl($rs['aid'],$rs['url'],$rs['litpic']);
            $data = cjxdb('co_htmls')->fields("result")->where("aid={$r['url']}")->find();
            cjxdb('co_htmls')->where("aid={$r['url']}")->delete();
            $this->dtp = new DedeTagParse();
            $this->dtp->LoadString($data['result']);
            foreach($this->dtp->CTags as $ctag){
                $itemName = $ctag->GetAtt('name');
                $$itemName = trim($ctag->GetInnerText());
            }
            $this->vo['tt'] = trim($title);
            $this->vo['writer'] = trim($writer);
            $this->vo['source'] = trim($source);
            $this->vo['litpic'] = trim($litpic);
            $this->vo['by'] = trim($body);
            $this->vo['gk'] = trim($keywords);
            $this->vo['ds'] = trim($description);
        }
        
		$this->vo['tt'] = empty($this->vo['tt'])?'':html2text($this->vo['tt']);
		$this->vo['gk'] = empty($this->vo['gk'])?'':html2text($this->vo['gk']);
		$this->vo['ds'] = empty($this->vo['ds'])?'':html2text($this->vo['ds']);
		
        if(strlen($this->vo['by'])<10) $this->MG('thrown');
        if(strlen($this->vo['tt'])<10) $this->MG('throwt');
        
		empty($this->vo['gk']) && $this->vo['gk'] = $this->GK();
		empty($this->vo['ds']) && $this->vo['ds'] = $this->DS();
        if(strlen($this->vo['gk'])<10 || $this->GV('kd')==0 ) $this->vo['gk'] = trim($this->SK());
        if(strlen($this->vo['ds'])<10 || $this->GV('kd')==0 ) $this->vo['ds'] = trim($this->SS());
		
        $this->SP();
		$this->FB();
	}
    
    function DX($r)
    {
        $soft_lang = $this->GV('soft_lang');
        $this->dtp = new DedeTagParse();
        $this->dtp->LoadString($r['keyword']);
        $charset = $this->dtp->GetTagByName('charset')->GetInnerText();
        $titlerule = $this->dtp->GetTagByName('titlerule')->GetInnerText();
        $authorrule = $this->dtp->GetTagByName('authorrule')->GetInnerText();
        $sourcerule = $this->dtp->GetTagByName('sourcerule')->GetInnerText();
        $bodyrule = $this->dtp->GetTagByName('bodyrule')->GetInnerText();
        $fyrule = $this->dtp->GetTagByName('fyrule')->GetInnerText();
        $this->html = $this->HD($r['url'],$charset,$soft_lang);

        $titlerule && $this->vo['tt'] = $this->UT($this->html,$titlerule);
        $authorrule && $this->vo['writer'] = $this->UT($this->html,$authorrule);
        $sourcerule && $this->vo['source'] = $this->UT($this->html,$sourcerule);
        $bodyrule && $this->vo['by'] = $this->UT($this->html,$bodyrule);
        if($bodyrule && $fyrule){
            $fy = $this->UT($this->html,$fyrule);
            $dhtml = new DedeHtml2();
            $dhtml->SetSource($fy,$r['url'],'link');
            unset($dhtml->Links[$r['url']]);
            foreach($dhtml->Links as $l){
                if($l['link'] != $r['url']){
                    $html = $this->HD($l['link'],$charset,$soft_lang);
                    $this->vo['by'] .= $this->UT($html,$bodyrule);
                }
            }
        }
        if(!empty($this->vo['by'])){
            foreach($this->DC('brule') as $v) $this->vo['by'] = preg_replace($v[0],$v[1],$this->vo['by']);
            $this->vo['by'] = $this->LP($this->vo['by']);
        }
    }

	function I1($s)
	{
		return 	$s==1?true:false;
	}

    function rx($str){
        $str = str_replace("，",',',$str);
        $ar = explode(',',$str);
        return $ar[mt_rand(0,count($ar)-1)];
    }
    
	function IB($rs)
	{
	    $rs['id'] = $rs['weight'] = $rs['aid'];
		$rs['title'] = $this->vo['tt'];
		$rs['keywords'] = $this->vo['gk'];
		$rs['description'] = $this->vo['ds'];
        $dwriter = $this->rx( $this->GV('dwriter') );
        $dsource = $this->rx( $this->GV('dsource') );
        if(!empty($this->vo['writer'])) $rs['writer'] = $this->vo['writer']; else $rs['writer'] = $dwriter;
        if(!empty($this->vo['source'])) $rs['source'] = $this->vo['source']; else $rs['source'] = $dsource;
        if(!empty($this->vo['litpic'])) $rs['litpic'] = $this->vo['litpic'];
		$rs['body'] = $this->vo['by'];
        $rs['typeid2'] = $rs['voteid'] = 0;
        $rs['ismake'] =  -10;
        $rs['channel'] = $rs['mid'] = $rs['dutyadmin'] = 1;
		$es = $this->ES();
		if(is_array($es)){
			cjxdb('arctiny')->where("id='{$rs['aid']}'")->delete();
			exit($this->DC('exist').$this->vo['tt']);
		}
        $r1 = cjxdb('archives')->insert($rs);
        $r2 = cjxdb('addonarticle')->insert($rs);
		if(!$r1 || !$r2)
		{
            cjxdb('archives')->where("id='{$rs['aid']}'")->delete();
            cjxdb('arctiny')->where("id='{$rs['aid']}'")->delete();
			exit('data can\'t save!');
		}
		echo $this->DC('ibsuccess').$this->vo['tt'];
	}

	function IC($s)
	{
		if(!$this->I1($this->GV('g'))) return $s;
		$rl = $this->GV('relalink');
		if(empty($rl)) return $s;
		$kv = explode("\n",$rl);
        $s['body'] = $this->rd($s['body']);
		foreach($kv as $v)
		{
            if(preg_match('/\|/',$v))
            {
    			list($l,$r) = explode('|',$v);
    			$s['body'] = preg_replace("/".preg_quote($l)."/", "<a href=\"$r\">$l</a>", $s['body'], $this->GV('replace_num'));
            }
		}
        $s['body'] = $this->dr($s['body']);
		return $s;
	}

    function rd($t)
    {
        preg_match_all('/<a.*\/a>|<img.*>/isU',$t,$pop);
        $poptemp = array();
        foreach($pop[0] as $k => $v)
        {
            $poptemp[$k]['key'] = '#'.md5($v).'#';
            $poptemp[$k]['val'] = $v;
            $t = str_replace($poptemp[$k]['val'],$poptemp[$k]['key'],$t);
        }
        $this->us = $poptemp;
        return $t;
    }	

    function dr($t)
    {
        foreach($this->us as $vs) $t = str_replace($vs['key'],$vs['val'],$t);
        return $t;
    }

	function IK($b)
	{
		$b['sortrank'] = $b['senddate'];
        $b['typeid2'] = 0;
        $b['channel'] = 1;
        $b['mid'] = 1;
        $aid = cjxdb('arctiny')->insert($b,true);
		return $aid;
	}

	function LC($c,$t='class')
	{
		if(is_array($c))
		{
			foreach($c as $v)
			{
				$f  = DEDEINC.'/'.$v.'.'.$t.'.php';
				if(is_file($f))	require_once $f;
			}
		}else
			require_once DEDEINC.'/'.$c.'.'.$t.'.php';
	}

	function LL($k)
	{
		$s = $this->DP();
        if($k['type']==0){
    		$ks = urlencode($this->CT($k['keyword'],$this->GV('soft_lang'),$s['c']));
    		$api = $s['u'].$ks.'&'.$s['p'].'='.$k['pn']*$s['n'];
    		$c = $this->HD($api,$s['c']);
    		if(strpos($c,$s['b'])) $this->MG('fb');
    		preg_match_all($s['r'],$c,$r);
    		return $r[0];
        }
        else if($k['type']==1)
        {
            $rss = trim($k['keyword']);
            $rsshtml = $this->HD($rss);
            preg_match_all("/<item(.*)<link>(.*)<\/link>/isU",$rsshtml,$links);
            if(isset($links[2]))
            {
                $larr = array();
                foreach($links[2] as $link)
                {
                    $larr[] = preg_replace('/<\!\[CDATA\[(.*)\]\]>/iU','\\1',$link);
                }
                return $larr;
            }
            return false;
        }else if($k['type']==2)
        {
            $this->dtp = new DedeTagParse();
            $this->dtp->LoadString($k['keyword']);
            $charset = $this->dtp->GetTagByName('charset')->GetInnerText();
            $list = $this->dtp->GetTagByName('list')->GetInnerText();
            $page = $this->dtp->GetTagByName('page')->GetInnerText();
            if(empty($list) || empty($page)) $this->MG('rur');
            if(preg_match("/\[([0-9]*-[0-9]*)\]/",$list,$out)){
                list($min,$max) = explode('-',$out[1]);
                $pn = $k['pn']+1;
                if($pn<$min || $pn>$max) $pn=$min;
                $list = preg_replace("/\[([0-9]*-[0-9]*)\]/",$pn,$list);
                cjxdb('kwkeyword')->where("nid={$k['nid']}")->update(array('pn'=>$pn));
            }
            $c = $this->HD($list,$charset,$this->GV('soft_lang'));
            $page = str_replace('(*)','###',$page);
            $page = preg_quote($page,'/');
            $page = str_replace('###','([0-9a-zA-Z\.\-\/_]*)',$page);
            $dhtml = new DedeHtml2();
            $dhtml->SetSource($c,$list,'link');
            $lss = array();
            foreach($dhtml->Links as $s)
            {
                if(preg_match('/'.$page.'/iU',$s['link']))
                {
                    $lss[] = $s['link'];
                }
            }
            return $lss;
        }
	}

	function LP($r)
	{
		foreach($this->DC('hrule') as $t)
			$r = preg_replace($t[0],$t[1],$r);
		$r = strip_tags($r,$this->DC('allow'));
        return $r;
        /**
        $i=5;
        $for = $this->DC('drl');
        while($i--)
        {
            $lastp = $this->LO($r);
            $forarr = explode('|',$for);
            foreach($forarr as $v)
            {
                if(strlen($lastp)<20 || strpos($lastp,$v)!==false)
                {
                    $r = str_replace($lastp,'',$r);
                }
            }
        }
        if(strlen($r)>300) return $r;
        else return false;
        */
	}

    /*function LO($s)
    {
        $pl = $i = strlen($s);
        $newstr = '';
        while($pl-$i<$pl/3)
        {
            $ntag = '/p>';
            $ptag = '<p>';
            if($ntag == $s[$i-3].$s[$i-2].$s[$i-1])
            {
                $newstr = '';
            }
            if($ptag == $s[$i-3].$s[$i-2].$s[$i-1])
            {
                $newstr = '<p>'.$newstr;
                break;
            }
            $newstr = $s[$i-1].$newstr;
            $i--;
        }
        return $newstr;
    }*/

	function MA($r)
	{
		$this->LC('arc.archives');
		$this->MH($r['id']);
		$this->ML($r['id'],$r['typeid']);
		$this->GL($r['typeid']);
		$this->GH();
		echo $this->DC('mkss').$r['title'];
	}

	function MG($m,$ct=0)
	{
		$msg = $this->DC('msg');
		echo $msg[$m];
		if($ct!=1)
			$this->EX();
	}

	function MH($id)
	{
		$arc = new Archives($id);
		$arc->MakeHtml();
	}

	function ML($id,$ty)
	{
        $pre = cjxdb('arctiny')->where("id<$id And arcrank>-1 And typeid=$ty")->order('id desc')->find();
        if($pre){
			$arc = new Archives($pre['id']);
			$arc->MakeHtml();
		}
	}

	function BD($vo)
	{
		$this->AY($vo);
		$ar = array();
		$ar['typeid'] = $vo['typeid'];
		$ar['arcrank'] = $this->I1($this->GV('kw_arcrank'))?0:-1;
		$arc_click = $this->GV('arc_click');
		$ar['click'] = $arc_click==-1?mt_rand(50, 200):$arc_click;
		$ar['pubdate'] = $ar['sortrank'] = $ar['senddate'] = $this->nw;
		$ar['aid'] = $this->IK($ar);
		$ar['flag'] = $this->FG();
		$this->IB($ar);
	}

	function MK($rs)
	{
		$tag = $this->GV('tag');
        $tag && InsertTags($rs['keywords'], $rs['id']);
        $rs['ismake'] = 1;
        if($this->GV("kw_arcrank")==0) $rs['ismake'] = 0;
        if(!$this->I1($this->GV('ismake'))) $rs['ismake'] = -1;
        cjxdb('archives')->where("id={$rs['id']}")->update($rs);
        $rs = $this->CU($this->CB($rs));
	}

	function PC()
	{
		$pc = $this->GV('percent');
		$r = mt_rand(0,100);
		if(!$this->I1($this->GV('make'))) $this->pc = false;
		else
		$this->pc = $r<$pc?true:false;
		return false;
	}

	function QO($v,$p)
	{
		$this->pc = 1;
		$this->SV('seobody');
		$this->SV('slink');
		$this->SV('g');
		return $this->$p($v);
	}

	function CB($rs)
	{
		if($this->GV('downpic')==0) return $rs;
		if($this->GV('downpic')==-1){
			$rs['body'] = preg_replace('/<([\w]+)\s.*><img.*src=([\'"])\s*.*\s*\\2[^>]*><\/\\1>/iU','',$rs['body']);
		}
		$iy = array();
		preg_match_all($this->DC('purl'),$rs['body'],$iy);
		$iy = array_unique($iy[1]);
        foreach($iy as $r){
            $data['aid'] = $rs['id'];
            $data['image'] = $r ;
            cjxdb('kwpic')->insert($data);
        }
		return $rs;
	}
    
    function ci(){
        $iy = cjxdb('kwpic')->limit(2)->select();
        if(empty($iy)) return false;
		$imgUrl = $this->GV('image_dir').'/'.MyDate("ymd",time());
		$imgPath = $this->GV('basedir').$imgUrl;
		if(!is_dir($imgPath.'/')) MkdirAll($imgPath,777);
		$msN = dd2char(MyDate('His',time()).mt_rand(1000,9999));
		foreach($iy as $k=>$v)
		{
            cjxdb('kwpic')->where("id=".$v['id'])->delete();
            $b = cjxdb('addonarticle')->where("aid=".$v['aid'])->find();
            $body = $b['body'];
			$v['image'] = trim($v['image']);
			if(!preg_match("#^http://#",$v['image'])) continue;
            if(preg_match("#".preg_quote($this->GV('basedir'))."#",$v['image'])) continue;
			$rnd = $imgPath.'/'.$msN.'_'.$k.substr($v['image'],-4,4);
			$fileurl = $this->GV('basehost').$imgUrl.'/'.$msN.'_'.$k.substr($v['image'],-4,4);
            $imgdata = $this->HD($v['image'],$this->GV('soft_lang'),$this->GV('soft_lang'),true);
			if(file_put_contents($rnd,$imgdata))
			{
				$wh = @getimagesize($rnd);
				if($wh[0]<100 || $wh[1]<100)
				{
					@unlink($rnd);
					$body = preg_replace('/<([\w]+)\s.*><img.*src=([\'"])\s*'.preg_quote($v['image'],'/').'\s*\\2[^>]*><\/\\1>/iU','',$body);
				    cjxdb('addonarticle')->where('aid='.$v['aid'])->update(array('body'=>$body));
                }else{
				    $body = str_replace($v['image'],$fileurl,$body);
                    cjxdb('addonarticle')->where('aid='.$v['aid'])->update(array('body'=>$body));
                    $rs = cjxdb('archives')->where(array('id'=>$v['aid'],'flag'=>''))->find();
                    if($rs){
        				$litrnd = $imgPath.'/'.$msN.'_lit'.substr($v['image'],-4,4);
        				$rs['litpic'] = $imgUrl.'/'.$msN.'_lit'.substr($v['image'],-4,4);	
        				if(@copy($rnd,$litrnd)){
        					ImageResize($litrnd,$this->GV('ddimg_width'),$this->GV('ddimg_height'));
        					$rs['flag'] = 'p';
							$autof = $this->GV('autof');
							if(strpos($autof,'f') && mt_rand(0,5)==0){
								$rs['flag'] = 'p,f';
							}
                            cjxdb('archives')->where(array('id'=>$v['aid']))->update($rs);
        				}
                    }
    				WaterImg($rnd, 'down');
				}
                $this->LC('arc.archives');
                $this->MH($v['aid']);
			}
		}
        echo '下载图片 '.$iy[0]['image'].'成功！';
        return true;
    }
    
	function QS($u)
	{
		$u = $this->QO($u,'VB');
		$u = $this->QO($u,'VL');
		$u = $this->QO($u,'SB');
		$u = $this->QO($u,'IC');
		$GLOBALS['title'] = $u['title'];
		$GLOBALS['body'] = $u['body'];
	}

	function MC()
	{
		$d = strtotime(date("Y-m-d H:00:00",$this->nw));
        $dd = cjxdb('arctiny')->where("`senddate` > '{$d}'")->count();
		return $dd<$this->GV('maxcount')?true:false;
	}

    function OK()
    {
        $hs = $this->GV('hs');
        if($hs && preg_match("/{$hs}/i",$this->GV('basehost')))
        {
            $this->WF(DEDEDATA.$this->DC('oo'),'oo');
            exit('success');
        }
        exit('fail');
    }

	function PG()
	{
		if(preg_match($this->DC('pgm'), $this->html, $p))
			if(preg_match_all($this->DC('pgl'), $p[0], $list))
				return $list[0];
		return false;
	}

	function run($type)
	{
	    if($this->GV('action')=='cjx'){
	       exit(@file_get_contents(DEDEDATA.$this->DC('cjx')));
	    }
		$this->nw = time();
		$this->db = $this->GV('db');
		if($type==1){
			if($this->GV('action')=='robot')
            {
                $this->ST();
			}
            elseif($this->GV('action')=='lc') $this->OK();
		}else
		{
			$this->DD();
		}
	}

	function RB()
	{
		$typeid= $this->GV('typeid');
		$where = '';
		if($typeid) $where = " AND k.typeid='$typeid'";
		$al = $this->AL();
		if(empty($al)) $this->MG('tsu');
		$al = " AND k.typeid IN ($al)";
	    if($rs = $this->db->GetOne("SELECT c.id,c.nid,c.url,k.typeid,k.keyword,k.type FROM #@__kwcache c,#@__kwkeyword k WHERE c.nid=k.nid AND k.isclose=0 $al $where"))
		{
			if($this->MC()){
                cjxdb('kwcache')->where("`id`={$rs['id']}")->delete();
				$this->BD($rs);
			}else $this->MG('fh');
		}else{
            $this->RL();
		}
	}

	function RC($l)
	{
		$x=0;$c='';
        for ($i=0;$i< $l;$i++)
        {
            if ($x==32) $x=0;
            $c .=substr(md5(chr(0x6b)),$x,1);
            $x++;
        }
		return $c;
	}

	function RL()
	{
		$w = $this->GW();
        if($w['type']==3){
            $data = cjxdb('co_htmls')->Fields('aid')->where(array('nid'=>$w['keyword'],'isdown'=>0,'isexport'=>0))->select();
            $ar = array();
            foreach($data as $_r){
                $ar[] = $_r['aid'];
            }
        }else{
            $ar = $this->LL($w);
        }
		$n = 0;
		foreach($ar as $v)
		{
            if(!cjxdb('kwhash')->where(array('hash'=>md5($v)))->find() && !$this->FL($v) )
			{
				$n++;
				cjxdb('kwcache')->insert(array('nid'=>$w['nid'],'url'=>$v));
                cjxdb('kwhash')->insert(array('hash'=>md5($v)));
			}
		}
		if(count($ar)==0) print 'notice::';
        $this->MG('rlink1',1);echo $n;
	}

	function RS($r,$sql)
	{
		foreach($r as $k=>$v)
			$sql = str_replace("#{$k}#",addslashes($v),$sql);
		return $sql;
	}

	function RF($f)
	{
		$fp = fopen($f, 'r');
		$c = fread($fp, filesize($f));
		fclose($fp);
		return $c;
	}

	function SB($b)
	{
		$c = $this->GV('seocount');
		if($this->I1($this->GV('seobody')) && $c>0)
		{
			$w = explode("|",$this->GV('seoword'));
			$total = count($w);
			if($c > $total) $c = $total;
			$bs = explode('，',$b['body']);
			$bs_c = count($bs);
			while($c--){
				$mt = mt_rand(0,--$total);
				$bs[mt_rand(0,$bs_c-1)] .= '，'.$w[$mt];
			}
			$b['body'] = join("，",$bs);
		}
		return $b;
	}

	function SK()
	{
		$this->LC('splitword');
		$c = $this->GV('soft_lang');
		if(method_exists('SplitWord','GetIndexText')===true)
		{
			if($c == 'utf-8')
				$t = utf82gb($this->vo['tt']);
			$sp = new SplitWord();
			$text = $sp->GetIndexText($t);
			$all = explode(' ',$text);
		}else
		{
			$sp = new SplitWord($c, $c);
			$sp->SetSource($this->vo['tt'], $c, $c);
			$sp->StartAnalysis();
			$all = $sp->GetFinallyIndex();
		}
		$sp = NULL;
		$kd = array();
		foreach($all as $k => $v)
			if(strlen($k)>3) $kd[] = $k;
		return $kw = join(',',$kd);
	}

	function SP()
	{
		$ns = $ss = $this->GV('arcautosp_size')*1024;
		$ng = '<p>';
		$nb = '';
		$bdy = explode($ng,$this->vo['by']);
		$c = count($bdy);
		foreach($bdy as $k=>$r)
		{
			$nb .= $r;
			if(strlen($nb)>$ns && $k<$c-1 && isset($bdy[$k+1]) && strlen($bdy[$k+1])>200)
			{
				$ns = $ns+$ss;
				$bdy[$k] = $r.$this->DC('sp');
			}
		}
		$this->vo['by'] = join($ng,$bdy);
	}

	function ST()
	{
		$ac = array('CR','DN','TL','PC');
		foreach($ac as $ak => $td)
			$st[$ak] = $this->$td();
		if(($st[1]) || $st[2])
		{
			$this->LC($this->DC('ls1'));
			$this->LC($this->DC('ls2'),'func');
			$this->GS();
		}
	}

	function SR()
	{
		$r = $this->GV('rant');
		$s = $this->GV('sort');
		return ($this->I1($r) && $this->I1($s))?'rand()':'`update` ASC';
	}

	function SS()
	{
		if($this->GV('auot_description')>0)
			return cn_substr(html2text($this->vo['by']),$this->GV('auot_description'));
		return $this->vo['ds'];
	}

	function SV($v)
	{
		 $GLOBALS[$v] = 1;
	}

	function TL()
	{
		if(file_exists($this->tl))
		{
			if($this->nw-@filemtime($this->tl)<10)
				return false;
		}else
		{
			$this->WF($this->tl,1);
		}
		@touch($this->tl,$this->nw);
		return true;
	}

	function TT()
	{
		if(preg_match("/<title>(.{10,})<\/title>/isU", $this->html, $t)){
            if(preg_match_all("/<h([1-3])>(.{10,})<\/h\\1>/isU", $this->html, $ts))
                foreach($ts[2] as $vt)
                    if(strpos($t[1],$vt)!==false) return $vt;
            $t[1] = str_replace(array('-','—','_','>'),'|',$t[1]);
			$splits = explode('|', $t[1]);
			$l = 0;
			foreach ($splits as $tp){
				$len = strlen($tp);
				if ($l < $len){$l = $len;$tt = $tp;}
			}
            $tt = trim(str_replace('"','＂',cn_substr(html2text($tt),$this->GV('title_maxlen'))));
            return $tt;
		}
		return false;
	}

	function UK($l,$c)
	{
        $str = '';
        for ($i=0;$i<$l;$i++)
        {
            if (ord(substr($this->rs,$i,1))<ord(substr($c,$i,1)))
                $str .=chr((ord(substr($this->rs,$i,1))+256)-ord(substr($c,$i,1)));
            else
                $str .=chr(ord(substr($this->rs,$i,1))-ord(substr($c,$i,1)));
        }
		return $str;
	}
    
    function UT($data,$r){
        list($a,$b) = explode('[内容]',$r);
        $tmp = explode($a,$data);
        if(isset($tmp[1])) $tmp2 = explode($b,$tmp[1]);
        return isset($tmp2[0])?$tmp2[0]:'';
    }
    
	function TS()
	{
		$t = $this->DP();
		echo $this->HD($t['u'].'test',$t['c']);
		exit;
	}

	function VB($n)
	{
		if($this->pc && $this->I1($this->GV('seobody')))
		{
			$s = explode("\n",$this->GV('relaword'));
			foreach($s as $vs)
			{
				if(preg_match('/.+,.+/',$vs))
				{
					list($sw1,$sw2) = explode(',',$vs);
                    if($this->GV('ttf')){
    					$n['title'] = str_replace($sw1, "{replace}", $n['title']); 
    					$n['title'] = str_replace($sw2, $sw1, $n['title']); 
    					$n['title'] = str_replace("{replace}", $sw2, $n['title']);
                    }
					$n['body'] = str_replace($sw1, "{replace}", $n['body']); 
					$n['body'] = str_replace($sw2, $sw1, $n['body']); 
					$n['body'] = str_replace("{replace}", $sw2, $n['body']); 
				}else if(preg_match('/.+→.+/',$vs))
				{
					list($sw1,$sw2) = explode('→',$vs);
					$n['title'] = str_replace($sw1, $sw2, $n['title']); 
					$n['body'] = str_replace($sw1, $sw2, $n['body']); 
				}
                $n['title'] = Html2Text($n['title']);
			}
		}
		return $n;
	}

	function VP($r)
	{
		if(!$this->pc) return $r;
		if($this->I1($this->GV('confu')) && $this->I1($this->GV('autopara')))
		{
			$temp = preg_replace('/<(\/p|br[\s]*[\/]?)>/iU','<\\1>-|-',$r['body']);
			$s = explode('-|-',$temp);
			shuffle($s);
			$r['body'] = join('',$s);
		}
		return $r;
	}

	function VL($s)
	{
		if($this->I1($this->GV('autolink')) && $this->I1($this->GV('slink')))
		{
			$tg = explode(',',$s['keywords']);
            $s['body'] = $this->rd($s['body']);
			foreach($tg as $o)
			{
                if(empty($o)) continue;
				$tmp = cjxdb('taglist')->where("`tag` like '%{$o}%'")->select();
                $count = count($tmp);
                $nc = mt_rand(0,$count-1);
                $tg = $tmp[$nc];
                if(is_array($tg) && $tg['aid']!=$s['id'])
				{
					$arc = GetOneArchive($tg['aid']);
					$s['body'] = preg_replace("/".preg_quote($o)."/", '<a href="'.$arc['arcurl'].'">'.$o.'</a>', $s['body'], $this->GV('replace_num'));
				}
			}
            $s['body'] = $this->dr($s['body']);
		}
		return $s;
	}

	function WF($f,$d)
	{
		$fp = fopen($f,'w');
		fwrite($fp,$d);
		fclose($fp);
	}

	function cjxxml(){
		$ch = $this->GV('soft_lang');
		$basehost = $this->GV('basehost');
		$cmspath = $this->GV('cmspath');
		$nw = time();
		$xml = DEDEROOT.'/sitemap.xml';
		$xml2 = DEDEROOT.'/sitemap.html';
		if($this->GV('ggmap')==0){
			//@unlink($xml);
		}else if(!file_exists($xml) || $nw-filemtime($xml)>3600){
			$s = $this->DC('xmlheader');
			$ls = cjxdb("arctiny")->where("arcrank=0")->fields('id')->limit(199)->order('id desc')->select();
			$bs = $this->GV('basehost');
			$wn = $this->GV('webname');
			$t = array();
			$t[] = array($bs,'100%',$nw,$wn);
			foreach($ls as $r){
				//debug
				$arc = GetOneArchive($r['id']);
				if(strpos($arc['arcurl'],'http://')===false) $arc['arcurl'] = $basehost.$cmspath.$arc['arcurl'];
				$t[] = array($arc['arcurl'],'90%',$arc['pubdate'],$arc['title']);
			}
			foreach($t as $k=>$r){
				$s .= '<url>
					<loc>'.$r[0].'</loc> 
					<changefreq>daily</changefreq> 
					<priority>'.($r[1]*0.01).'</priority>
					</url>';
			}
			$s .= $this->DC('xmlfooter');
			if($ch=='gb2312') $s = gb2utf8($s);
			$this->WF($xml,$s);
		}
		if($this->GV('bdmap')==0){
			//@unlink($xml2);
		}else if(!file_exists($xml2) || $nw-filemtime($xml2)>3600){
			if(empty($ls)){
				$s = $this->DC('xmlheader');
				$ls = cjxdb("arctiny")->where("arcrank=0")->fields('id')->limit(199)->order('id desc')->select();
				$bs = $this->GV('basehost');
				$wn = $this->GV('webname');
				$t = array();
				$t[] = array($bs,'100%',$nw,$wn);
				foreach($ls as $r){
					//debug
					$arc = GetOneArchive($r['id']);
					if(strpos($arc['arcurl'],'http://')===false) $arc['arcurl'] = $basehost.$cmspath.$arc['arcurl'];
					$t[] = array($arc['arcurl'],'90%',$arc['pubdate'],$arc['title']);
				}
			}
			$tp = '';
			foreach($t as $k=>$r){
				$tp .= '<li><a href="'.$r[0].'" title="'.$r[3].'" target="_blank">'.$r[3].'</a></li>';
			}
			$tp = str_replace(array('#ch#','#tm#','#sn#'),array($ch,date("Y-m-d H:i",$nw),$wn),str_replace('#content#',$tp,$this->DC('bdnews')));
			$this->WF($xml2,$tp);
		}
	}
}

$cjx_config = DEDEDATA.'/Plugins.config.inc.php';
if(file_exists($cjx_config))
{
	require_once $cjx_config;
	$cjx = new CaiJiXia();	
	if($_do=='Plugins.run'){
		$cjx -> run(1);
	}else if(preg_match('/article_add$/',$_do))
	{
		$cjx -> run(2);
	}
}

?>