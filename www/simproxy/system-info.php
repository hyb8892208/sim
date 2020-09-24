<?php
include_once("../inc/network_factory.inc");
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
//require("../inc/language.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");

?>

<?php
function printData()
{
	echo `df /data -h 2>/dev/null | awk 'NR==2{printf("%s/%s (%s)",$3,$2,$5)}' 2> /dev/null`;
}

function printCPUtemperature()
{
	$res= `sensors |grep "Core" | awk '{print $1, $2, $3, $4}' |xargs 2>/dev/null`;
	echo "${res}";
}

function printMem()
{
	$res= `cat /proc/meminfo 2>/dev/null  | awk '{if(NR==1){A=$2}if(NR==2){B=$2}}END{print (A-B)/A*100}' 2>/dev/null`;
	echo "${res}%";
}

function memory_clean()
{
	exec("echo 3 > /proc/sys/vm/drop_caches;echo 0 > /proc/sys/vm/drop_caches");
}

function get_seri_number(){
	$aql=new aql;
	$setok = $aql->set('basedir','/mnt/config/simbank/conf');
	if (!$setok) {
		echo $aql->get_error();
		return;
	}

	$db=$aql->query("select * from SimRdrSvr.conf" );
		
	if(isset($db['SimRdrSvr']['seri'])) {
		$simbank_serial_number = $db['SimRdrSvr']['seri'];
	} else {
		$simbank_serial_number = '';
	}
	
	return $simbank_serial_number;
}

if(isset($_GET['memory_clean']) && $_GET['memory_clean'] == 'yes') {
	memory_clean();
}


$hlock=lock_file("/opt/simbank/www/myimages/gw_info.conf");
$general_conf = $aql->query("select * from gw_info.conf where section='general'");
unlock_file($hlock);
$switch = $general_conf['general']['switch'];

if($switch == 'on'){
	$product_name = $general_conf['general']['product_name'];
	$address = $general_conf['general']['address'];
	$tel = $general_conf['general']['tel'];
	$fax = $general_conf['general']['fax'];
	$email = $general_conf['general']['email'];
	$web_site = $general_conf['general']['web_site'];
	$info_display = 'style="display:none;"';
}else{
	$product_name = get_model_name();
	$address = language('Contact Address content','Room 624, 6/F, TsingHua Information Port, QingQing Road, LongHua Street, LongHua District, ShenZhen');
	$tel = '+86-755-82535461';
	$fax = '+86-755-83823074';
	$email = 'support@openvox.cn';
	
	$language_type = get_language_type();
	if($language_type == "english"){
		$web_site = "http://www.openvox.cn";
	} else{
		$web_site = "http://openvox.com.cn";
	}
}


?>
	
	<table width="100%" class="tedit" >
	<?php if(!$cloudSimProxy){ ?>
		<tr>
			<th><?php echo language('Simbank Serial Number');?>:</th>
			<td>
				<?php echo get_seri_number();?>
			</td>
		</tr>
	<?php } ?>
		<tr <?php if($product_name == '') echo $info_display;?>>
			<th><?php echo language('Product Name');?>:</th>
			<td><?php echo $product_name; ?>
			
			</td>
		</tr>		
		<tr>
			<th><?php echo language('Software Version');?>:</th>
			<td><?php echo $__software_version; ?></td>
		</tr>
		<tr>
			<th><?php echo language('Build Time');?>:</th>
			<td><?php echo $__buil_time; ?></td>
		</tr>
		
		
		<tr>
			<th><?php echo language('Memory Usage');?>:</th>
			<td>
				<table><tr>
					<td style="border:0px;padding:0px"><?php printMem(); ?></td>
					<td style="border:0px;"><a href="<?php echo get_self() ?>?memory_clean=yes"><?php echo language('Memory Clean');?></a></td>
				</tr></table>
			</td>
		</tr>


<!--segcomdels--><!--teiqdels-->
<?php
	$product_customid = get_redis_value('product.sw.customid');
	if($product_customid != 'general'){
?>
		<tr <?php if($address == '') echo $info_display;?>>
			<th><?php echo language('Contact Address');?>:</th>
			<td>
				<?php echo $address;?>
			</td>
		</tr>
		
<!--segcomdele--><!--teiqdele-->
		
		<tr <?php if($tel == '') echo $info_display;?>>
			<th><?php echo language('Tel');?>:</th>
			<td><?php echo $tel;?></td>
		</tr>
		
<!--segcomdels--><!--teiqdels-->
		
		<tr <?php if($fax == '') echo $info_display;?>>
			<th><?php echo language('Fax');?>:</th>
			<td><?php echo $fax;?></td>
			
		</tr>
		
<!--segcomdele--><!--teiqdele-->
		
		<tr <?php if($email == '') echo $info_display;?>>
			<th><?php echo language('E-Mail');?>:</th>
			<td><a href="mailto:<?php echo $email;?>"><?php echo $email;?></a></td>
		</tr>
		
		
		<tr <?php if($web_site == '') echo $info_display;?>>
			<th><?php echo language('Web Site');?>:</th>
			<td><a href="<?php echo $web_site;?>" target="_top"><?php echo $web_site;?></a></td>
		</tr>
<?php } ?>
		
		<tr>
			<th><?php echo language('System Time');?>:</th>
			<td><span id="currenttime"></span></td>
		</tr>
		<tr>
			<th><?php echo language('System Uptime');?>:</th>
			<td><span id="uptime"></span></td>
		</tr>
	</table>

<?php

if(file_exists("/proc/uptime")) {
	$fh = fopen("/proc/uptime","r");
	$line = fgets($fh);
	fclose($fh);

	$start_time = substr($line,0,strpos($line,'.'));

	$all_time = trim(`date "+%Y:%m:%d:%H:%M:%S"`);
	$item = explode(':', $all_time, 6);
	if(isset($item[5])) {
		$year = $item[0];
		$month = $item[1];
		$date = $item[2];
		$hour = $item[3];
		$minute = $item[4];
		$second = $item[5];
	}
?>

<script type="text/javascript" language="javascript">
<!--
var c=0;
var Y=<?php echo $year?>;
var M=<?php echo $month?>;
var D=<?php echo $date?>;
var sec=<?php echo $hour*3600+$minute*60+$second?>;
function ctime() {
	sec++;
	H=Math.floor(sec/3600)%24;
	I=Math.floor(sec/60)%60;
	S=sec%60;
	if(S<10) S='0'+S;
	if(I<10) I='0'+I;
	if(H<10) H='0'+H;
	if (H=='00' & I=='00' & S=='00') D=D+1; //日进位
	if (M==2) { //判断是否为二月份******
		if (Y%4==0 && !Y%100==0 || Y%400==0) { //是闰年(二月有28天)
			if (D==30){
				M+=1;D=1;
			} //月份进位
		} else { //非闰年(二月有29天)
			if (D==29) {
				M+=1;D=1;
			} //月份进位
		}
	} else { //不是二月份的月份******
		if (M==4 || M==6 || M==9 || M==11) { //小月(30天)
			if (D==31) {
				M+=1;D=1;
			} //月份进位
		} else { //大月(31天)
			if (D==32) {
				M+=1;D=1;
			} //月份进位
		}
	}

	if (M==13) {
		Y+=1;M=1;
	} //年份进位

	//setInterval(ctime,1000);
	setTimeout("ctime()", 1000);
//	set_value('cur_time', 'ada');
//	document.system_time.cur_time.value = Y+'-'+M+'-'+D+' '+H+':'+I+':'+S;
	document.getElementById("currenttime").innerHTML = Y+'-'+M+'-'+D+' '+H+':'+I+':'+S;
}

var sec2=<?php echo $start_time?>;
function utime() {
	sec2++;
	day=Math.floor(sec2/3600/24);
	hour=Math.floor(sec2/3600)%24;
	minute=Math.floor(sec2/60)%60;
	second=sec2%60;
	if(hour<10) hour = '0' + hour;
	if(minute<10) minute = '0' + minute;
	if(second<10) second = '0' + second;

	//setInterval(utime,1000);
	document.getElementById("uptime").innerHTML = day+' days  '+hour+':'+minute+':'+second; //= hour+':'+minute+':'+second;
	setTimeout("utime()", 1000);
}

function onload_func()
{
	ctime();
	utime();
}

$(document).ready(function(){
	onload_func();
});
</script>

<?php
}
?>


<?php require("../inc/boot.inc");?>
