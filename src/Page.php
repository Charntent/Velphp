<?php
namespace vel;
// +----------------------------------------------------------------------
// | VelPHP [ WE CAN DO IT JUST Vel ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2018 http://velphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: VelGe <VelGe@gmail.com>
// +----------------------------------------------------------------------
use vel\Db;

class Page
{
    
    public $db,$sql,$pagesize,$start,$total,$gx,$countSql;
    
    public function __construct($sql,$pagesize,$gx = false,$countSql=""){

        $this->sql = $sql;
        $this->pagesize = $pagesize;
        $this->start = gpc('page')?intval(gpc('page'))*$this->pagesize:0;
        $this->db = Db::getInstance();
		$this->gx = $gx;
		$this->countSql = $countSql;
    }
    
    public function getlist(){

    	if($this->gx == false) {
			$sql = $this->sql." LIMIT {$this->start},{$this->pagesize}";
		}else{
			$sql = $this->sql;
		}

        return $this->db->select($sql);
    }
    
    public function getpage($type=''){
		global $catid,$is_html,$fengefu,$wl_lang;

        $nowurl = $this->nowrul();
		$nowurl = str_replace("index.php/",'',$nowurl);
		$nowurl = str_replace("index.php",'',$nowurl);
        $this->total = $this->gettotal();
        
        $nowpage = ceil($this->start/$this->pagesize);
        $totalpage = ceil($this->total/$this->pagesize);
        $startpage = $nowpage<=5?0:$nowpage-5;
        $endpage = $nowpage+5>$totalpage?$totalpage:$nowpage+5;
		
		$page_content = '';
		
		
		if($totalpage>1){
			$param = '';
			$_gp = array_merge($_GET, $_POST);
			foreach($_gp as $_k => $_v){
				if($_k!='page' && is_string($_v)){
					$param .= $_k.'='.stripslashes($_v).'&';
				}
		}

			$page_content = '';
			
			switch($type){
				
				case -1:
					if($this->start>0) $page_content .= '<a href="javascript:;" onClick="getreview('.$type.')">'.$wl_lang['first_page'].'</a>';
					for($i=$startpage;$i<$endpage;$i++){
						if($i==$nowpage){
							$page_content .= '<a href="javascript:;" style="background:#CCC">'.($i+1).'</a>';
						}else if($i==0){
							$page_content .= '<a href="javascript:;" onClick="getreview('.$type.',0)">'.($i+1).'</a>';
						}else{
							$page_content .= '<a href="javascript:;" onClick="getreview('.$type.','.$i.')">'.($i+1).'</a>';
						}
					}
					if($totalpage>1 && ($nowpage+1)!=$totalpage) $page .= '<a href="javascript:;" onClick="getreview('.$type.','.($totalpage-1).')">'.$wl_lang['first_page'].'</a>';
				 
		    
			break;
			case -2:
			    if($this->start>0) $page_content .= '<a href="javascript:;" class="case-js"  data-href="'.$nowurl.'?'.trim($param,'&').'">'.$wl_lang['first_page'].'</a>';
				for($i=$startpage;$i<$endpage;$i++){
					if($i==$nowpage){
						$page_content .= '<a href="javascript:;" style="background:#CCC">'.($i+1).'</a>';
					}else if($i==0){
						$page .= '<a href="javascript:;" class="case-js" data-href="'.$nowurl.'?'.trim($param,'&').'">'.($i+1).'</a>';
					}else{
						$page_content .= '<a href="javascript:;" class="case-js" data-href="'.$nowurl.'?'.$param.'page='.$i.'">'.($i+1).'</a>';
					}
				}
				if($totalpage>1 && ($nowpage+1)!=$totalpage) $page_content .= '<a href="javascript:;"   class="case-js" data-href="'.$nowurl.'?'.$param.'page='.($totalpage-1).'">'.$wl_lang['last_page'].'</a>';
			  
		    break;

			case 'amazeui':

				if($this->start>0) $page_content .= '<li><a  class="case-js"  href="'.$nowurl.'?'.trim($param,'&').'">'.$wl_lang['first_page'].'</a></li>';

				for($i=$startpage;$i<$endpage;$i++){
					if($i==$nowpage){
						$page_content .= '<li class="am-active"><a href="javascript:;">'.($i+1).'</a></li>';
					}else if($i==0){
						$page_content .= '<li> <a  class="case-js" href="'.$nowurl.'?'.trim($param,'&').'">'.($i+1).'</a></li>';
					}else{
						$page_content .= '<li> <a class="case-js" href="'.$nowurl.'?'.$param.'page='.$i.'">'.($i+1).'</a></li>';
					}
				}
				if($totalpage>1 && ($nowpage+1)!=$totalpage) $page_content .= '<li><a   class="case-js" href="'.$nowurl.'?'.$param.'page='.($totalpage-1).'">'.$wl_lang['last_page'].'</a></li>';
			
			break;
			case -5:
				//if($this->start>0) $page_content .= '<a href="'.$nowurl.'?'.trim($param,'&').'">'.$wl_lang['first_page'].'</a>';
				for($i=$startpage;$i<$endpage;$i++){
					if($i==$nowpage){
						$page_content .= '<a href="javascript:;" class="active">'.($i+1).'</a>';
					}else if($i==0){
						$page_content .= '<a href="'.$nowurl.'?'.trim($param,'&').'">'.($i+1).'</a>';
					}else{
						$page_content .= '<a href="'.$nowurl.'?'.$param.'page='.$i.'">'.($i+1).'</a>';
					}
				}
				if($nowpage<$endpage-1)
				$page_content .='<a href="'.$nowurl.'?'.$param.'page='.($nowpage+1).'">&nbsp;<i class="fa fa-angle-right"></i></a>';
				//if($totalpage>1 && ($nowpage+1)!=$totalpage) $page_content .= '<a href="'.$nowurl.'?'.$param.'page='.($totalpage-1).'">'.$wl_lang['last_page'].'</a>';
				
			break;
			default:
				if($is_html){
					if($this->start>0) $page_content .= '<li><a class="pageon" href="'.$nowurl.'.html">'.$wl_lang['first_page'].'</a></li>';
					for($i=$startpage;$i<$endpage;$i++){
						if($i==$nowpage){
							$page_content .= '<li class="on"><a href="javascript:;">'.($i+1).'</a></li>';
						}elseif($i==0){
							$page_content .= '<li><a href="'.$nowurl.'.html">'.($i+1).'</a></li>';
						}else{
							$page_content .= '<li><a href="'.$nowurl.'_'.$i.'.html">'.($i+1).'</a></li>';
						}
					}
					if($totalpage>1 && ($nowpage+1)!=$totalpage) $page_content .= '<li><a class="pagedown" href="'.$nowurl.'_'.($totalpage-1).'.html">'.$wl_lang['last_page'].'</a></li>';
				}else{
					//非静态的默认页
					if($this->start>0) $page_content .= '<li><a class="pageon" href="'.$nowurl.'?'.trim($param,'&').'">'.$wl_lang['first_page'].'</a></li>';
					for($i=$startpage;$i<$endpage;$i++){
						if($i==$nowpage){
							$page_content .= '<li class="on"><a href="javascript:;">'.($i+1).'</a></li>';
						}else if($i==0){
							$page_content .= '<li><a href="'.$nowurl.($param?'?':'').trim($param,'&').'">'.($i+1).'</a></li>';
						}else{
							$page_content .= '<li><a href="'.$nowurl.'?'.$param.'page='.$i.'">'.($i+1).'</a></li>';
						}
					}
					if($totalpage>1 && ($nowpage+1)!=$totalpage) $page_content .= '<li><a class="pagedown" href="'.$nowurl.'?'.$param.'page='.($totalpage-1).'">'.$wl_lang['last_page'].'</a></li>';
				 }
			break;
	      }
		  
	  }
	    if($type==-6 || $type=='lxkt' || $type=='amazeui' ){
	       $page_content .= "<li><a href='javascript:;'>".$wl_lang['total_page']."{$totalpage}".$wl_lang['page']."{$this->total}".$wl_lang['tiao']."</a></li>";
		}else{
		   $page_content .= "<li><a href='javascript:;'>".$wl_lang['total_page']."{$totalpage}".$wl_lang['page']."{$this->total}".$wl_lang['tiao']."</a></li>";
		}
		
		return $page_content;
    }
    
    private function gettotal(){

    	if($this->countSql){
    		$sql = $this->countSql;
		}else{
			$sql = preg_replace("#SELECT[ \r\n\t](.*?)[ \r\n\t]FROM#is", 'SELECT COUNT(*) AS cc FROM', $this->sql,1);
			$sql = preg_replace("#ORDER[ \r\n\t]{1,}BY(.*)#is",'',$sql,1);
		}

        $rs = $this->db->select($sql);
		if(empty($rs)) return 0;
		$count = count($rs);

		if($count > 1 ){
			return $count;
		}else
        	return $rs[0]['cc'];
    }
    
    private function nowrul(){
		global $is_html;
		$catid = gpc('catid');
		if($is_html){
			$nowurl = convert_catid_to_url($catid);
			$nowurl = str_replace('.html','',$nowurl);
		}else{
			if(!empty($_SERVER["REQUEST_URI"])){
				$nowurl = $_SERVER["REQUEST_URI"];
				$nowurls = explode("?",$nowurl);
				$nowurl = $nowurls[0];
			}else{
				$nowurl = $_SERVER["PHP_SELF"];
			}
		}
        return $nowurl;
    }
    
}

?>

