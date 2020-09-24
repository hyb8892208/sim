<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require("../inc/mysql_class.php");
include_once("../inc/wrcfg.inc");
include_once("../inc/aql.php");
include_once("../inc/define.inc");
include_once("../inc/language.inc");

$tmp_db = new mysql();
$db = new mysql('simserver');

function show_sim_group_rules(){
	global $tmp_db;
	
	$res = $tmp_db->Get('tb_gateway_info','ob_gw_seri,ob_grp_name','order by ob_gw_seri desc');

	$i = 0;
	while($info = mysqli_fetch_array($res,MYSQLI_ASSOC)){
		$group_info[$i]['ob_gw_seri'] = $info['ob_gw_seri'];
		$group_info[$i]['ob_grp_name'] = $info['ob_grp_name'];
		$i++;
	}
	
	$ret=get_conf('/mnt/config/simbank/conf/strategy.conf');

	$mode=$ret['strategy']['mode'];
	
?>
	<script type="text/javascript" src="/js/jquery.ibutton.js"></script> 
	<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
	<form id="mainform" enctype="multipart/form-data" action="<?php get_self();?>" method="post" >
		<div id="tab">
			<li class="tb1">&nbsp;</li>
			<li class="tbg"><?php echo language('Sim Distribution');?></li>
			<li class="tb2">&nbsp;</li>
		</div>
		
		<table width="100%" class="tedit" >
			<tr>
				<th style="width:209px;">
					<div class="helptooltips">
						<?php echo language('Schedule mode');?>:
						<span class="showhelp">
							<?php echo language('Schedule mode help', 'After opening, the SIM card group will be used for distribution.');?>
						</span>
					</div>
				</th>
				<td>
					<input type="checkbox" name="schedule" id="schedule"
					<?php if($mode == 1 || $mode == 3 || $mode == 5 || $mode == 7 || $mode == 9 || $mode == 11 || $mode == 13 || $mode == 15) echo 'checked';?>
					/>
				</td>
			</tr>
		</table>
		
		<div id="newline"></div>
		
		<input type="submit" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Schedule Save'" />
		
		<div id="newline"></div>
	
		<table width="100%" class="tsort">
			<tbody>
				<tr>
					<th><?php echo language('Gateway Serial Number');?></th>
					<th><?php echo language('Group Name'); ?></th>
					<th width="40px"><?php echo language('Actions');?></th>
				</tr>
				
				<?php
				for($i=0;$i<count($group_info);$i++){
				?>
				<tr>
					<td><?php echo $group_info[$i]['ob_gw_seri'];?></td>
					<td><?php echo $group_info[$i]['ob_grp_name'];?></td>
					<td>
						<button type="button" value="Modify" style="width:32px;height:32px;" onclick="getPage('<?php echo $group_info[$i]['ob_gw_seri'];?>')">
							<img src="/images/edit.gif" />
						</button>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>

		<input type="hidden" name="send" id="send" value="" />
	</form>
	
	<script>
	$(function(){
		$("#schedule").iButton();
	});
	
	function getPage(ob_gw_seri){
		window.location.href = '<?php echo get_self();?>?sel_seri_name='+ob_gw_seri;
	}
	</script>
<?php
}

function edit_sim_group_rules(){
	global $db;
	
	$res = $db->Get('tb_group_info','DISTINCT ob_grp_name','where ob_gw_seri = ""');
	
	$i = 0;
	while($info = mysqli_fetch_array($res,MYSQLI_ASSOC)){
		$group_name_arr[$i]['ob_grp_name'] = $info['ob_grp_name'];
		$i++;
	}
?>

<form enctype="multipart/form-data" action="<?php echo get_self();?>" method="post">
	<input type="hidden" name="sim_seri" id="sim_seri" value="<?php echo $_GET['sel_seri_name'];?>" />
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg"><?php echo language('Sim Group');?></li>
		<li class="tb2">&nbsp;</li>
	</div>
	
	<table width="100%" class="tedit">
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Gateway Serial Number');?>:
					<span class="showhelp">
					<?php echo language('Gateway Serial Number help');?>
					</span>
				</div>
			</th>
			<td>
				<?php echo $_GET['sel_seri_name'];?>
			</td>
		</tr>
		
		<tr>
			<th>
				<div class="helptooltips">
					<?php echo language('Group Name');?>:
					<span class="showhelp">
					<?php echo language('Group Name help');?>
					</span>
				</div>
			</th>
			<td>
				<select name="group_name" id="group_name" >
					<option value="">None</option>
					<?php 
					for($i=0;$i<count($group_name_arr);$i++){
					?>
					<option value="<?php echo $group_name_arr[$i]['ob_grp_name'];?>"><?php echo $group_name_arr[$i]['ob_grp_name'];?></option>
					<?php } ?>
				</select>
			</td>
		</tr>
	</table>
	
	<div id="newline"></div>
	
	<table id="float_btn" class="float_btn">
		<tr id="float_btn_tr" class="float_btn_tr">
			<td>
				<input type="hidden" name="send" id="send" value="" />
				<input style="margin-right:15px;" type="submit" value="<?php echo language('Save');?>" onclick="document.getElementById('send').value='Save'" />
			</td>
		</tr>
	</table>
</form>
<?php
}

function save_sim_group_rules(){
	global $db;
	global $tmp_db;
	
	$group_name = $_POST['group_name'];
	$sim_seri = $_POST['sim_seri'];
	
	$db->Set('tb_group_info',"ob_gw_seri=''","where ob_gw_seri='$sim_seri'");
	$db->Set('tb_group_info',"ob_gw_seri='$sim_seri'","where ob_grp_name='$group_name'");
	
	$db->Set('tb_gateway_info',"ob_grp_name='$group_name'", "where ob_gw_seri='$sim_seri'");
	
	$tmp_db->Set('tb_gateway_info',"ob_grp_name='$group_name'", "where ob_gw_seri='$sim_seri'");
	
	$tmp_db->Set('tb_gateway_link_info',"ob_grp_name='$group_name'", "where ob_gw_seri='$sim_seri'");
}

function save_schedule(){
	$aql = new aql(); 
	$setok = $aql->set('basedir','/mnt/config/simbank/conf');
	$conf_path = '/mnt/config/simbank/conf/strategy.conf';
	if(!$aql->open_config_file($conf_path)){
		echo $aql->get_error();
		return 1;
	}
	
	$res = $aql->query("select * from strategy.conf");

	if(isset($res['strategy']['mode'])){
		$mode = $res['strategy']['mode'];
	}else{
		$mode = 0;
	}
	
	if($mode == 1 || $mode == 3 || $mode == 5 || $mode == 7 || $mode == 9 || $mode == 11 || $mode == 13 || $mode == 15){
		$other_mode = $mode - 1;
	}else{
		$other_mode = $mode;
	}
	
	if(isset($_POST['schedule'])){
		$schedule = 1;
	}else{
		$schedule = 0;
	}
	
	$mode = $schedule + $other_mode;
	
	if(isset($res['strategy']['mode'])){
		$aql->assign_editkey('strategy','mode',$mode);
	}else{
		$aql->assign_append('strategy','mode',$mode);
	}
	
	if (!$aql->save_config_file('strategy.conf')) {
		echo $aql->get_error();
		return 1;
	}
	
	$duration_online = $res['strategy']['duration_online'];
	$counts = $res['strategy']['counts'];
	$sim_use_dura = $res['strategy']['sim_use_dura'];
	$duration_sleep = $res['strategy']['duration_sleep'];
	
	//gsoap
	$client = new SoapClient("http://127.0.0.1:8888/?wsdl");
	
	$soap_arr = [$mode, $duration_online, $counts, $sim_use_dura, $duration_sleep];
	
	$result = $client->__soapCall('SBKSrategySave', $soap_arr, array('location' => 'http://127.0.0.1:8888', 'uri' => 'webproxy'));
}

if($_POST){
	if(isset($_POST['send']) && $_POST['send'] == 'Save'){
		save_sim_group_rules();
		show_sim_group_rules();
	}else if(isset($_POST['send']) && $_POST['send'] == 'Schedule Save'){
		save_schedule();
		show_sim_group_rules();
	}
}else if($_GET){
	edit_sim_group_rules();
}else{
	show_sim_group_rules();
}

require("/opt/simbank/www/inc/boot.inc");