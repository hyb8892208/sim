<?php
$p1 = pcntl_fork();

if ($p1 == -1)
{
    echo "pcntl_fork error";
}
else if ($p1)
{
    //parent 
    echo "parent";
}
else
{
    //child
        $child_pid = pcntl_fork();
        if($child_pid)
        {
            //parent
		echo "child-parent";
        }else{
            //child
            gogo();
        }
}

function gogo()
{
	$pg = pcntl_fork();
	if ($pg)
	{
		echo "gogo-parent";
	}
	else
	{
		echo "gogo-child";
	}
}
?>
