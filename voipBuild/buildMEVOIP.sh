#! /bin/bash

# To start:
# aptitude install subversion; svn checkout http://debian-freepbx-config.googlecode.com/svn/trunk/ /opt/voip
# bash /opt/voip/voipBuild/buildMEVOIP.sh


set -e

BASEDIR="$( cd "$( dirname "$0" )" && pwd )" #/usr/src/mevoip
NUMCPUS=$(cat /proc/cpuinfo | grep "^processor" | wc -l)
UPDATESVN=true
BUILDS=true
MAKECPUS="-j $NUMCPUS"
CLEAN=true
EXTRAS=true
BUILDASTERISK=true
BUILDDIGIVOICE=false


DISTRO=$(lsb_release -s -i)

chmod +x $BASEDIR/*.sh

whiptail --title "ME VOIP Asterisk Install" --msgbox "This insane script is going to install Asterisk and FreePBX on your Debian Lenny system from scratch. You have $NUMCPUS CPUs... really?" 12 78

# Proxy configuration.
MYPROXYHOST=$(whiptail --title "ME VOIP Asterisk Install" --inputbox "Please enter your proxy host (just the hostname, without port). If you don't have one, leave it empty." 12 78 192.168.4.150 3>&1 1>&2 2>&3)
MYPROXYPORT=$(whiptail --title "ME VOIP Asterisk Install" --inputbox "Please enter your proxy port." 12 78 3128 3>&1 1>&2 2>&3)

if [ "a$MYPROXYHOST" = "a" ]; then
	# No proxy, removing it. I dunno?
	whiptail --title "ME VOIP Asterisk Install" --msgbox "I'm sorry, I'm not really sure how to handle no-proxy cases. We'll just hope for the best." 12 78
else
	# Setup the proxy, for subversion:
	mkdir -p ~/.subversion
	echo -e "[global]\nhttp-proxy-host = $MYPROXYHOST\nhttp-proxy-port = $MYPROXYPORT" > ~/.subversion/servers
	
	# Setup the proxy, for wget.
	echo -e "\n\n#added by mevoip\nhttp_proxy = http://$MYPROXYHOST:$MYPROXYPORT/\n" >> /etc/wgetrc

	# Setup the proxy, for apt-get 
	echo "Acquire::http::Proxy \"http://$MYPROXYHOST:$MYPROXYPORT/\";" > /etc/apt/apt.conf
	#cat ~/.subversion/servers
	#cat /etc/wgetrc
	#cat /etc/apt/apt.conf
	#read
fi

whiptail --title "ME VOIP Asterisk Install" --msgbox "This will now install some $DISTRO packages and configure them." 12 78

# Preseed mysql
echo mysql-server mysql-server/root_password select root | debconf-set-selections
echo mysql-server mysql-server/root_password_again select root | debconf-set-selections

# Preseed postfix.
echo "postfix postfix/main_mailer_type select Internet Site" | debconf-set-selections
echo "postfix postfix/mailname select $(hostname -f)" | debconf-set-selections

if [ "$DISTRO" = "Ubuntu" ]; then
	DISTROPACKAGES="mysql-client libmysqlclient-dev mysql-common mysql-server-5.1 mysql-client-5.1"
else
	DISTROPACKAGES="mysql-client libmysqlclient15-dev mysql-common mysql-server-5.0 mysql-client-5.0"
fi

# Debian Deps
aptitude -y install gcc g++ make libncurses5-dev subversion libcurl4-openssl-dev libiksemel-dev libogg-dev libpq-dev libreadline5-dev libsnmp-dev \
	libssl-dev libvorbis-dev zlib1g-dev libsnmp9-dev libgmime-2.0-2-dev libspandsp-dev $DISTROPACKAGES \
	php5-mysql php-pear php-db php5-gd freetds-common freetds-dev libspeex-dev libspeexdsp-dev unixodbc-dev libsqlite3-dev sqlite3 libsqlite0-dev sqlite \
	apache2 libapache2-mod-php5 curl wget nano less ccze postfix linux-headers-`uname -r`

	
cd $BASEDIR

if [ "$BUILDDIGIVOICE" = "true" ]; then
	if [ ! -e dgvchannel-1.0.5.tar.gz ]; then
		wget "http://downloads.digivoice.com.br/pub/dgvchannel/stable/dgvchannel-1.0.5.tar.gz"
	fi

	if [ ! -e voicerlib-4.2.2.0.tar.gz ]; then
		wget "http://downloads.digivoice.com.br/pub/voicerlib/linux/stable/voicerlib-4.2.2.0.tar.gz"
	fi

	if [ ! -e voicerlib-4.2.2.0 ]; then
		tar xzvf voicerlib-4.2.2.0.tar.gz
	fi

	if [ ! -e dgvchannel-1.0.5 ]; then
		tar xzvf dgvchannel-1.0.5.tar.gz
	fi
fi

if [ "$UPDATESVN" = "true" ]; then
	cd $BASEDIR
	svn co http://svn.freepbx.org/freepbx/branches/2.8 freepbx

	cd $BASEDIR/freepbx
	php5 setup_svn.php

	cd $BASEDIR
	#svn co http://svn.digium.com/svn/asterisk/branches/1.6.2 asterisk
	svn co http://svn.digium.com/svn/asterisk/tags/1.6.2.13 asterisk
	
	cd $BASEDIR
	#svn co http://svn.digium.com/svn/dahdi/linux/branches/2.2 dahdi
	svn co http://svn.digium.com/svn/dahdi/linux/tags/2.4.0 dahdi

	cd $BASEDIR
	#svn co http://svn.digium.com/svn/dahdi/tools/branches/2.2 dahdi_tools
	svn co http://svn.digium.com/svn/dahdi/tools/tags/2.4.0 dahdi_tools

	cd $BASEDIR
	#svn co http://svn.digium.com/svn/libpri/branches/1.4 libpri
	svn co http://svn.digium.com/svn/libpri/tags/1.4.11.4 libpri

	cd $BASEDIR
	#svn co http://svn.digium.com/svn/asterisk-addons/branches/1.6.2 asterisk_addons
	svn co http://svn.digium.com/svn/asterisk-addons/tags/1.6.2.1 asterisk_addons
	
fi



if [ "$BUILDASTERISK" = "true" ]; then
	whiptail --title "ME VOIP Asterisk Install" --msgbox "This will now BUILD A LOT of source code (Asterisk, LibPRI, DAHDI, etc)." 12 78


	cd $BASEDIR/dahdi
	if [ "$CLEAN" = "true" ]; then make distclean; fi
	make $MAKECPUS	
	make install

	cd $BASEDIR/dahdi_tools
	if [ "$CLEAN" = "true" ]; then make clean; fi
	./configure
	make $MAKECPUS
	make install
	make config

	cd $BASEDIR/libpri
	if [ "$CLEAN" = "true" ]; then make clean; fi
	make $MAKECPUS && echo "*************** Make libPri OK!"""
	make install

	cd $BASEDIR/asterisk
	if [ "$CLEAN" = "true" ]; then make distclean; fi
	echo "1.6.2.13-MEVOIP" > $BASEDIR/asterisk/.version && echo "**** Set Asterisk version... forced... beware"

	if [ "$EXTRAS" = "true" ]; then
		cd $BASEDIR/asterisk
		if [ ! -f codecs/ilbc/iLBC_define.h ]; then
			wget -P codecs/ilbc http://www.ietf.org/rfc/rfc3951.txt
			wget -q -O - http://www.ilbcfreeware.org/documentation/extract-cfile.awk | sed -e 's/\r//g' > codecs/ilbc/extract-cfile.awk
			(cd codecs/ilbc && awk -f extract-cfile.awk rfc3951.txt)
			echo "Done iLBC codec..."
		else
			echo "iLBC codec already done."
		fi
	fi

	if [ -e .svn ]; then
		echo "***** Enganando asterisk version SVN"
		mv  .svn  .svn_engana_asterisk_version
	fi

	./configure --disable-xmldoc

	#make menuselect
	cp $BASEDIR/asterisk.menuselect.makeopts $BASEDIR/asterisk/menuselect.makeopts
	make $MAKECPUS && echo "*************** Make Asterisk OK!"
	make install
	make samples

	cd $BASEDIR/asterisk
	if [ -e .svn_engana_asterisk_version ]; then
		echo "***** Desenganando asterisk version SVN"
		mv .svn_engana_asterisk_version .svn
	fi

	cd $BASEDIR/asterisk_addons
	if [ "$CLEAN" = "true" ]; then make distclean; fi
	./configure
	#make menuselect
	cp $BASEDIR/asterisk_addons.menuselect.makeopts $BASEDIR/asterisk_addons/menuselect.makeopts
	make $MAKECPUS
	make install
	make samples

	echo "Ate aqui tudo bem!"
fi


if [ "$BUILDDIGIVOICE" = "true" ]; then
	whiptail --title "ME VOIP Asterisk Install" --msgbox "This will now BUILD DigiVoice Stuff (VoicerLib, chan_dgv)." 12 78

	cd $BASEDIR/voicerlib-4.2.2.0
	make clean
	make $MAKECPUS && echo "Digivoice VoicerLib make OK!!!"
	make install

	cd $BASEDIR/voicerlib-4.2.2.0/driver/linux
	make config && echo "Digivoice Voircelib linux driver config OK!!!"

	## a Digivoice infelizmente pregou os paths. Vamos colocar symlinks para enganar...
	if [ ! -e /usr/src/asterisk ]; then ln -s $BASEDIR/asterisk /usr/src/asterisk; fi
	if [ ! -e /usr/src/libpri ]; then ln -s $BASEDIR/libpri /usr/src/libpri; fi
	if [ ! -e /usr/include/voicerlib ]; then ln -s $BASEDIR/voicerlib-4.2.2.0 /usr/include/voicerlib; fi

	cd $BASEDIR/dgvchannel-1.0.5
	make clean
	make $MAKECPUS && echo "Digivoice Channel make OK!!!"
	make install
	make install_config && echo "Digivoice channel config OK..."
	make config && echo "Digivoice config autostart ok..."
fi
