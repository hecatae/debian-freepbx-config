#! /bin/bash

BASEDIR=/opt/mevoip/freePbxConfig 
cd $BASEDIR/../
svn update

chmod +x $BASEDIR/*.sh

if [ ! -e /usr/local/sbin/voipConfigMaker ]; then
	echo "Symlinking..."
	ln -s $BASEDIR/voipConfigMaker.sh /usr/local/sbin/voipConfigMaker
fi


rm /opt/freepbx/html/admin/common/mainstyle.css
rm /opt/freepbx/html/admin/images/freepbx_large.png
ln -s $BASEDIR/webCustom/mainstyle.css /opt/freepbx/html/admin/common/mainstyle.css
ln -s $BASEDIR/webCustom/freepbx_large.png /opt/freepbx/html/admin/images/freepbx_large.png


cd $BASEDIR
php5 voipConfigMaker.php run $@


