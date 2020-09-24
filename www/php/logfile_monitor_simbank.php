#!/bin/php


<?php

function lock_file($file_path)
{
	//$new_name = str_replace("/","_",$file_path);
	$lock_path = $file_path . ".lock";
	$fh = fopen($lock_path, "w+");
	if($fh <= 0) {
		return -1;
	}
	flock($fh,LOCK_EX);

	return $fh;
}

function unlock_file($fh)
{
	if($fh > 0) {
		flock($fh,LOCK_UN);
		fclose($fh);
	}
}




//default setting;
/************************************************/
$conf_path = '/etc/asterisk/gw';
$conf_file = 'logfile_monitor.conf';
$conf_path_file = $conf_path."/".$conf_file;
$default_maxsize = 2048*1024;
$debug = false;
$interval = 45;


function check_cut_txt($file, $maxsize)
{
	global $debug;
	clearstatcache();
	if($debug){echo "in check_cut_txt(), file=$file maxsize=$maxsize filesize=".filesize($file)."\n";}
	if(is_dir($file)){
		$dir = $file;
		$dir_handle = opendir($dir);
		while(($file = readdir($dir_handle))!= false){
			if($file == '.' || $file == '..') {
				continue;
			}   
			$file = $dir."/".$file;
			check_cut_txt($file, $maxsize);
		}  
	}else{
		while(file_exists($file) && filesize($file) > $maxsize){
			if($debug)echo "========$file need to be cut\n";
			$size = $maxsize/2;
			//if ($file == "/var/log/pri_messages") {pri_exec_debug("off");}  
			$flock = lock_file($file);
			$fh = fopen($file, "r+");
			fseek($fh, -$size, SEEK_END);/* read the last $size content; */
			$buf = fread($fh, $size);
			echo "get buf: ".strlen($buf)."\n";
			ftruncate($fh, 0);/* clear up file */
			rewind($fh);
			fwrite($fh, $buf);/* write the read content; */
			fclose($fh);
			//
			$buf="";
			unlock_file($flock);
			//if ($file == "/var/log/pri_messages") {pri_exec_debug("on");}
			clearstatcache();
		}
	}
}

function monitor_file_func($monitor_flie, $interval)
{
	global $debug;

	if($debug)echo "start monitor ...\n";
	while(true){
		if($debug)echo "\ncheck...\n";
		foreach($monitor_flie as $key => $value){
			if($debug)echo "key=$key\n";
			$file = $monitor_flie[$key]['file'];
			$maxsize = $monitor_flie[$key]['maxsize'];
			check_cut_txt($file, $maxsize);			
		}
		sleep($interval);
	}
}

################################################################
#
#
#	file monitor
#
#
################################################################

/* Rebuild monitor file list

/opt/simbank/SimProxySvr/SimProxySvr.log 
/opt/simbank/SimRdrSvr/SimRdrSvr.log 
/opt/simbank/www/php/socket_s.log 
/opt/simbank/www/php/establish.log 
/opt/simbank/www/php/release.log


*/  //added by LRG, added for simbank 
$monitor_file["1"]['file']="/opt/simbank/SimProxySvr/SimProxySvr.log";
$monitor_file["1"]['maxsize']=$default_maxsize;

$monitor_file["2"]['file']="/opt/simbank/SimRdrSvr/SimRdrSvr.log";
$monitor_file["2"]['maxsize']=$default_maxsize;

$monitor_file["3"]['file']="/opt/simbank/www/php/socket_s.log";
$monitor_file["3"]['maxsize']=$default_maxsize;

$monitor_file["4"]['file']="/opt/simbank/www/php/establish.log";
$monitor_file["4"]['maxsize']=$default_maxsize;

$monitor_file["5"]['file']="/opt/simbank/www/php/release.log";
$monitor_file["5"]['maxsize']=$default_maxsize;



/* start monitor */
if($debug)print_r($monitor_file);
monitor_file_func($monitor_file, $interval);

?>

