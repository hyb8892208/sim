<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
?>

<?php
function show_location()
{
	$db=new mysql();
	$data = $db->Get("tb_locate_info",'*','');
	$all_location=array();
	$k = 0;
	while($row = mysqli_fetch_array($data,MYSQLI_ASSOC))
	{
		$all_location[$k] = $row;
		$k += 1;
	}
?>
	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg">
			<?php echo 'Location infomation';?>
		</li>
		<li class="tb2">&nbsp;</li>
	</div>
	<table width="100%" class="tshow">
		<tr>
			<th><?php echo 'Location Code';?></th>
			<th><?php echo 'Zoneinfo';?></th>
			<th><?php echo 'Description';?></th>
			<th width="80px"><?php echo 'Actions';?></th>
			<input type="hidden" id="sel_locate_id" name="sel_locate_id" value="" />
		</tr>
<?php
	if($all_location){
		foreach($all_location as $loca) {
?>
		<tr>
			<td>
				<?php echo $loca['ob_loc_id']; ?>
			</td>
			<td>
				<?php echo $loca['v_loc_info']; ?>
			</td>
			<td>
				<?php echo $loca['v_loc_desc']; ?>
			</td>
			<td>
				<button type="button" value="Modify" style="width:32px;height:32px;" 
					onclick="getPage('<?php echo $loca['ob_loc_id']; ?>');">
					<img src="/images/edit.gif">
				</button>
				<button type="submit" value="Delete" style="width:32px;height:32px;" 
					onclick="document.getElementById('send').value='Delete';return delete_click('<?php echo $loca['ob_loc_id']; ?>');" >
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
	<input type="submit" value="<?php echo 'Add New Location';?>" onclick="document.getElementById('send').value='Add New Location';" />
	</form>
	
<script type="text/javascript">
function getPage(value)
{
	window.location.href = '<?php echo get_self();?>?sel_location='+escape(value);
}

function delete_click(value1)
{
	ret = confirm("<?php echo 'Are you sure to delete you selected ?'; ?>");

	if(ret) {
		document.getElementById('sel_locate_id').value = value1;
		return true;
	}

	return false;
}
</script>

<?php
}

function add_location_page($sel_location = '')
{
	
	$all_location=array();
	if($sel_location) {
		$db=new mysql();
		$data = $db->Get("tb_locate_info",'*',"where ob_loc_id = \"$sel_location\"");
		$k = 0;
		$all_location = mysqli_fetch_array($data,MYSQLI_ASSOC);
		echo "<h4>";echo 'Modify a Location';echo "</h4>";
	} else {
		$all_location['ob_loc_id'] = '';
		$all_location['v_loc_info'] = '';
		$all_location['v_loc_desc'] = '';
		echo "<h4>";echo 'Create a Location';echo "</h4>";
	}
?>

	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<input type="hidden" id="old_ob_loc_id" name="old_ob_loc_id" value="<?php echo $all_location['ob_loc_id'];?>" />

	<div id="tab_main" style="display:block">
		<table width="100%" class="tedit" >
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'Location Code';?>:
						<span class="showhelp">
							<?php echo 'Location Code';?>
						</span>
					</div>
				</th>
				<td >
					<input type="text" name="ob_loc_id" id="ob_loc_id" value="<?php echo htmlentities($all_location['ob_loc_id']);?>" /><span id="cob_loc_id"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'Zoneinfo';?>:
						<span class="showhelp">
							<?php echo 'Zoneinfo';?>
						</span>
					</div>
				</th>
				<td >
					<input type="text" name="v_loc_info" id="v_loc_info" value="<?php echo htmlentities($all_location['v_loc_info']);?>" /><span id="cv_loc_info"></span>
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
					<input type="text" name="v_loc_desc" id="v_loc_desc" value="<?php echo htmlentities($all_location['v_loc_desc']);?>" /><span id="cv_loc_desc"></span>
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

function save_location()				//save location
{ 
	$old_ob_loc_id = $_POST['old_ob_loc_id'];
	$ob_loc_id = $_POST['ob_loc_id'];
	$v_loc_info = $_POST['v_loc_info'];
	$v_loc_desc = $_POST['v_loc_desc'];

	$db=new mysql();
	$data = $db->Get("tb_locate_info",'*','');
	$check_loc = '';
	while($row = mysqli_fetch_array($data,MYSQLI_ASSOC))
	{
		if($row['ob_loc_id'] == $ob_loc_id){			//if location code repeat,return false.
			$check_loc = "repeat";
			return $check_loc;
		}
	}
	
	if($_POST['old_ob_loc_id'] == ''){				//add location
		$values = "\"$ob_loc_id\",\"$v_loc_info\",\"$v_loc_desc\"";
		$fields = "ob_loc_id,v_loc_info,v_loc_desc";
		$data = $db->Add("tb_locate_info",$fields,$values);
	} else {														//modify location
		$condition = "where ob_loc_id = \"$old_ob_loc_id\"";
		$fields = "ob_loc_id=\"$ob_loc_id\",v_loc_info=\"$v_loc_info\",v_loc_desc=\"$v_loc_desc\"";
		$data = $db->Set("tb_locate_info",$fields,$condition);
	}
	return $data;
}

function del_location()					//delete location
{
	$sel_locate_id = $_POST['sel_locate_id'];
	$db=new mysql();
	$condition = "ob_loc_id = \"$sel_locate_id\"";
	$data = $db->Del("tb_locate_info",$condition);
	return $data;
}
?>

<?php
	if($_POST) {
		if( (isset($_POST['send']) && ($_POST['send'] == 'Add New Location') ) ) {
			add_location_page();
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Save') {
			$status = save_location();
			if($status == 1){
				echo "<h4>";echo 'Operation Success.';echo "</h4>";
			} elseif($status == "repeat") {
				echo "<h4>";echo 'Location Code Already Exists.';echo "</h4>";
			} else {
				echo "<h4>";echo 'Operation Miss.';echo "</h4>";
			}
			show_location();
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Delete') {
			if(del_location() == 1) {
				echo "<h4>";echo 'Delete Success.';echo "</h4>";
			} else {
				echo "<h4>";echo 'Delete Failed.';echo "</h4>";
			}
			show_location();
		}
	} else if($_GET) {
		if( isset($_GET['sel_location']) ) {
			add_location_page($_GET['sel_location'],'');
		}
	} else {
		show_location();
	}
?>


<?php 
require("../inc/boot.inc");
?>
