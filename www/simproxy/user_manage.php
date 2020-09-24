<?php
require("../inc/head.inc");
require("../inc/menu.inc");
require_once("../inc/function.inc");
require('../inc/mysql_class.php');
?>

<?php
function show_user()
{
	$db=new mysql();
	$data = $db->Get("tb_user_info",'*','');
	$all_user=array();
	$k = 0;
	while($row = mysqli_fetch_array($data,MYSQLI_ASSOC))
	{
		$all_user[$k] = $row;
		$k += 1;
	}
?>
	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<div id="tab">
		<li class="tb1">&nbsp;</li>
		<li class="tbg">
			<?php echo 'user Infomation';?>
		</li>
		<li class="tb2">&nbsp;</li>
	</div>
	<table width="100%" class="tshow">
		<tr>
			<th><?php echo 'User Name';?></th>
			<th><?php echo 'User Privilege ';?></th>
			<th><?php echo 'User Description';?></th>
			<th width="80px"><?php echo 'Actions';?></th>
			<input type="hidden" id="sel_user_name" name="sel_user_name" value="" />
		</tr>
<?php
	if($all_user){
		foreach($all_user as $user) {
?>
		<tr>
			<td>
				<?php echo $user['ob_usr_name']; ?>
			</td>
			<td>
				<?php if($user['n_usr_priv'] == 1){echo 'admin';}elseif($user['n_usr_priv'] == 2){echo 'normal user';} ?>
			</td>
			<td>
				<?php echo $user['v_usr_desc']; ?>
			</td>
			<td>
				<button type="button" value="Modify" style="width:32px;height:32px;" 
					onclick="getPage('<?php echo $user['ob_usr_name']; ?>');">
					<img src="/images/edit.gif">
				</button>
				<button type="submit" value="Delete" style="width:32px;height:32px;" 
					onclick="document.getElementById('send').value='Delete';return delete_click('<?php echo $user['ob_usr_name']; ?>');" >
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
	<input type="submit" value="<?php echo 'Add New User Info';?>" onclick="document.getElementById('send').value='Add New User Info';" />
	</form>
	
<script type="text/javascript">
function getPage(value)
{
	window.location.href = '<?php echo get_self();?>?sel_user='+escape(value);
}

function delete_click(value1)
{
	ret = confirm("<?php echo 'Are you sure to delete you selected ?'; ?>");
	
	if(ret) {
		document.getElementById('sel_user_name').value = value1;
		return true;
	}

	return false;
}
</script>

<?php
}

function add_user_info($username = '')
{
	$all_user=array();
	$db=new mysql();
	$general_usr_priv = '';
	$admin_usr_priv = '';
	if($username) {
		$data = $db->Get("tb_user_info",'*',"where ob_usr_name = \"$username\"");
		$k = 0;
		$all_user = mysqli_fetch_array($data,MYSQLI_ASSOC);
		if($all_user['n_usr_priv'] == 1){
			$admin_usr_priv = 'selected';
		}else{
			$general_usr_priv = 'selected';
		}
		echo "<h4>";echo 'Modify User Info';echo "</h4>";
	} else {
		$all_user['ob_usr_name'] = '';
		$all_user['n_usr_priv'] = '';
		$all_user['v_usr_passwd'] = '';
		$all_user['v_usr_desc'] = '';
		echo "<h4>";echo 'Create User Info';echo "</h4>";
	}
?>
	<script type="text/javascript" src="/js/jquery.ibutton.js"></script>
	<link type="text/css" href="/css/jquery.ibutton.css" rel="stylesheet" media="all" />
	<form enctype="multipart/form-data" action="<?php echo get_self() ?>" method="post">
	<input type="hidden" id="old_ob_usr_name" name="old_ob_usr_name" value="<?php echo $all_user['ob_usr_name'];?>" />

	<div id="tab_main" style="display:block">
		<table width="100%" class="tedit" >
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'User Name';?>:
						<span class="showhelp">
							<?php echo 'User Name';?>
						</span>
					</div>
				</th>
				<td >
					<input type="text" name="ob_usr_name" id="ob_usr_name" value="<?php echo htmlentities($all_user['ob_usr_name']);?>" /><span id="cob_usr_name"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'Password';?>:
						<span class="showhelp">
							<?php echo 'Password';?>
						</span>
					</div>
				</th>
				<td >
					<input style="width:70%;" type="password" name="v_usr_passwd" id="v_usr_passwd" value="<?php echo htmlentities($all_user['v_usr_passwd']);?>" /><span id="cv_usr_passwd"></span>
				</td>
			</tr>
			<tr>
				<th>
					<div class="helptooltips">
						<?php echo 'User Privilege';?>:
						<span class="showhelp">
							<?php echo 'User Privilege';?>
						</span>
					</div>
				</th>
				<td >
					<select id="n_usr_priv" name="n_usr_priv">
					    <option value="1" <?php echo $admin_usr_priv; ?>><?php echo 'admin';?></option>
					    <option value="2" <?php echo $general_usr_priv; ?>><?php echo 'normal user';?></option>
					</select><span id="cn_usr_priv"></span>
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
					<input style="width:60%;height:100px;" type="text" name="v_usr_desc" id="v_usr_desc" value="<?php echo htmlentities($all_user['v_usr_desc']);?>" /><span id="cv_usr_desc"></span>
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
	<script type="text/javascript">
		$(document).ready(function (){
		});
	</script>
<?php
}

function save_user_info()				//save user info
{ 
	$old_ob_usr_name = trim($_POST['old_ob_usr_name']);
	$ob_usr_name = trim($_POST['ob_usr_name']);
	$n_usr_priv = trim($_POST['n_usr_priv']);
	$v_usr_passwd = trim($_POST['v_usr_passwd']);
	$v_usr_desc = trim($_POST['v_usr_desc']);
	$db=new mysql();
	if($old_ob_usr_name != '' && $old_ob_usr_name != $ob_usr_name){
		$data = $db->Get("tb_user_info",'*','');
		$check_usr = '';
		while($row = mysqli_fetch_array($data,MYSQLI_ASSOC))
		{
			if($row['ob_usr_name'] == $ob_usr_name){			//if user repeat,return "repeat".
				$check_usr = "repeat";
				return $check_usr;
			}
		}
	}
	if($_POST['old_ob_usr_name'] == ''){				//add location
		$values = "\"$ob_usr_name\",\"$n_usr_priv\",\"$v_usr_passwd\",\"$v_usr_desc\"";
		$fields = "ob_usr_name,n_usr_priv,v_usr_passwd,v_usr_desc";
		$data = $db->Add("tb_user_info",$fields,$values);
	} else {														//modify location
		$condition = "where ob_usr_name = \"$old_ob_usr_name\"";
		$fields = "ob_usr_name=\"$ob_usr_name\",n_usr_priv=\"$n_usr_priv\",v_usr_passwd=\"$v_usr_passwd\",v_usr_desc=\"$v_usr_desc\"";
		$data = $db->Set("tb_user_info",$fields,$condition);
	}
	return $data;
}

function del_user_info()					//delete user info
{
	$sel_user_name = $_POST['sel_user_name'];
	$db=new mysql();
	$condition = "ob_usr_name = \"$sel_user_name\"";
	$data = $db->Del("tb_user_info",$condition);
	return $data;
}
?>

<?php
	if($_POST) {
		if( (isset($_POST['send']) && ($_POST['send'] == 'Add New User Info') ) ) {
			add_user_info();
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Save') {
			$status = save_user_info();
			if($status == 1){
				echo "<h4>";echo 'Operation Success.';echo "</h4>";
			} elseif($status == "repeat") {
				echo "<h4>";echo 'User Info Already Exists.';echo "</h4>";
			} else {
				echo "<h4>";echo 'Operation Miss.';echo "</h4>";
			}
			show_user();
		} elseif (isset($_POST['send']) && $_POST['send'] == 'Delete') {
			if(del_user_info() == 1) {
				echo "<h4>";echo 'Delete Success.';echo "</h4>";
			} else {
				echo "<h4>";echo 'Delete Failed.';echo "</h4>";
			}
			show_user();
		}
	} else if($_GET) {
		if( isset($_GET['sel_user']) ) {
			add_user_info($_GET['sel_user'],'');
		}
	} else {
		show_user();
	}
?>


<?php 
require("../inc/boot.inc");
?>
