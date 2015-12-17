An automated Asterisk FreePBX build system based on a Debian Lenny, Debian Squeeze, Ubuntu 10.04 or Ubuntu 11.10 barebones server installation.

An automatic FreePBX massive data-load and management system based on XML files, handled by PHP scripts, featuring:

  * Automatic IVR (aka URA, _Unidade de Resposta Audível_) creation, using Google's Translator as Voice Engine. All prompts are defined in the XML in plain English (or Portuguese)
  * Keeping FreePBX configuration versioned eg in Subversion
  * Custom outbound-dialing rules per-user
  * Queue creation and static member management
  * Automatic inbound route creation for DDR (_Discagem Direta a Ramal_) for ringgroups, queues, etc
  * Support for many hardware devices
    * All softphones
    * Sipura/Linksys FXS/FXO
    * Grandstream FXO's
    * Digivoice trunks
    * Redfone E1 trunks
  * Fully FreePBX 2.8.x based. The loading only changes data in FreePBX's MySQL database. Can be removed at any time, and leave a functional FreePBX installation.
  * The exception is for outbound rules/policies, using custom contexts for that.


`DigiVoice (brazilian R2 telephony cards) and Redfone E1 hardware support.`


## Getting Started ##

Download install Debian/Ubuntu Server. Enable SSH etc.
```
# aptitude install subversion; svn checkout http://debian-freepbx-config.googlecode.com/svn/trunk/ /opt/voip
# bash /opt/voip/voipBuild/buildMEVOIP.sh
# bash /opt/voip/voipBuild/setupMEVOIPFreePBX.sh
```