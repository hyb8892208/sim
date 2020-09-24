<?php
/*
※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※
【文件名】: mySql
【作 用】: mysql数据库操作类
【作 者】: Riyan
【版 本】: version 1.6
【修改日期】: 2009/09/10
※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※
*/
class mysql{
private $host;               // 数据库主机
private $user;               // 数据库用户名
private $pwd;                // 数据库密码
private $db;                 // 数据库名
private $conn;               // 数据库连接标识
private $result;             // 执行query命令的结果资源标识
private $sql;              // sql执行语句
private $row;                // 返回的条目数
private $char;               // 数据库编码，GBK,UTF8,gb2312

private $error_log = true; // 是否开启错误记录
private $show_error = true; // 测试阶段，显示所有错误,具有安全隐患,默认关闭
private $is_error   = false; // 发现错误是否立即终止,默认true,建议不启用，因为当有问题时用户什么也看不到是很苦恼的

private $pageNo = 1;        // 当前页
private $pageAll = 0;        // 总页数
private $rsAll   = 0;        // 总记录
private $pageSize;           // 每页显示记录条数

/*---------------------------------------------------------------------------------
   函数名：__construct($host,$user,$pwd,$database,$conn,$char)
   作 用：构造函数
   参 数：$host (数据库主机)
    $user (数据库用户名)
    $pwd (数据库密码)
          $db   (数据库名)
          $conn (数据库连接标识)
          $char (数据库编码)
   返回值：无 
   实 例：无
-----------------------------------------------------------------------------------*/
public function __construct($host='127.0.0.1',$user='root',$pwd='',$db='tmp_simserver',$char="gbk",$conn="conn"){
//public function __construct($host='172.16.6.103',$user='root',$pwd='',$db='simserver',$char="gbk",$conn="conn"){
     $this->host   = $host;
     $this->user   = $user;
     $this->pwd    = $pwd;
     $this->db     = $db;
     $this->conn   = $conn;
     $this->char   = $char;
     $this->connect();
}

// 数据库连接
private function connect(){
   if($this->conn=="pconn") $this->conn=mysql_pconnect($this->host,$this->user,$this->pwd); // 永久链接
   else $this->conn=mysql_connect($this->host,$this->user,$this->pwd); // 临时链接
   if ($this->show_error){
    if(!$this->conn) $this->show_error("无法连接服务器!");
    if(!$this->select_db($this->db)) $this->show_error("无法连接数据库：",$this->db);
   }
   mysql_query("SET NAMES $this->char");
   mysql_query("SET CHARACTER_SET_CLIENT='$this->char'"); 
   mysql_query("SET CHARACTER_SET_RESULTS='$this->char'");
}

// 数据库选择
public function select_db($db){return mysql_select_db($db, $this->conn);}

/*---------------------------------------------------------------------------------
   函数名：mysql_server($num)
   作 用：取得 MySQL 服务器信息
   参 数：$num(信息值)
   返回值：字符串
   实 例：无
-----------------------------------------------------------------------------------*/
public function mysql_server($num=''){
   switch ($num){
    case 1:
     return mysql_get_server_info(); // 取得 MySQL 服务器信息
     break;
    case 2:
     return mysql_get_host_info();   // 取得 MySQL 主机信息
     break;
    case 3:
     return mysql_get_client_info(); // 取得 MySQL 客户端信息
     break;
    case 4:
     return mysql_get_proto_info(); // 取得 MySQL 协议信息
     break;
    default:
     return mysql_get_client_info(); // 取得 MySQL 版本信息
   }
}

/*---------------------------------------------------------------------------------
   函数名：query($sql)
   作 用：数据库执行语句，可执行查询添加修改删除等任何sql语句
   参 数：$sql(sql语句)
   返回值：布尔
   实 例：无
-----------------------------------------------------------------------------------*/
public function query($sql){ 
	if($sql== "") {
		$this->show_error("sql语句错误：","sql查询语句为空");
	}
	$this->sql=$sql;
	$result=mysql_query($this->sql,$this->conn);
	if(!$result){
		if($this->show_error) { 
			$this->show_error("sql语句错误：",$this->sql); // 调试中使用，sql语句出错时会自动打印出来
		}
	}
	else{
		$this->result=$result;
	}
    return $result;
}

/*---------------------------------------------------------------------------------
   函数名：create_db($database_name)
   作 用：创建添加新的数据库
   参 数：$db_name(数据库名称)
   返回值：无
   实 例：无
-----------------------------------------------------------------------------------*/
public function create_db($db_name){$this->query("CREATE DATABASE ".$db_name);}

// 查询服务器所有数据库
public function show_db(){
   $this->query("show databases");
   echo "现有数据库：".$this->num_rows()."<br />";
   $i=1;
   while($row=$this->fetch_array()){
    echo $i.".".$row[Database]."<br />";
    $i++;
   }
}

// 以数组形式返回主机中所有数据库名 
public function db_list() { 
   $rsPtr=mysql_list_dbs($this->conn); 
   $i=0; 
   $cnt=mysql_num_rows($rsPtr); 
   while($i < $cnt){ 
    $rs[]=mysql_db_name($rsPtr,$i); 
    $i++; 
   } 
   return $rs; 
}

// 查询数据库下所有的表
public function show_tables($db_name){
   $this->query("show tables");
   echo "现有数据库：".$amount=$this->num_rows();
   echo "<br />";
   $i=1;
   while($row=$this->fetch_array()){
    $Fileds="Tables_in_".$db_name;
    echo "$i $row[$Fileds]";
    echo "<br />";
    $i++;
   }
}

/*---------------------------------------------------------------------------------
   函数名：num_fields($Table)
   作 用：查询字段数量
   参 数：$Table(数据库表名)
   返回值：字符串
   实 例：$DB->num_fields("mydb")
-----------------------------------------------------------------------------------*/
public function num_fields($Table){
   $this->query("SELECT * FROM $Table");
   echo "<br />";
   echo "字段数：".$total=mysql_num_fields($this->result);
   echo "<pre>";
   for ($i=0; $i<$total; $i++){
    print_r(mysql_fetch_field($this->result,$i));
   }
   echo "</pre><br />";
}

/*---------------------------------------------------------------------------------
   函数名：Get($Table,$Fileds,$Condition)
   作 用：查询数据
   参 数：$Table(表名)
      $Fileds(字段名，默认为所有)
    $Condition(查询条件)
   返回值：无
   实 例：$DB->Get("mydb","user,password","order by id desc")
-----------------------------------------------------------------------------------*/
public function Get($Table,$Fileds="*",$Condition=""){
   if (!$Fileds || empty($Fileds)){$Fileds="*";}
   //return "SELECT $Fileds FROM $Table $Condition";
   return $this->query("SELECT $Fileds FROM $Table $Condition");
}

public function GetOnce($Table,$Fileds="*",$Condition=""){
   if (!$Fileds || empty($Fileds)){$Fileds="*";}
   return $this->query("SELECT $Fileds FROM $Table $Condition LIMIT 0,1");
}

/*---------------------------------------------------------------------------------
   函数名：Add($Table,$Fileds,$Value)
   作 用：添加数据
   参 数：$Table(表名)
      $Fileds(字段名)
    $Value(对应值)
   返回值：布尔
   实 例：$DB->Add("mydb","user,password","'admin','123456'")
-----------------------------------------------------------------------------------*/
public function Add($Table,$Fileds,$Value){
	//return "INSERT INTO $Table ($Fileds) VALUES ($Value)";
	return $this->query("INSERT INTO $Table ($Fileds) VALUES ($Value)");
}

/*---------------------------------------------------------------------------------
   函数名：Set($Table,$Content,$Condition)
   作 用：更改数据
   参 数：$Table(表名)
      $Content(数据内容)
    $Condition(更改条件)
   返回值：布尔
   实 例：$DB->Set("mydb","user='admin',password='123456'","where id=1")
-----------------------------------------------------------------------------------*/
public function Set($Table,$Content,$Condition="") {
	return $this->query("UPDATE $Table SET $Content $Condition");
}

/*---------------------------------------------------------------------------------
   函数名：Del($Table,$Condition)
   作 用：删除数据
   参 数：$Table(表名)
    $Condition(删除条件)
   返回值：布尔
   实 例：$DB->Del("mydb","id=1")
-----------------------------------------------------------------------------------*/
public function Del($Table,$Condition=""){return $this->query("DELETE FROM $Table WHERE $Condition");} 

// 取得结果数据
public function result(){return mysql_result($str);}

// 取得记录集,获取数组-索引和关联,使用$row['content']
public function fetch_array(){return mysql_fetch_array($this->result);}

// 获取关联数组,使用$row['字段名']
public function fetch_assoc(){return mysql_fetch_assoc($this->result);}    

// 获取数字索引数组,使用$row[0],$row[1],$row[2]
public function fetch_row(){return mysql_fetch_row($this->result);} 

// 获取对象数组,使用$row->content 
public function fetch_obj(){return mysql_fetch_object($this->result);} 

// 取得上一步 INSERT 操作产生的 ID
public function insert_id(){return mysql_insert_id();}

// 指向确定的一条数据记录
public function data_seek($id){
   if($id>0){$id=$id-1;}
   if(!@mysql_data_seek($this->result,$id)){$this->show_error("sql语句有误：", "指定的数据为空");}
   return $this->result; 
}

// 根据select查询结果计算结果集条数 
public function num_rows(){ 
   if($this->result==null){
   if($this->show_error){$this->show_error("sql语句错误","暂时为空，没有任何内容！");}   
   }else{
   return mysql_num_rows($this->result); 
   }
}

// 根据insert,update,delete执行结果取得影响行数 
public function affected_rows(){return mysql_affected_rows();}

// 获取地址栏参数
public function getQuery(){
   foreach($_GET as $key => $value){$list[] .= $key."=".$value;}
   return $list?"?".implode("&amp;", $list):"";
}

/*---------------------------------------------------------------------------------
   函数名：getPage($Table,$Fileds,$Condition,$pageSize)
   作 用：获取分页信息
   参 数：$Table(表名)
      $Fileds(字段)
    $Condition(查询条件)
    $pageSize(每页记录条数)
   返回值：字符串
   实 例：无
-----------------------------------------------------------------------------------*/
public function getPage($Table,$Fileds="*",$Condition="",$pageSize=10){
   $this->pageSize=intval($pageSize);
   if (isset($_GET["Page"]) && intval($_GET["Page"])){$this->pageNo=intval($_GET["Page"]);}
   if (empty($Fileds)){$Fileds="*";}
   $this->Get($Table,"*",$Condition);
   $this->rsAll=$this->num_rows();
   if ($this->rsAll>0){
    $this->pageAll=ceil($this->rsAll/$this->pageSize);
    if ($this->pageNo<1){$this->pageNo =1;}
    if ($this->pageNo>$this->pageAll){$this->pageNo=$this->pageAll;}
    $sql="SELECT $Fileds FROM $Table $Condition".$this->limit(true);
    $this->query($sql);
   }
   return $this->rsAll;
}

// 分页limit语句
public function limit($str=false){
   $n=($this->pageNo-1)*$this->pageSize;
   return $str ? " LIMIT ".$n.",".$this->pageSize:$n;
}

// 显示分页
public function showPage(){
   $pageNav="";
   if($this->pageAll>1){
    $pageNav.="<div class=\"page\">".chr(10);
    unset($_GET["Page"]);
    $url=$this->getQuery();
    $url=empty($url)?"?Page=":$url."&amp;Page=";
    if ($this->pageNo>1){
     $pageNav.="<a href=\"".$url."1\">首页</a>".chr(10);
     $pageNav.="<a href=\"".$url.($this->pageNo-1)."\">上页</a>".chr(10);
    }
    if ($this->pageAll<5){
     $n=array(1,$this->pageAll);
    }elseif($this->pageNo>6){
     $n=array(($this->pageNo)-2,($this->pageNo)+2);
     if ($n[1]>$this->pageAll) $n=array(($this->pageAll)-4,$this->pageAll);
    }else{
     $n=array(1,5);
    }
    for ($n[0];$n[0]<=$n[1];$n[0]++){
     $stat = $this->pageNo==$n[0] ? " class=\"curr\"":" href=\"".$url.$n[0]."\"";
     $pageNav.="<a title=\"第".$n[0]."页\"".$stat.">".$n[0]."</a>".chr(10);
    }
    if ($this->pageNo<$this->pageAll){
     $pageNav.="<a href=\"".$url.($this->pageNo+1)."\">下页</a>".chr(10);
     $pageNav.="<a href=\"".$url.$this->pageAll."\">尾页</a>".chr(10);
    }
    $pageNav.="[页次:<b>".$this->pageNo."</b>/".$this-> pageAll." 共<b>".$this->rsAll."</b>条记录 <b>".$this->pageSize."</b>条/页]</div>".chr(10);
   }
   return $pageNav;
}

// 获得客户端真实的IP地址
public function getip(){
   if ($_SERVER["HTTP_X_FORWARDED_FOR"]) $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
   elseif ($_SERVER["HTTP_CLIENT_IP"]) $ip = $_SERVER["HTTP_CLIENT_IP"];
   elseif ($_SERVER["REMOTE_ADDR"]) $ip = $_SERVER["REMOTE_ADDR"];
   elseif (getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR");
   elseif (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP");
   elseif (getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR");
   else $ip = NULL;
   return $ip;
}

/*---------------------------------------------------------------------------------
   函数名：show_error($message,$sql)
   作 用：输出显示错误信息
   参 数：$message(错误信息)
      $sql(sql语句)
   返回值：字符串
   实 例：无
-----------------------------------------------------------------------------------*/
public function show_error($message="",$sql=""){
   if(!$sql){
    echo "<font color='red'>".$message."</font>";
   }else{
    echo "<fieldset>";
    echo "<legend>错误信息提示</legend>";
    echo "<div style='font-size:14px; font-family:Verdana, Arial, Helvetica, sans-serif;'>错误原因：".mysql_error()."<br /><br />";
    echo "<div style='line-height:20px; background:#F00; border:1px solid #F00; color:#FFF;'>".$message."</div>";
    echo "<font color='red'>".$sql."</font><br />";
    if($this->error_log){
     $ip=$this->getip();
     $message=$message."\r\n".$this->sql."\r\n客户IP:".$ip."\r\n时间 :".date("Y-m-d H:i:s")."\r\n\r\n";
     $filename=date("Y-m-d").".txt";
     $file_path="./error/".$filename;
     if(!file_exists("./error")){
      if(!mkdir("./error",0777)){ // 建立文件夹，默认的mode是0777，意味着最大可能的访问权
       die("upload files directory does not exist and creation failed");
      }
     }
     if(!file_exists($file_path)){fopen($file_path,"w+");} // 建立txt文件
     if (is_writable($file_path)){ // 确定文件存在并且可写
      if (!$handle=fopen($file_path, 'a')){ // 使用添加模式打开$filename，文件指针将会在文件的开头
       echo "__不能打开文件$filename";
       exit;
      }elseif (!fwrite($handle, $message)){ // 将错误信息写入到文件中。
       echo "__不能写入到文件$filename";
       exit;
      }else{
       echo "__错误记录被保存到文件$filename!";
       fclose($handle); // 关闭文件
      }
     }else{
      echo "__文件$filename不可写";
     }
    }
    if($this->is_error) exit;
   }
   echo "</div></fieldset><br />";
}

/*---------------------------------------------------------------------------------
   函数名：drop($table)
   作 用：删除表(请慎用,无法恢复)
   参 数：$table(要删除的表名，默认为所有)
   返回值：无
   实 例：$DB->drop("mydb")
-----------------------------------------------------------------------------------*/
public function drop($table){
   if ($table){
    $this->query("DROP TABLE IF EXISTS $table");
   }else{
    $rst=$this->query("SHOW TABLES"); 
    while ($row=mysql_fetch_array($rst)){
     $this->query("DROP TABLE IF EXISTS $row[0]");
    }
   }
}

// 释放结果集
public function free(){@mysql_free_result($this->result);}

// 关闭数据库
public function close(){mysql_close($this->conn);}

// 析构函数，自动关闭数据库,垃圾回收机制
public function __destruct(){
   $this->free();
   $this->close();
}
}
?>
