#! /bin/bash

set -e

BASEDIR="$( cd "$( dirname "$0" )" && pwd )" #/usr/src/mevoip
MYSQLUSER=root
MYSQLPASS=root
MYHOSTNAME=$(hostname -f)
MYIPADDR=$(ip addr | tr -s " " | grep "^\ inet\ " | grep "eth0" | cut -d " " -f 3 | cut -d "/" -f 1)


whiptail --title "ME VOIP FreePBX Install" --msgbox "This will now install some Debian packages and configure them at hostname $MYHOSTNAME and IP address $MYIPADDR." 12 78

cd $BASEDIR
# original da Disc-OS?
#wget -O Disc-OS-Sounds-1.0-pt_BR.tar.gz  "http://downloads.sourceforge.net/project/disc-os/Disc-OS%20Sounds/1.0-RELEASE/Disc-OS-Sounds-1.0-pt_BR.tar.gz?r=http%3A%2F%2Fsourceforge.net%2Fprojects%2Fdisc-os%2Ffiles%2F&ts=1289173263&use_mirror=ufpr"

if [ ! -e Disc-OS-Sounds-1.0-pt_BR_16.tar.gz ]; then 
	wget -O Disc-OS-Sounds-1.0-pt_BR_16.tar.gz "http://www.delete.com.br/wp-content/uploads/2010/10/sounds_1.6.tar.gz"
fi

if [ ! -e /var/lib/asterisk/sounds/pt_BR ]; then
	cd /var/lib/asterisk/sounds
	tar xzvf $BASEDIR/Disc-OS-Sounds-1.0-pt_BR_16.tar.gz
	chown -R asterisk:asterisk /var/lib/asterisk/sounds
fi

# set locales...
echo -e "en_US.UTF-8 UTF-8\npt_BR ISO-8859-1\npt_BR.UTF-8 UTF-8\npt_PT ISO-8859-1\npt_PT.UTF-8 UTF-8\npt_PT@euro ISO-8859-15" > /etc/locale.gen
/usr/sbin/locale-gen

if ! grep -q "^asterisk" /etc/group ; then 
	addgroup asterisk
fi

if ! grep -q "^asterisk" /etc/passwd ; then 
	useradd -g asterisk -c "Asterisk PBX" -d /var/lib/asterisk asterisk
fi

mkdir -p /var/run/asterisk
chown asterisk.asterisk /var/run/asterisk
chown -Rf asterisk.asterisk /var/log/asterisk

# /etc/php5/apache2/php.ini magic_quotes_gpc = Off
sed -i -e 's/magic_quotes_gpc = On/magic_quotes_gpc = Off ;changed by mevoip/' /etc/php5/apache2/php.ini

# /etc/apache2/envvars | export APACHE_RUN_USER=asterisk | export APACHE_RUN_GROUP=asterisk
sed -i -e 's/www-data/asterisk #changed by mevoip/' /etc/apache2/envvars


if grep -q "^bind-address" /etc/mysql/my.cnf; then
	sed -i -e 's/^bind-address/# bind-address #changed by mevoip/' /etc/mysql/my.cnf
	/etc/init.d/mysql restart
fi


cd $BASEDIR/freepbx

mysqladmin -u$MYSQLUSER -p$MYSQLPASS create asterisk
mysqladmin -u$MYSQLUSER -p$MYSQLPASS create asteriskcdrdb

mysql -u$MYSQLUSER -p$MYSQLPASS asterisk < SQL/newinstall.sql
mysql -u$MYSQLUSER -p$MYSQLPASS asteriskcdrdb < SQL/cdr_mysql_table.sql

mysql -u$MYSQLUSER -p$MYSQLPASS mysql -e "grant all privileges on asterisk.* to freepbx@localhost identified by 'freepbx123';"
mysql -u$MYSQLUSER -p$MYSQLPASS mysql -e "grant all privileges on asteriskcdrdb.* to freepbx@localhost identified by 'freepbx123';"

mysql -u$MYSQLUSER -p$MYSQLPASS mysql -e "grant all privileges on asterisk.* to freepbxConfig@'%' identified by 'ast123';"
mysql -u$MYSQLUSER -p$MYSQLPASS mysql -e "grant all privileges on asteriskcdrdb.* to freepbxConfig@'%' identified by 'ast123';"

mysql -u$MYSQLUSER -p$MYSQLPASS asteriskcdrdb < $BASEDIR/extraMysqlStatements.sql


# depois
cd $BASEDIR
#wget http://asteriskipcop.berlios.de/fpfa/amportal
cp amportal.sh /etc/init.d/amportal
chmod +x /etc/init.d/amportal
update-rc.d amportal defaults

cd $BASEDIR/freepbx
php5 setup_svn.php


# precisa levantar o asterisk para poder instalar o freepbx

# para isto precisa tirar o digivoice
echo "noload => chan_dgv.so" >> /etc/asterisk/modules.conf

# levanta o asterisk...
asterisk -G asterisk -U asterisk -F

# @TODO: configurar o user no manager?

whiptail --title "ME VOIP FreePBX Install" --msgbox "This will now install FreePBX (install_amp). Please press ENTER and accept default values for any prompts that follow." 12 78

# instala o freepbx
./install_amp --dbhost localhost --dbname asterisk --username freepbx --password freepbx123 --webroot /opt/freepbx/html 

# acerta config...
sed -i -e "s/AUTHTYPE=none/AUTHTYPE=database/" /etc/amportal.conf
sed -i -e "s/AMPWEBADDRESS=xx.xx.xx.xx/AMPWEBADDRESS=$MYIPADDR/" /etc/amportal.conf


# apaga um raio dum arquivo chato, não sei bem pq tem isso
if [ -e  /etc/asterisk/sip_notify.conf ]; then
	rm -f /etc/asterisk/sip_notify.conf
fi

# para ficar certinho precisaria matar o asterisk....
/etc/init.d/amportal stop
/etc/init.d/amportal stop

sleep 2

# para isto precisa tirar o digivoice
echo "noload => chan_dgv.so" >> /etc/asterisk/modules.conf


cd $BASEDIR

if ! grep -q "^dbname\ =\ asteriskcdrdb" /etc/asterisk/res_mysql.conf; then
cat << EOF >> /etc/asterisk/res_mysql.conf
;; added my MEVOIP
[general]
dbhost = localhost
dbname = asteriskcdrdb
dbuser = freepbx
dbpass = freepbx123
dbsock = /var/run/mysqld/mysqld.sock
requirements=createclose
EOF
fi

if ! grep -q "^queue_log => mysql,general" /etc/asterisk/extconfig.conf; then
	echo -e "\n;; added my MEVOIP\nqueue_log => mysql,general" >> /etc/asterisk/extconfig.conf
fi

if ! grep -q "^\[freepbxConfig\]" /etc/asterisk/manager.conf; then
cat << EOF >> /etc/asterisk/manager.conf
;; added my MEVOIP
[freepbxConfig]
secret = ast123
deny=0.0.0.0/0.0.0.0
permit=0.0.0.0/0.0.0.0
read = system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate
write = system,call,log,verbose,command,agent,user,config,command,dtmf,reporting,cdr,dialplan,originate
EOF
fi


# levanta o amp
/etc/init.d/amportal start

## Apache config.
a2enmod ssl
a2dissite default-ssl

mkdir -p /var/log/apache2/freepbx

cp $BASEDIR/apachesite.conf /etc/apache2/sites-available/freepbx
sed -i -e "s/thehostname/$MYHOSTNAME/" /etc/apache2/sites-available/freepbx
sed -i -e "s/thelocalip/$MYIPADDR/" /etc/apache2/sites-available/freepbx
a2ensite freepbx

whiptail --title "ME VOIP FreePBX Install - Apache" --msgbox "This will now test Apache config and restart it." 12 78
apache2ctl -t

/etc/init.d/apache2 stop; sleep 2; /etc/init.d/apache2 start

whiptail --title "ME VOIP FreePBX Install - Apache" --msgbox "Please test FreePBX install at https://$MYIPADDR - the certificate is invalid, ignore it. Username is 'admin', password 'admin'." 12 78


