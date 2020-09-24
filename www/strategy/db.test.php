<?php
require_once('../inc/mysql_class.php');


$db=new mysql();//('172.16.6.103','root','','simserver',"gbk","pconn");
$result = $db->query("select * from tb_simbank_link_info");
while ($row = mysql_fetch_assoc($result))
{
	//echo $row["n_sb_link_last_time"];
	print_r($row);
}

$ii = "1111111111";
$aa = 44;
$bb = 55;
$setval = "ob_gw_seri=$ii,ob_gw_link_bank_nbr=$aa,ob_gw_link_slot_nbr=$bb";
$cond = "where ob_gw_seri=\"0123456789\"";
$db->Set("tb_gateway_link_info", $setval, $cond);

$time = date('Y-m-d H:i:s');
$seri = "ell000009821";
$result = $db->Set("tb_gateway_info","d_heartbeat_time = \"$time\"","where ob_gw_seri = \"$seri\"");
echo $result;
echo "------\n";
echo TRUE;
echo "------\n";
echo FALSE;
echo "------\n";
/*$total = mysql_num_fields($result);
for ($i = 0; $i < $total; $i++)
{
	//print_r(mysql_fetch_field($result, $i));
	//$record = mysql_fetch_field($result, "n_sb_link_last_time");
	$record = mysql_fetch_array($result, $i);
	echo $record;
	//print_r($record);
	//echo $record["n_sb_link_last_time"];
}*/
$data = $db->Get("tb_gateway_link_info", "*", "");
//while (!empty($data))
while ($record = mysqli_fetch_array($data,MYSQLI_ASSOC))
{
	if (!isset($record))
	{
		continue;
	}
	printf("%s- %02d-%02d\n", $record["ob_gw_seri"], $record["ob_gw_link_bank_nbr"], $record["ob_gw_link_slot_nbr"]);
}
$time_curr = time();
echo $time_curr;
?>
