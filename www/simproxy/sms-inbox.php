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
?>

<?php
$line_counts = 10;

$db = new mysql();
if(!$db) {
	require("/www/cgi-bin/inc/boot.inc");
	exit(0);
}

/********************************************************************/
if($_POST) {
	if(isset($_POST['send'])) {
		if($_POST['send'] == 'Delete') {
			if(isset($_POST['sms'])) {
				del_sms($_POST['sms']);
			}
		} else if($_POST['send'] == 'Clean Up') {
			del_all_sms();
		} else if($_POST['send'] == 'Export') {
			$export_to_excel = true;
		}
	}
}
/********************************************************************/

$current_page = 1;
if(isset($_GET['current_page']) && is_numeric($_GET['current_page'])){
	$current_page = $_GET['current_page'];
}
$page = ($current_page-1)*10;
$r = $db->Get('tb_simbank_link_info','*',"where v_sms_recv_msg<>''");
$n = $db->num_rows();
$page_num = ceil($n/$line_counts);

/* filter begin */
$port_filter = "";
if(isset($_GET['port_filter']))
	$port_filter =  $_GET['port_filter'];
else if(isset($_POST['port_filter']))
	$port_filter =  $_POST['port_filter'];
if($port_filter != ''){
	$port_filter_sql = str_replace('*', '%', $port_filter);
	if($filter_sql == '')
		$filter_sql = "where CONCAT(ob_sb_seri,'-',(8*ob_sb_link_bank_nbr+ob_sb_link_sim_nbr+1)) like '%".$port_filter_sql."%'";
	else
		$filter_sql .= " and CONCAT(ob_sb_seri,'-',(8*ob_sb_link_bank_nbr+ob_sb_link_sim_nbr+1)) like '%".$port_filter_sql."%'";
}

$message_filter = "";
if(isset($_GET['message_filter']))
	$message_filter = $_GET['message_filter'];
else if(isset($_POST['message_filter']))
	$message_filter = $_POST['message_filter'];
if($message_filter != ''){
	$message_array = preg_split("/[^0-9a-zA-Z\x{4e00}-\x{9fa5}]+/u", $message_filter);
	foreach($message_array as $message){
		if($message != ""){
			if($filter_sql == '')
				$filter_sql = "where v_sms_recv_msg like '%".$message."%'";
			else
				$filter_sql .= " and v_sms_recv_msg like '%".$message."%'";
		}
	}
}
if($filter_sql == ''){
	$filter_sql = "where v_sms_recv_msg<>''";
}else{
	$filter_sql .= "and v_sms_recv_msg<>''";
}
/* filter end */

function del_sms($sms_array)
{
	global $db;
	
	$str="";
	foreach($sms_array as $data) {
		$temp = explode("-",$data);
		$str .= "ob_sb_seri='$temp[0]' and ob_sb_link_bank_nbr=$temp[1] and ob_sb_link_sim_nbr=$temp[2] or ";
	}

	if($str != "") {
		$str = rtrim($str,"or ");
		$db->Set("tb_simbank_link_info","v_sms_recv_msg=''","where ".$str);
	}
}

function del_all_sms()
{
	global $db;
	
	$db->Set("tb_simbank_link_info","v_sms_recv_msg=''","");
}

function export_excel()
{
	global $db;
	global $__BRD_HEAD__;
	global $__GSM_HEAD__;
	global $filter_sql;
	global $sort_sql;	

	$output_name = 'smsinbox.xls';
	$all_time = trim(`date "+%Y:%m:%d:%H:%M:%S"`);
	$item = explode(':', $all_time, 6);
	if(isset($item[5])) {
		$year = $item[0];
		$month = $item[1];
		$date = $item[2];
		$hour = $item[3];
		$minute = $item[4];
		$second = $item[5];
		$output_name = "smsinbox-$year-$month-$date-$hour-$minute-$second.txt";
	}

	ob_clean();
	flush();
	header("Content-type: application/octet-stream"); 
	header("Accept-Ranges: bytes"); 
	header("Content-Disposition:attachment;filename=$output_name");

	//Add UTF-8 BOM
	$utf8_bom = pack("C*",0xEF,0xBB,0xBF);
	echo $utf8_bom;
	
	$results = $db->Get('tb_simbank_link_info','*',"where v_sms_recv_msg<>''");

	while($sim = mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$port = $sim['ob_sb_seri'].'-'.($sim['ob_sb_link_bank_nbr']*8+$sim['ob_sb_link_sim_nbr']+1);
		$time = $sim['v_sms_recv_time'];
		$message = $sim['v_sms_recv_msg'];
		echo $port."\t".$time."\t".$message."\n";
	}
	if($results === false){
		echo language("Database busy tip","Database is busy now. Please try later.");
	}
	exit(0);
}

if(isset($export_to_excel) && $export_to_excel) {
	export_excel();
}

?>

	<script type="text/javascript" src="/js/functions.js"></script>

	<link type="text/css" href="/css/jquery-ui-1.10.2.custom.all.css" rel="stylesheet" media="all"/>
	<link type="text/css" href="/css/jquery-ui-timepicker-addon.css" rel="stylesheet" media="all"/>

	<script type="text/javascript" src="/js/jquery-ui-1.10.2.custom.all.min.js"></script>
	<script type="text/javascript" src="/js/jquery-ui-timepicker-addon.js"></script>
	<script type="text/javascript" src="/js/jquery-ui-sliderAccess.js"></script>
	<script type="text/javascript">
	
	
	$(document).ready(function(){
		$("#do_filter").click(function(){
			$("#port_filter_flag").attr("value", $("#port_filter").attr("value"));
			$("#message_filter_flag").attr("value", $("#message_filter").attr("value"));
			getpage(1);
		});

		$("#clean_filter").click(function(){
			$("#port_filter").attr("value", "");
			$("#message_filter").attr("value", "");

			$("#current_page_flag").attr("value", 1);
			$("#port_filter_flag").attr("value", "");
			$("#message_filter_flag").attr("value", "");
			$("#sort_flag").attr("value", "");
			$("#order_flag").attr("value", "");

		});

		$("#input_page").keypress(function(e){
			if(window.event){ // IE
				var keynum = e.keyCode;
			}else if(e.which){ // Netscape/Firefox/Opera
				var keynum = e.which;
			}

			if(keynum == 13) {
				var page = $("#input_page").attr("value");
				$("#current_page_flag").attr("value", page);
				getpage(page);
			}
			
		});

		$("form").keypress(function(e){
			if(window.event){ // IE
				var keynum = e.keyCode;
			}else if(e.which){ // Netscape/Firefox/Opera
				var keynum = e.which;
			}

			if(keynum == 13) {
				return false;
			}
		});
	});

	function getpage(page)
	{
		var url = '<?php echo get_self();?>'+'?';
		if(page == 0){return false;}
		
		if(page != '')
			url += "current_page="+page+"&";

		var port_filter = document.getElementById('port_filter_flag').value;
		if(port_filter != '')
			url += 'port_filter='+encodeURIComponent(port_filter)+'&';

		var message_filter = document.getElementById("message_filter_flag").value;
		if(message_filter != '')
			url += "message_filter="+encodeURIComponent(message_filter)+"&";

		window.location.href = url;
	}
	
	function getpage_go(page)
	{
		var url = '<?php echo get_self();?>'+'?';
		if(page == 0){return false;}
		
		if(page != '')
			url += "current_page="+page+"&";
		
		var port_filter = document.getElementById('port_filter_flag').value;
		if(port_filter != '')
			url += 'port_filter='+encodeURIComponent(port_filter)+'&';

		var message_filter = document.getElementById("message_filter_flag").value;
		if(message_filter != '')
			url += "message_filter="+encodeURIComponent(message_filter)+"&";

		if(page_check()){
			alert("<?php echo language("No page tip","No page");?>"+page);
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
	</script>

	<!-- Filter table -->
	<table width="100%" class="tsort" style="table-layout:fixed;" >
		<tr>
			<th width='35px' class="nosort"></th>
			<th width='15%' class="nosort"><?php echo language('Port');?></th>
			<th width='40%' class="nosort"><?php echo language('Message Keywords');?></th>
		</tr>
		<tr>
			<td>
			</td>
			<td>
				<input type="text" id="port_filter" name="port_filter"  style='width:95%' value="<?php echo $port_filter;?>" >
			</td>
			<td>
				<input type="text" id="message_filter" name="message_filter"  style='width:98%' value="<?php echo $message_filter; ?>">
			</td>

		</tr>
	</table>

	<br/>
	<input type="button" id="do_filter" value="<?php echo language('Filter');?>"/> 
	<input type="button" id="clean_filter" value="<?php echo language('Clean Filter');?>"/> 
	<br/>
	
	<div id="newline"></div>

	<!-- SIMBANK Inbox -->	
	<form id="mainform" enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
<?php
		$results = $db->Get('tb_simbank_link_info','CONCAT(ob_sb_seri,"-",(8*ob_sb_link_bank_nbr+ob_sb_link_sim_nbr+1)) AS port, v_sms_recv_time, v_sms_recv_msg',"$filter_sql limit $page,$line_counts");
		$counts = $db->num_rows();
		if($counts > 0) {
			echo "<span style=\"font-weight:bold\">";echo language('Total Records');echo ": $n";echo "</span>";
?>
			<table width="100%" class="tsort">
				<tr>
					<th width='05%' class="nosort">
						<input type="Checkbox" name="selall" onclick="selectAll(this.checked,'sms[]')" />
					</th>
					<th width='15%' ><?php echo language('Port');?></th>
					<th width='25%' ><?php echo language('Time');?></th>
					<th width='40%' class="nosort"><?php echo language('Message');?></th>
				</tr>
				<?php
				if($results){
					while($sim = mysqli_fetch_array($results,MYSQLI_ASSOC)){
				?>
					<tr>
						<td> 
							<input type="Checkbox" name="sms[]" value="<?php echo $sim['port'];?>" />
						</td>
						<td> 
							<?php 
								echo $sim['port'];
							?>
						</td>
						<td> 
							<?php echo $sim['v_sms_recv_time'];?>
						</td>
						<td> 
							<textarea rows="1" style="width:99%;" readonly ><?php echo $sim['v_sms_recv_msg'];?></textarea>
						</td>
					</tr>
				<?php
					}
				}
				?>
			</table>
			<?php
		}
		?>
		<div id="newline"></div>
		<br/>
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
			<a title="goto input page" style="cursor:pointer;" onclick="getpage_go(document.getElementById('input_page').value);">go</a>
		</div>
		<?php } ?>
		<div style="clear:both;"></div>
		
		<?php
		if($counts > 0) {
		?>
			<div id="newline"></div>
			<br/>

			<input type="hidden" name="send" id="send" value="" />
			<input type="submit" value='<?php echo language('Delete');?>' 
				onclick="document.getElementById('send').value='Delete';return confirm('<?php echo language('Delete confirm','Are you sure to delete you selected ?');?>')"/> 
			<input type="submit" value='<?php echo language('Clean Up');?>' 
				onclick="document.getElementById('send').value='Clean Up';return confirm('<?php echo language('Clean Up smss tip','Are you sure to delete all smss ?');?>')"/> 
			<input type="submit" value='<?php echo language('Export');?>' 
				onclick="document.getElementById('send').value='Export';"/>

	<?php
		}
	?>

		<input type="hidden" id="current_page_flag" name="current_page" value="<?php echo $current_page;?>" />
		<input type="hidden" id="port_filter_flag" name="port_filter" value="<?php echo $port_filter;?>" />
		<input type="hidden" id="message_filter_flag" name="message_filter" value="<?php echo $message_filter;?>" />

	</form>

	<div id="newline"></div>

<script type="text/javascript">
$(document).ready(function(){
	$("textarea").each(function(){
		$(this).css("height", this.scrollHeight);
	});
});
</script>
<?php require("/opt/simbank/www/inc/boot.inc");?>

