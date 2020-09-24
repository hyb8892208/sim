<?php
ini_set("display_errors","on");
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
?>

<?php
function show_group()
{
	$db=new mysql();
	$data = $db->Get("tb_group_info",'*','');
	$all_group=array();
	$k = 0;
	while($row = mysqli_fetch_array($data,MYSQLI_ASSOC))
	{
		$all_group[$k] = $row;
		$k += 1;
	}
?>
	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg">
			<?php echo 'Group Infomation';?>
		</li>
		<li class="tb2">&nbsp;</li>
	</div>
	<table width="100%" class="tshow">
		<tr>
			<th width="145px"><?php echo 'Group Name';?></th>
			<th width="35%"><?php echo 'Line Create Strategy';?></th>
			<th width="35%"><?php echo 'Line Release Strategy';?></th>
			<th><?php echo 'Description';?></th>
			<th width="80px"><?php echo 'Actions';?></th>
			<input type="hidden" id="sel_group_name" name="sel_group_name" value="" />
		</tr>
<?php
	if($all_group){
		foreach($all_group as $gro) {
?>
		<tr>
			<td>
				<?php echo $gro['ob_grp_name']; ?>
			</td>
			<td>
				<?php echo $gro['v_grp_create_policy']; ?>
			</td>
			<td>
				<?php echo $gro['v_grp_release_policy']; ?>
			</td>
			<td>
				<?php echo $gro['v_grp_desc']; ?>
			</td>
			<td>
				<button type="button" value="Modify" style="width:32px;height:32px;" 
					onclick="getPage('<?php echo $gro['ob_grp_name']; ?>');">
					<img src="/images/edit.gif">
				</button>
				<button type="submit" value="Delete" style="width:32px;height:32px;" 
					onclick="document.getElementById('send').value='Delete';return delete_click('<?php echo $gro['ob_grp_name']; ?>');" >
					<img src="/images/delete.gif">
				</button>
			</td>
		</tr>
<?php
		}
	}
?>
	</table>
	<div id="newline"></div>
	<input type="hidden" name="send" id="send" value="" />
	<input type="submit" value="<?php echo 'Add New Group';?>" onclick="document.getElementById('send').value='Add New Group';" />
	</form>
	
<script type="text/javascript">
function getPage(value)
{
	window.location.href = '<?php echo get_self();?>?sel_group='+escape(value);
}

function delete_click(value1)
{
	ret = confirm("<?php echo 'Are you sure to delete you selected ?'; ?>");

	if(ret) {
		document.getElementById('sel_group_name').value = value1;
		return true;
	}

	return false;
}
</script>

<?php
}

function add_group_page($sel_group = '')
{
	$all_group=array();
	if($sel_group) {
		$db=new mysql();
		$data = $db->Get("tb_group_info",'*',"where ob_grp_name = \"$sel_group\"");
		$k = 0;
		$all_group = mysqli_fetch_array($data,MYSQLI_ASSOC);
		echo "<h4>";echo 'Modify Group';echo "</h4>";
	} else {
		$all_group['ob_grp_name'] = '';
		$all_group['v_grp_match_policy'] = '';
		$all_group['v_grp_create_policy'] = '';
		$all_group['v_grp_release_policy'] = '';
		$all_group['v_grp_desc'] = '';
		echo "<h4>";echo 'Create Group';echo "</h4>";
	}
	$route_match = $db->Get("tb_policy_info",'ob_pol_name','where n_pol_type = 1');
	$all_route_match= Array();
	$k = 0;
	while($locate = mysql_fetch_array($route_match))
	{
		$all_route_match[$k] = $locate['ob_pol_name'];
		$k += 1;
	}
	$route_establish = $db->Get("tb_policy_info",'ob_pol_name','where n_pol_type = 2');
	$all_route_establish= Array();
	$k = 0;
	while($locate = mysql_fetch_array($route_establish))
	{
		$all_route_establish[$k] = $locate['ob_pol_name'];
		$k += 1;
	}
	$route_release = $db->Get("tb_policy_info",'ob_pol_name','where n_pol_type = 3');
	$all_route_release= Array();
	$k = 0;
	while($locate = mysql_fetch_array($route_release))
	{
		$all_route_release[$k] = $locate['ob_pol_name'];
		$k += 1;
	}
?>

	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<input type="hidden" id="old_ob_grp_name" name="old_ob_grp_name" value="<?php echo $all_group['ob_grp_name'];?>" />

	<div id="tab_main" style="display:block">
		<table width="100%" class="tedit" >
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'Group Name';?>:
						<span class="showhelp">
							<?php echo 'Group Name';?>
						</span>
					</div>
				</th>
				<td >
					<input type="text" name="ob_grp_name" id="ob_grp_name" value="<?php echo htmlentities($all_group['ob_grp_name']);?>" /><span id="cob_grp_name"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'Line Create Strategy';?>:
						<span class="showhelp">
							<?php echo 'Line Create Strategy';?>    
						</span>
					</div>
				</th>
				<td >
					<select id="v_grp_create_policy" name="v_grp_create_policy">
						<?php
							foreach($all_route_establish as $key => $establish){
						?>
					    <option value="<?php echo $establish;?>" <?php if($establish == $all_group['v_grp_create_policy']){echo 'selected';};?>><?php echo $establish;?></option>
					    <?php
					    	}
					    ?>
					</select><span id="cv_grp_create_policy"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'Line ReleaseStrategy';?>:
						<span class="showhelp">
							<?php echo 'Line Release Strategy';?>
						</span>
					</div>
				</th>
				<td >
					<select id="v_grp_release_policy" name="v_grp_release_policy">
						<?php
							foreach($all_route_release as $key => $release){
						?>
					    <option value="<?php echo $release;?>" <?php if($release == $all_group['v_grp_release_policy']){echo 'selected';};?>><?php echo $release;?></option>
					    <?php
					    	}
					    ?>
					</select><span id="cv_grp_release_policy"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'Description';?>:
						<span class="showhelp">
							<?php echo 'Description';?>
						</span>
					</div>
				</th>
				<td >
					<input style="width:60%;height:100px;" type="text" name="v_grp_desc" id="v_grp_desc" value="<?php echo htmlentities($all_group['v_grp_desc']);?>" /><span id="cv_grp_desc"></span>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="newline"></div>

	<br>

	<input type="hidden" name="send" id="send" value="" />
	<input type="submit" class="float_btn gen_short_btn"   value="<?php echo 'Save';?>" onclick="document.getElementById('send').value='Save';"/>
	&nbsp;
	<input type=button  value="<?php echo 'Cancel';?>" onclick="window.location.href='<?php echo get_self();?>'" />
	</form>
<?php
}

function save_group()				//save group
{ 
	$old_ob_grp_name = trim($_POST['old_ob_grp_name']);
	$ob_grp_name = trim($_POST['ob_grp_name']);
	$v_grp_create_policy = trim($_POST['v_grp_create_policy']);
	$v_grp_release_policy = trim($_POST['v_grp_release_policy']);
	$v_grp_desc = trim($_POST['v_grp_desc']);
	$db=new mysql();
	if($old_ob_grp_name != '' && $old_ob_grp_name != $ob_grp_name){
		$data = $db->Get("tb_group_info",'*','');
		$check_loc = '';
		while($row = mysqli_fetch_array($data,MYSQLI_ASSOC))
		{
			if($row['ob_grp_name'] == $ob_grp_name){			//if group repeat,return "repeat".
				$check_grp = "repeat";
				return $check_grp;
			}
		}
	}
	
	if($_POST['old_ob_grp_name'] == ''){				//add location
		$values = "\"$ob_grp_name\",\"$v_grp_create_policy\",\"$v_grp_release_policy\",\"$v_grp_desc\"";
		$fields = "ob_grp_name,v_grp_create_policy,v_grp_release_policy,v_grp_desc";
		$data = $db->Add("tb_group_info",$fields,$values);
	} else {														//modify location
		$condition = "where ob_grp_name = \"$old_ob_grp_name\"";
		$fields = "ob_grp_name=\"$ob_grp_name\",v_grp_create_policy=\"$v_grp_create_policy\",v_grp_release_policy=\"$v_grp_release_policy\",v_grp_desc=\"$v_grp_desc\"";
		$data = $db->Set("tb_group_info",$fields,$condition);
	}
	return $data;
}

function del_group()					//delete location
{
	$sel_group_name = $_POST['sel_group_name'];
	$db=new mysql();
	$condition = "ob_grp_name = \"$sel_group_name\"";
	$data = $db->Del("tb_group_info",$condition);
	return $data;
}
?>

<?php
	if($_POST) {
		if( (isset($_POST['send']) && ($_POST['send'] == 'Add New Group') ) ) {
			add_group_page($_POST);
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Save') {
			$status = save_group();
			if($status == 1){
				echo "<h4>";echo 'Operation Success.';echo "</h4>";
			} elseif($status == "repeat") {
				echo "<h4>";echo 'Group Already Exists.';echo "</h4>";
			} else {
				echo "<h4>";echo 'Operation Miss.';echo "</h4>";
			}
			show_group();
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Delete') {
			if(del_group() == 1) {
				echo "<h4>";echo 'Delete Success.';echo "</h4>";
			} else {
				echo "<h4>";echo 'Delete Failed.';echo "</h4>";
			}
			show_group();
		}
	} else if($_GET) {
		if( isset($_GET['sel_group']) ) {
			add_group_page($_GET['sel_group'],'');
		}
	} else {
		show_group();
	}
?>


<?php 
require("../inc/boot.inc");
?>
