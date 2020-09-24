<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
//require("../inc/language.inc");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");

	$tmp_db = new mysql();
	$db = new mysql('simserver');
	

	$current_page = 1;
	if(isset($_GET['current_page'])){
		$current_page = $_GET['current_page'];
	}
	
	/* filter begin */
	$sort = '';
	if(isset($_GET['sort']))
		$sort = $_GET['sort'];

	$order = '';
	if(isset($_GET['order']))
		$order = $_GET['order'];

	$sort_sql = 'order by n_sim_balance desc';
	$port_class='sort';
	$phone_number_class='sort';
	$balance_class='sort';
	$time_class='sort';
	
	switch($sort) {
		case 'port':
			if($order == 'des') {
				$port_class = 'asc';
				$sort_sql = 'order by ob_sb_seri asc,ob_sb_link_bank_nbr asc,ob_sb_link_sim_nbr asc';
			} else {
				$port_class = 'des';
				$sort_sql = 'order by ob_sb_seri desc,ob_sb_link_bank_nbr desc,ob_sb_link_sim_nbr desc';
			}
			break;
		case 'phone_number':
			if($order == 'des') {
				$phone_number_class = 'asc';
				$sort_sql = 'order by v_sim_phone_number asc';
			} else {
				$phone_number_class	= 'des';
				$sort_sql = 'order by v_sim_phone_number desc';
			}
			break;
		case 'balance':
			if($order == 'des') {
				$balance_class = 'asc';
				$sort_sql = 'order by n_sim_balance asc';
			} else {
				$balance_class = 'des';
				$sort_sql = 'order by n_sim_balance desc';
			}
			break;
		case 'time':
			if($order == 'des') {
				$time_class = 'asc';
				$sort_sql = 'order by v_sms_recv_time asc';
			} else {
				$time_class = 'des';
				$sort_sql = 'order by v_sms_recv_time desc';
			}
			break;
	}
	/* filter end */
	
	/* database begin */
	$line_counts = 20;
	
	$results = $tmp_db->Get('tb_simbank_link_info',"*",'where n_sim_balance != -95.27 or v_sim_phone_number is not null '.$sort_sql);

	
	$i = 0;
	while($sim = mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($sim['v_sim_phone_number'] == '' && $sim['n_sim_balance'] == -95.27){continue;}
		$sim_line[$i]['v_sim_phone_number'] = $sim['v_sim_phone_number'];
		if($sim['n_sim_balance'] == -95.27){
			$sim_line[$i]['n_sim_balance'] = '';
		}else{
			$sim_line[$i]['n_sim_balance'] = $sim['n_sim_balance'];
		}
		
		$sim_line[$i]['ob_sb_seri'] = $sim['ob_sb_seri'];
		$sim_line[$i]['ob_sb_link_bank_nbr'] = $sim['ob_sb_link_bank_nbr'];
		$sim_line[$i]['ob_sb_link_sim_nbr'] = $sim['ob_sb_link_sim_nbr'];
		$sim_line[$i]['v_sim_update_time'] = $sim['v_sms_recv_time'];
		$i++;
	}
	$page_num = ceil(count($sim_line)/$line_counts);
	/* database end */
?>
<form id="mainform" enctype="multipart/form-data" action="<?php get_self();?>" method="post">
	<table width="100%" class="tsort">
		<tbody>
			<tr>
				<th class="<?php echo $port_class; ?>" onclick="sort_click(this,'port')" ><?php echo language('Port'); ?></th>
				<th class="<?php echo $phone_number_class; ?>" onclick="sort_click(this,'phone_number')" ><?php echo language('Phone Number'); ?></th>
				<th class="<?php echo $balance_class; ?>" onclick="sort_click(this,'balance')" ><?php echo language('Balance'); ?></th>
				<th class="<?php echo $time_class; ?>" onclick="sort_click(this,'time')" ><?php echo language('Time'); ?></th>
			</tr>
			<?php
			for($i=$line_counts*($current_page-1);$i<$line_counts*($current_page-1)+$line_counts;$i++){
				if(($sim_line[$i]['v_sim_phone_number'] != '' && isset($sim_line[$i]['v_sim_phone_number'])) || ($sim_line[$i]['n_sim_balance'] != '' && isset($sim_line[$i]['n_sim_balance']))){
					echo "<tr><td>".$sim_line[$i]['ob_sb_seri'].'-'.(8*$sim_line[$i]['ob_sb_link_bank_nbr']+$sim_line[$i]['ob_sb_link_sim_nbr']+1)."</td><td>".$sim_line[$i]['v_sim_phone_number']."</td><td>".$sim_line[$i]['n_sim_balance']."</td><td>".$sim_line[$i]['v_sim_update_time']."</td></tr>";
				}
			}
			?>
		</tbody>
	</table>
	<br>
	<div id="newline"></div>
	<?php if($page_num>1){ ?>
	<div class="pg">
		<a title="Previous page" style="cursor:pointer;" class="prev" onclick="getpage(<?php echo ($current_page-1);?>)"></a>
		<?php 
		for($i=1;$i<=$page_num;$i++){
			if($i==$current_page){
				echo "<strong>".$current_page."</strong>";
			}else{
				echo "<a onclick='getpage(".$i.")' style='cursor:pointer;'>".$i."</a>";
			}
		}
		?>
		<?php 
		if(($current_page+1)<=$page_num){
		?>
		<a title="Next page" style="cursor:pointer;" class="nxt" onclick="getpage(<?php echo ($current_page+1);?>)"></a>
		<?php }?>
		<label>
			<input type="text" id="input_page" name="page" value="<?php echo $current_page;?>" size="2" class="px" title="Please input your page number, and press [Enter] to skip to.">
			<span title="total pages: <?php echo $counts;?>"> / <?php echo $page_num;?></span>
		</label>
		<a title="goto input page" style="cursor:pointer;" onclick="getpage_go(document.getElementById('input_page').value);"><?php echo language('go'); ?></a>
	</div>
	<?php }?>
	<div style="clear:both;"></div>

	<input type="submit" value="<?php echo language('Export'); ?>" onclick="document.getElementById('send').value='export'">
	<input type="submit" value="<?php echo language('Clean Up'); ?>" onclick="document.getElementById('send').value='clean';return clean_up();">
	
	<input type="hidden" name="send" value="" id="send" />
	<input type="hidden" id="current_page_flag" name="current_page" value="<?php echo $current_page;?>" />
	<input type="hidden" id="sort_flag" name="sort" value="<?php echo $sort;?>" />
	<input type="hidden" id="order_flag" name="order" value="<?php echo $order;?>" />
</form>
<script>
function getpage(page)
{
	var url = '<?php echo get_self();?>'+'?';
	if(page == 0){
		page = document.getElementById("current_page_flag").value;
	}
	if(page != ''){
		url += "current_page="+page+'&';
	}
	
	var sort = document.getElementById("sort_flag").value;
	if(sort != '')
		url += "sort="+sort+"&";
	var order = document.getElementById("order_flag").value;
	if(order != '')
		url += "order="+order+"&";
	
	window.location.href = url;
}

function sort_click(obj, type)
{
	document.getElementById("sort_flag").value=type;
	if(document.getElementById("order_flag").value == "des")
		document.getElementById("order_flag").value = "asc";
	else
		document.getElementById("order_flag").value = "des";
	getpage(0);
}

function getpage_go(page)
{
	var url = '<?php echo get_self();?>'+'?';
	if(page == 0){
		page = document.getElementById("current_page_flag").value;
	}
	if(page != ''){
		url += "current_page="+page;
	}
	
	if(page_check()){
		alert("<?php echo language("Page does not exist!"); ?>");
		return false;
	}
	window.location.href = url;
}
function page_check(){
	var input_val = $("#input_page").val();
	var page_num = <?php echo $page_num;?>;
	if(input_val>page_num){
		return true;
	}
	return false;
}
function clean_up(){
	var ret = confirm("<?php echo language('clean up tip', 'Are you sure you want to clean up all the data?');?>");
	return ret;
}
</script>
<?php
function export_excel()
{
	global $tmp_db;
	global $sort_sql;
	
	$output_name = 'PhoneBalance.xls';
	$all_time = trim(`date "+%Y:%m:%d:%H:%M:%S"`);
	$item = explode(':', $all_time, 6);
	if(isset($item[5])) {
		$year = $item[0];
		$month = $item[1];
		$date = $item[2];
		$hour = $item[3];
		$minute = $item[4];
		$second = $item[5];
		$output_name = "PhoneBalance-$year-$month-$date-$hour-$minute-$second.txt";
	}

	ob_clean();
	flush();
	header("Content-type: application/octet-stream"); 
	header("Accept-Ranges: bytes"); 
	header("Content-Disposition:attachment;filename=$output_name");

	//Add UTF-8 BOM
	$utf8_bom = pack("C*",0xEF,0xBB,0xBF);
	echo $utf8_bom;

	$results = $tmp_db->Get('tb_simbank_link_info',"*",'where n_sim_balance != -95.27 or v_sim_phone_number is not null '.$sort_sql);

	while($sim = mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($sim['v_sim_phone_number'] == '' && $sim['n_sim_balance'] == -95.27){continue;}
		$_port = $sim['ob_sb_seri'].'-'.$sim['ob_sb_link_bank_nbr'].'-'.$sim['ob_sb_link_sim_nbr'];
		$_number = $sim['v_sim_phone_number'];
		if($sim['n_sim_balance'] == -95.27){
			$_balance = '';
		}else{
			$_balance = $sim['n_sim_balance'];
		}
		$_time = $sim['v_sms_recv_time'];
		echo "Port:".$_port." \t\tPhone Number:".$_number." \t\tBalance:".$_balance." \t\tTime:".$_time."\n";
	}
	if($results === false){
		echo language("Database busy tip","Database is busy now. Please try later.");
	}
	exit(0);
}

function clean_up(){
	global $db;
	global $tmp_db;
	
	$results = $tmp_db->Set('tb_sim_info',"n_sim_balance=-95.27,v_sim_phone_number='',v_sim_update_time=''");
	$tmp_db->Set('tb_simbank_link_info',"n_sim_balance=-95.27,v_sim_phone_number=''");
	
	$db->Set('tb_sim_info',"n_sim_balance=-95.27,v_sim_phone_number='',v_sim_update_time=''");
	if($results == false){
		echo language("Database busy tip","Database is busy now. Please try later.");
	}
	header('Location: '.$_SERVER["PHP_SELF"]);
}

if(isset($_POST) && $_POST['send'] == 'export'){
	export_excel();
}else if(isset($_POST) && $_POST['send'] == 'clean'){
	clean_up();
}

require("/opt/simbank/www/inc/boot.inc");
?>
