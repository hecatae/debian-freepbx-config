<?php
error_reporting(E_ALL);
define('BASEDIR', dirname(__FILE__).DIRECTORY_SEPARATOR);
define('LIBDIR', dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR);
define('VOIPCONFIGELEMENTSDIR', LIBDIR."configElements".DIRECTORY_SEPARATOR);

include(LIBDIR."php-asmanager.php");
include(LIBDIR."tts.php");
include(VOIPCONFIGELEMENTSDIR."VOIPXmlConfiguredElement.php");
include(VOIPCONFIGELEMENTSDIR."VOIPConference.php");
include(VOIPCONFIGELEMENTSDIR."VOIPConfig.php");
include(VOIPCONFIGELEMENTSDIR."VOIPInboundRoute.php");
include(VOIPCONFIGELEMENTSDIR."VOIPOutboundRoute.php");
include(VOIPCONFIGELEMENTSDIR."VOIPQueue.php");
include(VOIPCONFIGELEMENTSDIR."VOIPRingGroup.php");
include(VOIPCONFIGELEMENTSDIR."VOIPTrunk.php");
include(VOIPCONFIGELEMENTSDIR."VOIPUser.php");
include(VOIPCONFIGELEMENTSDIR."VOIPChannelMap.php");
include(VOIPCONFIGELEMENTSDIR."VOIPSound.php");
include(VOIPCONFIGELEMENTSDIR."VOIPURA.php");
include(VOIPCONFIGELEMENTSDIR."VOIPRecording.php");
include(VOIPCONFIGELEMENTSDIR."VOIPAnnounce.php");
include(VOIPCONFIGELEMENTSDIR."VOIPMiscApp.php");



error_reporting(E_ALL);
$errors = array();

$config = parseConfig("myvoip", "127.0.0.1");


function parseConfig($basename, $server) {
	$xmlfile = '/etc/voip/voipconfig.xml'; 
	$xlsfile = dirname(__FILE__).DIRECTORY_SEPARATOR.$basename;
	if (! file_exists($xmlfile)) { VOIPXMLConfiguredElement::error("Can't find $xmlfile."); }

	
	$config = new VOIPConfig(new SimpleXMLElement(file_get_contents($xmlfile)));
	$config->findRefs();
	
	
	
	if (createExcel()) {
		$objPHPExcel = phpexcel_createPHPExcelObject();
		$objPHPExcel->getProperties()->setCreator("freePbxConfig - MEVOIP - $basename")
									 ->setLastModifiedBy("freePbxConfig - MEVOIP - $basename")
									 ->setTitle("freePbxConfig - MEVOIP - $basename")
									 ->setSubject("freePbxConfig - MEVOIP - $basename")
									 ->setDescription("freePbxConfig - MEVOIP - $basename")
									 ->setKeywords("freePbxConfig - MEVOIP - $basename")
									 ->setCategory("freePbxConfig - MEVOIP - $basename");

		$s = $objPHPExcel->setActiveSheetIndex(0);
		$objPHPExcel->getActiveSheet()->setTitle('VOIP PABX');

		$objPHPExcel->getDefaultStyle()->getFont()->setName('Tahoma');
		$objPHPExcel->getDefaultStyle()->getFont()->setSize(8); 
		
		$config->writeExcel($objPHPExcel);
		
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		$objWriter->save($xlsfile.'.xls'); 
	}
	
	if (true) {
		$serdata = array();
		$serdata['adLogin'] = $config->getUserArrayByField($config->users, 'adLogin');
		$serdata['ext'] =  $config->getUserArrayByField($config->users, 'extension');
		$serdata['area'] =  $config->getUserArrayByField($config->users, 'area', false);
		$serdata['queues'] =  $config->getUserArrayByField($config->queues, 'extension');
		$serdata['groups'] =  $config->getUserArrayByField($config->ringGroups, 'extension');
		$serdata['confs'] =  $config->getUserArrayByField($config->confs, 'extension');
		$provision = dirname(__FILE__).DIRECTORY_SEPARATOR.$basename.DIRECTORY_SEPARATOR.$basename.'.dat';
		file_put_contents($provision, serialize($serdata));
		VOIPXMLConfiguredElement::info("Wrote provisioning info to $provision.");
	}
	
	$argv = @$_SERVER['argv'];
	if (@$argv[1] == "run") {
		if (!hasErrors()) {
			VOIPXMLConfiguredElement::info("Previous errors not detected, applying configuration.");
			$config->applyConfigToFreePBX($server);
		} else {
			VOIPXMLConfiguredElement::error("Previous errors detected. STOPPING.");
		}
	} else {
		$config->generateOutboundRulesFile('outbound_rules.conf');
	}
	
	
	//print_r($config);
}

function phpexcel_createPHPExcelObject() {
	/** PHPExcel */
	require_once LIBDIR.'PHPExcel.php';

	/** PHPExcel_IOFactory */
	require_once LIBDIR.'PHPExcel/IOFactory.php';

	/** PHPExcel_Cell_AdvancedValueBinder */
	require_once LIBDIR.'PHPExcel/Cell/AdvancedValueBinder.php';

	// Create new PHPExcel object
	$objPHPExcel = new PHPExcel();

	return $objPHPExcel;
}

function createExcel() {
	return true;
}

function hasErrors() {
	global $errors;
	return count($errors) > 0;
}

function isDebug() {
	$argv = @$_SERVER['argv'];
	if (in_array('debug', $argv)) return true;
	return false;
}

function consoleColor() { return DIRECTORY_SEPARATOR != "\\"; }
?>