#! /bin/bash

# To start:
# aptitude install subversion; svn checkout http://debian-freepbx-config.googlecode.com/svn/trunk/ /opt/voip
# bash /opt/voip/voipBuild/buildMEVOIP.sh


set -e

BASEDIR="$( cd "$( dirname "$0" )" && pwd )" #/usr/src/mevoip
BASECONF=$BASEDIR/conf
NUMCPUS=$(cat /proc/cpuinfo | grep "^processor" | wc -l)
UPDATESVN=true
BUILDS=true
MAKECPUS="-j $NUMCPUS"
CLEAN=true
EXTRAS=true
BUILDASTERISK=true
BUILDDIGIVOICE=false
BUILDREDFONE=false
PAUSING="yes"
DGVSCHEME="unstable"
DEBIANVLIBDINIT=""
BUILDILBC="no"


FONULATOR=fonulator-2.0.3
LIBFB=libfb-2.0.2
FBFLASH=fb_flash-2.0.0


if [ "$DGVSCHEME" = "old" ]; then
	#Old version
	DGVCHANVER=dgvchannel-1.0.5
	DGVCHANURL="http://downloads.digivoice.com.br/pub/dgvchannel/old-releases/${DGVCHANVER}.tar.gz"
	VOICERVER=voicerlib-4.2.2.0
	VOICERURL="http://downloads.digivoice.com.br/pub/voicerlib/linux/old-releases/${VOICERVER}.tar.gz"
	DEBIANVLIBDINIT="debianvlibd.sh"
fi

if [ "$DGVSCHEME" = "unstable" ]; then
	# Unstable
	DGVCHANVER=dgvchannel-1.0.8_rc3
	DGVCHANURL="http://downloads.digivoice.com.br/pub/dgvchannel/unstable/${DGVCHANVER}.tar.gz"
	VOICERVER=voicerlib-4.2.3.0
	VOICERURL="http://downloads.digivoice.com.br/pub/voicerlib/linux/stable/${VOICERVER}.tar.gz"
	DEBIANVLIBDINIT="debianvlibd.sh"
fi

if [ "$DGVSCHEME" = "stable" ]; then
	# Stable
	DGVCHANVER=dgvchannel-1.0.6
	DGVCHANURL="http://downloads.digivoice.com.br/pub/dgvchannel/stable/${DGVCHANVER}.tar.gz"
	VOICERVER=voicerlib-4.2.3.0
	VOICERURL="http://downloads.digivoice.com.br/pub/voicerlib/linux/stable/${VOICERVER}.tar.gz"
	DEBIANVLIBDINIT="debianvlibd.sh"
fi


if [ -e /etc/debian_version ]; then
	DISTRO="Debian"
else
	DISTRO=$(lsb_release -s -i)
fi

chmod +x $BASEDIR/*.sh

whiptail --title "ME VOIP Asterisk Install" --msgbox "This insane script is going to install Asterisk and FreePBX on your $DISTRO system from scratch. You have $NUMCPUS CPUs... really? Using $DGVSCHEME Digivoice drivers: ${DGVCHANVER} and ${VOICERVER}." 12 78

# Proxy configuration.
MYPROXYHOST=$(whiptail --title "ME VOIP Asterisk Install" --inputbox "Please enter your proxy host (just the hostname, without port). If you don't have one, leave it empty." 12 78 "" 3>&1 1>&2 2>&3)
MYPROXYPORT=$(whiptail --title "ME VOIP Asterisk Install" --inputbox "Please enter your proxy port." 12 78 "" 3>&1 1>&2 2>&3)

if [ "a$MYPROXYHOST" = "a" ]; then
	# No proxy, removing it. I dunno?
	whiptail --title "ME VOIP Asterisk Install" --msgbox "Not using any proxies." 12 78
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
	DISTROPACKAGES="apache2-mpm-prefork mysql-client libmysqlclient-dev mysql-common mysql-server-5.1 mysql-client-5.1"
fi

# Debian Deps
aptitude -y install gcc g++ make libncurses5-dev subversion libcurl4-openssl-dev libiksemel-dev libogg-dev libpq-dev libreadline5-dev libsnmp-dev sox libsox-fmt-mp3 libsox-fmt-all \
	libssl-dev libvorbis-dev zlib1g-dev libsnmp9-dev libgmime-2.0-2-dev libspandsp-dev libnewt-dev libargtable2-dev libnet1-dev libpcap-dev $DISTROPACKAGES \
	php5-mysql php-pear php-db php5-gd freetds-common freetds-dev libspeex-dev libspeexdsp-dev unixodbc-dev libsqlite3-dev sqlite3 libsqlite0-dev sqlite \
	apache2 libapache2-mod-php5 curl wget nano less ccze postfix linux-headers-`uname -r` 
	
	
cd $BASEDIR

if [ "$BUILDDIGIVOICE" = "true" ]; then
	if [ ! -e ${DGVCHANVER}.tar.gz ]; then
		wget "$DGVCHANURL"
	fi

	if [ ! -e ${VOICERVER}.tar.gz ]; then
		wget "$VOICERURL"
	fi

	if [ ! -e ${VOICERVER} ]; then
		tar xzvf ${VOICERVER}.tar.gz
	fi

	if [ ! -e ${DGVCHANVER} ]; then
		tar xzvf ${DGVCHANVER}.tar.gz
	fi
fi

if [ "$UPDATESVN" = "true" ]; then
	if [ "a" = "a" ]; then
		echo "Getting FreePBX from SVN..."

		cd $BASEDIR
		svn co http://svn.freepbx.org/freepbx/branches/2.8 freepbx

		echo "Getting FreePBX from SVN (setup_svn.php)..."
		cd $BASEDIR/freepbx
		php5 setup_svn.php
	fi
	
	
	cd $BASEDIR
	svn co http://svn.digium.com/svn/asterisk/tags/1.6.2.20 asterisk
	
	if [ "$BUILDDAHDI" = "true" ]; then
		cd $BASEDIR
		svn co http://svn.digium.com/svn/dahdi/linux/tags/2.4.1.2 dahdi

		cd $BASEDIR
		svn co http://svn.digium.com/svn/dahdi/tools/tags/2.4.1 dahdi_tools
	fi
	
	cd $BASEDIR
	svn co http://svn.digium.com/svn/libpri/tags/1.4.11.5 libpri

	cd $BASEDIR
	svn co http://svn.digium.com/svn/asterisk-addons/tags/1.6.2.4 asterisk_addons
	
fi

if [ "$BUILDREDFONE" = "true" ]; then
	if [ ! -e openr2-1.3.1.tar.gz ]; then
		wget "http://openr2.googlecode.com/files/openr2-1.3.1.tar.gz"
	fi


	if [ ! -e "${FONULATOR}.tar.gz" ]; then
		wget "http://support.red-fone.com/downloads/fonulator/${FONULATOR}.tar.gz"
	fi

	if [ ! -e "${LIBFB}.tar.gz" ]; then
		wget "http://support.red-fone.com/downloads/fonulator/${LIBFB}.tar.gz"
	fi

	if [ ! -e "${FBFLASH}.tar.gz" ]; then
		wget "http://support.red-fone.com/fb_flash/${FBFLASH}.tar.gz"
	fi

	if [ ! -e openr2-1.3.1 ]; then
		tar xzvf openr2-1.3.1.tar.gz
	fi
	
	if [ ! -e ${FONULATOR} ]; then
		tar xzvf "${FONULATOR}.tar.gz"
	fi

	if [ ! -e ${LIBFB} ]; then
		tar xzvf "${LIBFB}.tar.gz"
	fi

	if [ ! -e ${FBFLASH} ]; then
		tar xzvf "${FBFLASH}.tar.gz"
	fi
fi


if [ "$BUILDASTERISK" = "true" ]; then

	if [ "$PAUSING" = "yes" ]; then
		whiptail --title "ME VOIP Asterisk Install" --msgbox "This will now BUILD A LOT of source code (Asterisk, LibPRI, DAHDI, etc)." 12 78
	fi
	
	if [ "$BUILDDAHDI" = "true" ]; then
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
	fi
	
	if [ "$BUILDREDFONE" = "true" ]; then
		if [ "$PAUSING" = "yes" ]; then
			whiptail --title "ME VOIP FreePBX REDFONE Install: ${LIBFB}" --msgbox "Going to build $BASEDIR/${LIBFB}" 12 78
		fi
		cd ${BASEDIR}/${LIBFB}
		./configure
		make 
		make install

		if [ "$PAUSING" = "yes" ]; then
			whiptail --title "ME VOIP FreePBX REDFONE Install: ${FONULATOR}" --msgbox "Going to build $BASEDIR/${FONULATOR}" 12 78
		fi
		cd ${BASEDIR}/${FONULATOR}
		./configure
		make 
		make install

		if [ "$PAUSING" = "yes" ]; then
			whiptail --title "ME VOIP FreePBX REDFONE Install: ${FBFLASH}" --msgbox "Going to build $BASEDIR/${FBFLASH}" 12 78
		fi
		cd ${BASEDIR}/${FBFLASH}
		./configure
		make 
		make install

		cd $BASEDIR/libpri
		if [ "$CLEAN" = "true" ]; then make clean; fi
		make $MAKECPUS && echo "*************** Make libPri OK!"""
		make install
		
		cd $BASEDIR/openr2-1.3.1
		./configure
		if [ "$CLEAN" = "true" ]; then make clean; fi
		make $MAKECPUS && echo "*************** Make OpenR2 OK!"""
		make install
	fi


	
	

	cd $BASEDIR/asterisk
	if [ "$CLEAN" = "true" ]; then make distclean; fi
	echo "1.6.2.20-PARDINI" > $BASEDIR/asterisk/.version && echo "**** Set Asterisk version... forced... beware"

	if [ "$BUILDILBC" = "true" ]; then
		cd $BASEDIR/asterisk
		if [ ! -f codecs/ilbc/iLBC_define.h ]; then
			wget -P codecs/ilbc http://www.ietf.org/rfc/rfc3951.txt
			(cd codecs/ilbc && awk -f extract-cfile.awk rfc3951.txt)
			wget -q -O - http://www.ilbcfreeware.org/documentation/extract-cfile.awk | sed -e 's/\r//g' > codecs/ilbc/extract-cfile.awk
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

	cp $BASEDIR/asterisk.menuselect.makeopts $BASEDIR/asterisk/menuselect.makeopts
	#make menuselect
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
	cp $BASEDIR/asterisk_addons.menuselect.makeopts $BASEDIR/asterisk_addons/menuselect.makeopts
	#make menuselect
	make $MAKECPUS
	make install
	make samples

	echo "Ate aqui tudo bem!"
fi


if [ "$BUILDDIGIVOICE" = "true" ]; then

	if [ "$PAUSING" = "yes" ]; then
		whiptail --title "ME VOIP Asterisk Install" --msgbox "This will now BUILD DigiVoice Stuff (VoicerLib, chan_dgv)." 12 78
	fi

	cd $BASEDIR/${VOICERVER}/driver/linux
	cp $BASEDIR/$DEBIANVLIBDINIT vlibd.debian

	cd $BASEDIR/${VOICERVER}
	make clean
	make $MAKECPUS && echo "Digivoice VoicerLib make OK!!!"
	make install
	make config && echo "Digivoice Voircelib linux driver config OK!!!"

	## a Digivoice infelizmente pregou os paths. Vamos colocar symlinks para enganar...
	if [ ! -e /usr/src/asterisk ]; then ln -s $BASEDIR/asterisk /usr/src/asterisk; fi
	if [ ! -e /usr/src/libpri ]; then ln -s $BASEDIR/libpri /usr/src/libpri; fi
	if [ ! -e /usr/include/voicerlib ]; then ln -s $BASEDIR/${VOICERVER} /usr/include/voicerlib; fi

	cd $BASEDIR/${DGVCHANVER}
	make clean
	make $MAKECPUS && echo "Digivoice Channel make OK!!!"
	make install
	make install_config && echo "Digivoice channel config OK..."
	make config && echo "Digivoice config autostart ok..."
fi


echo "Copying default configuration..."

cp -v $BASECONF/asterisk/logger.conf /etc/asterisk/

if [ "$BUILDREDFONE" = "true" ]; then
	cp -v $BASECONF/dahdi/init.conf /etc/dahdi/
	cp -v $BASECONF/dahdi/modules /etc/dahdi/
	cp -v $BASECONF/dahdi/system.conf /etc/dahdi/
	cp -v $BASECONF/asterisk/chan_dahdi.conf /etc/asterisk/
	cp -v $BASECONF/init.d/fonulator /etc/init.d/
	cp -v $BASECONF/redfone.conf /etc/redfone.conf
fi

if [ "$BUILDDIGIVOICE" = "true" ]; then
	cp -v $BASECONF/init.d/dgvfifo /etc/init.d/
	cp -v $BASECONF/init.d/vlibd /etc/init.d/
fi

if [ "$FIXMUNIN" = "true" ]; then
	cp -v $BASECONF/init.d/munin-node /etc/init.d/
	cp -v $BASECONF/munin/plugin-conf.d/munin-node /etc/munin/plugin-conf.d/munin-node
fi

