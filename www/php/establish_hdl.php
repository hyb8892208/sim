#!/usr/bin/php
<?php

printf("running establish strategy\n");
while (true)
{
	system("/usr/bin/php /opt/simbank/www/strategy/establish.php >> /opt/simbank/www/php/establish.log");
	sleep(10);
}


?>
