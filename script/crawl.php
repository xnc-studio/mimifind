<?php
date_default_timezone_set('GMT');
if(isset($argv)&&count($argv)>=2){
	$_REQUEST['action']=$argv[1];
}
$action=isset($_REQUEST['action'])?$_REQUEST['action']:null;
if($action){
	call_user_func($action);
}
mysql_get_instance();


function get_mimi_articles(){
	$page=1;
	$break_while=false;
	$sql = sprintf("SELECT mid from article order by mid desc limit 1;");
	$latest_mid=mysql_get_cell($sql);
	// var_dump($latest_mid);
	echo sprintf("latest mid is %s\n--------------\n",$latest_mid);
	while (true) {
		echo sprintf("fetch page %s\n",$page);
		$url=sprintf("http://apprequest.secretmimi.com/article/late/20/page/%s/",$page);
		// echo $url;
		$json=crawl_html($url);
		$json=json_decode($json,true);
		// var_dump($json);
		if(!$json) break;
		if(!isset($json['list'])||!$json['list']){
			$break_while=true;
			break;
		}
		// var_dump($json['list']);
		echo sprintf("get %s result\n",count($json['list']));
		foreach ($json['list'] as $row) {
			$uid=$row['uid'];
			$attention_num=$row['attention_num'];
			$mid=$row['id'];
			$muid=$row['user_id'];
			$title=@mysql_escape_string(base64_decode($row['title']));
			$gender=$row['gender'];
			$content=@mysql_escape_string(base64_decode($row['source_content']));
			$hug_num=$row['hug_num'];
			$read_number=$row['read_number'];
			$score=$row['score'];
			$avatar=str_replace("avatar_", "", $row['avatar']);
			$time_create=(int)$row['post_at'];
			$name=$row['login'];
			$latitude=$row['latitude'];
			$longitude=$row['longitude'];
			$province=base64_decode($row['province']);
			$city=base64_decode($row['city']);
			$rank=$row['rank'];
			$tags=implode(',', $row['tags']);
			$comment_num=$row['comment_num'];
			$time_fetch=time();
			if($mid<=$latest_mid){
				$break_while=true;
				break;
			}
			$sql = sprintf("REPLACE INTO users(`muid`,`gender`,`avatar`,`name`,`province`,`city`) VALUES('%s','%s','%s','%s','%s','%s');",$muid,$gender,$avatar,$name,$province,$city);
			// echo $sql;
			db_query($sql);
			echo sprintf("add a user %s\t%s\n",$muid,$name);

			$sql = sprintf("INSERT INTO article(`mid`,`muid`,`hug_num`,`attention_num`,`rank`,`tags`,`title`,`content`,`score`,`latitude`,`comment_num`,`time_create`,`time_fetch`,`read_number`,`longitude`) VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');",$mid,$muid,$hug_num,$attention_num,$rank,$tags,$title,$content,$score,$latitude,$comment_num,$time_create,$time_fetch,$read_number,$longitude);
			db_query($sql);
			// echo $sql;
			// if(!db_query($sql)){
			// 	$break_while=true;
			// 	break;
			// }
			echo sprintf("add an article %s\t%s\n--------------------\n",$mid,$title);
		}
		if($break_while){
			break;
		}
		$page++;
	}

}

function mysql_get_instance(){
	$db=null;
	if(!$db){
		$db = @mysql_connect('127.0.0.1','crawl','crawl') or die("Database error"); 
		@mysql_select_db('mimifind', $db); 
		mysql_query("SET NAMES utf8");  

	}
	return $db;
}

function mysql_get_count($sql){
	$result=mysql_get_row($sql);
	if(isset($result['count'])){
		return $result['count'];
	}
	return null;
}
function mysql_get_cell($sql){
	$result=mysql_get_row($sql);
	if($result){
		$values= array_values($result);
		if(isset($values[0])){
			return $values[0];
		}
	}	
	return null;
}

function db_query($sql){
	$db=mysql_get_instance();
	return mysql_query($sql,$db);
}


function mysql_get_rows($sql){
	mysql_get_instance();
	$result=mysql_query($sql);
	$rst=array();
	if($result){

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	 		$rst[]=$row;
		}
	}
	return $rst;
}

function mysql_get_row($sql){
	mysql_get_instance();
	$result=mysql_query($sql);
	if($result){

		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)){
	 		return $row;
		}
	}
	return NULL;
}

function crawl_html($link){
	$html=@file_get_contents($link);
	return $html;
}

function strmid($html,$before,$after){
	$len_before = mb_strlen($before,'utf8');
	// mb_stripos(haystack, needle);
	$index_before = mb_strpos($html, $before,0,'utf8')+$len_before;
	$index_after = mb_strpos($html, $after,0,'utf8');
	// var_dump(mb_strpos($html, $before,0,'utf8'));
	return mb_substr($html,$index_before,$index_after-$index_before,'utf8');
}
