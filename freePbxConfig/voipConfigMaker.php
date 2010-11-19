<?php
define(BASEDIR, dirname(__FILE__).DIRECTORY_SEPARATOR);
define(LIBDIR, dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."lib".DIRECTORY_SEPARATOR);
define(VOIPCONFIGELEMENTSDIR, LIBDIR."configElements".DIRECTORY_SEPARATOR);

include(LIBDIR."php-asmanager.php");
include(VOIPCONFIGELEMENTSDIR."VOIPXmlConfiguredElement.php");
include(VOIPCONFIGELEMENTSDIR."VOIPConference.php");
include(VOIPCONFIGELEMENTSDIR."VOIPConfig.php");
include(VOIPCONFIGELEMENTSDIR."VOIPInboundRoute.php");
include(VOIPCONFIGELEMENTSDIR."VOIPOutboundRoute.php");
include(VOIPCONFIGELEMENTSDIR."VOIPQueue.php");
include(VOIPCONFIGELEMENTSDIR."VOIPRingGroup.php");
include(VOIPCONFIGELEMENTSDIR."VOIPTrunk.php");
include(VOIPCONFIGELEMENTSDIR."VOIPUser.php");

$errors = array();

$config = parseConfig("myvoip", "127.0.0.1");


function parseConfig($basename, $server) {
	$xmlfile = dirname(__FILE__).DIRECTORY_SEPARATOR.$basename.DIRECTORY_SEPARATOR.$basename.'.xml';
	$xlsfile = dirname(__FILE__).DIRECTORY_SEPARATOR.$basename.DIRECTORY_SEPARATOR.$basename;
	if (! file_exists($xmlfile)) { VOIPXMLConfiguredElement::error("Can't find $xmlfile."); }

	
	$config = new VOIPConfig(new SimpleXMLElement(file_get_contents($xmlfile)));
	$config->findRefs();
	
	
	
	
	$objPHPExcel = phpexcel_createPHPExcelObject();
	$objPHPExcel->getProperties()->setCreator("Mercado Eletrônico - MEVOIP - $basename")
								 ->setLastModifiedBy("Mercado Eletrônico - MEVOIP - $basename")
								 ->setTitle("Mercado Eletrônico - MEVOIP - $basename")
								 ->setSubject("Mercado Eletrônico - MEVOIP - $basename")
								 ->setDescription("Mercado Eletrônico - MEVOIP - $basename")
								 ->setKeywords("Mercado Eletrônico - MEVOIP - $basename")
								 ->setCategory("Mercado Eletrônico - MEVOIP - $basename");

	$s = $objPHPExcel->setActiveSheetIndex(0);
	$objPHPExcel->getActiveSheet()->setTitle('VOIP PABX');

	$objPHPExcel->getDefaultStyle()->getFont()->setName('Tahoma');
	$objPHPExcel->getDefaultStyle()->getFont()->setSize(8); 
	
	$config->writeExcel($objPHPExcel);
	
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save($xlsfile.'.xls'); 
	
	
	
	
	
	$argv = @$_SERVER['argv'];
	if ($argv[1] == "run") {
		if (!hasErrors()) {
			VOIPXMLConfiguredElement::info("Previous errors not detected, applying configuration.");
			$config->applyConfigToFreePBX($server);
		} else {
			VOIPXMLConfiguredElement::error("Previous errors detected. STOPPING.");
		}
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