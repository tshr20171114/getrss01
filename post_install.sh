#!/bin/bash

set -x

date

php -l loggly.php
php -l loggly_error.php

tar xf phpPgAdmin-5.1.tar.bz2

mv phpPgAdmin-5.1 www/phppgadmin

rm -f phpPgAdmin-5.1.tar.bz2

cp config.inc.php www/phppgadmin/conf/config.inc.php
cp -f Connection.php www/phppgadmin/classes/database/Connection.php

chmod 755 ./start_web.sh

date
