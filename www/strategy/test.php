<?php
include_once("../inc/config.inc");

function get_strategy_conf()
{
	$ret = get_config("strategy.conf", NULL);
	return $ret["strategy"];
}
function func1($para)
{
	echo $para . "\n";
}
function main()
{
	$aa = "aa";
	func1("this is $aa");
	$ret = get_strategy_conf();
	print_r($ret);
	if ($ret["mode"] == 1)
	{
		echo "mode = 1";
	}
	else
	{
		echo "mode = 2";
	}
	$time = $ret["counts"] - 1;
	echo $time;
	//printf("mode:%d, duration_online:%d, counts:%d, duration_sleep%d\n", \
	//	$ret['mode'], $ret['duration_online'], $ret['counts'], $ret['duration_sleep']);
	echo $ret["duration_sleep"];
	return 0;
}

main();


?>
