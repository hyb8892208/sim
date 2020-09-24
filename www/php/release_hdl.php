#!/usr/bin/php
<?php

printf("running release strategy\n");
while (true)
{
        system("/usr/bin/php /opt/simbank/www/strategy/release.php >> /opt/simbank/www/php/release.log");
        sleep(10);
}


?>
