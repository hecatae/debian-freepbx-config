#! /bin/bash


/etc/init.d/amportal stop
/etc/init.d/apache2 stop

# Debian Deps
aptitude purge libncurses5-dev subversion libcurl4-openssl-dev libiksemel-dev libogg-dev libpq-dev libreadline5-dev libsnmp-dev \
	libssl-dev libvorbis-dev zlib1g-dev libsnmp9-dev libgmime-2.0-2-dev libspandsp-dev mysql-client libmysqlclient15-dev \
	php5-mysql php-pear php-db php5-gd freetds-common freetds-dev libspeex-dev libspeexdsp-dev unixodbc-dev libsqlite3-dev sqlite3 libsqlite0-dev sqlite \
	apache2 libapache2-mod-php5 mysql-common mysql-server-5.0 mysql-client-5.0


rm -rf /etc/asterisk
rm -rf /etc/amportal.conf
rm -rf /var/www/html
rm -rf /opt/freepbx

rm -rf /etc/apache2/sites-available/freepbx
rm -rf /var/log/apache2/freepbx

rm -rf /var/lib/asterisk
rm -rf /var/run/asterisk

