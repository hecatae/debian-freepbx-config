<?php
class VOIPXmlConfiguredElement {
	protected $xml = null;
	protected $config = null;
	
	public $extension = null;
	public $name = null;
	public function getId() { return $this->extension; }
	public function getName() { return $this->name; }
	
	public function __construct($xml, $config=null, $addTo=null, $callable=false) {
		//echo "Constructor...\n";
		$this->xml = $xml;
		$this->config = $config;
		
		// Protect this against evil?
		$this->extension = $this->readXMLAttrInt("extension");
		$this->name = $this->readXMLAttrString("name");
		
		$this->parse();
		$this->xml = null;
		
		if ($addTo) {
			$varName = $addTo;
			$theId = $this->getId();
			if ($old = @$this->config->{$varName}[$theId]) {
				$oldname = $old->name;
				$newname = $this->name;
				$this->error("Duplicate extension entry in $varName for '$theId'. '$oldname' vs '$newname'");
			}
			$this->config->{$varName}[$this->getId()] = $this;
			

			$varName = $addTo."Names";
			$theId = $this->getName();
			if ($old = @$this->config->{$varName}[$theId]) {
				$oldname = $old->extension;
				$newname = $this->extension;
				$this->error("Duplicate name entry in $varName for '$theId'. '$oldname' vs '$newname'");
			
			}
			
			$this->config->{$varName}[$this->getName()] = $this;
			
			if ($callable) {
				$varName = 'callables';
				$theId = $this->getId();
				if ($old = @$this->config->{$varName}[$theId]) {
					$oldname = get_class($old) . "-" . $old->name;
					$newname = get_class($this) . "-" . $this->name;
					$this->error("Duplicate callable extension for '$theId'. '$oldname' vs '$newname'");
				}
				$this->config->{$varName}[$this->getId()] = $this;
			}
		}
	}
	
	public function writeExcelHeader($values, $sheetName='Another Sheet') {
		// Handle the previous sheet cols...
		if (@$this->config->xlsSheet) {
			//$this->config->excelMaxCol
			for ($i = 0; $i < $this->config->excelMaxCol; $i++) {
				$width = @$coldata['width'];
				$cell = $this->config->xlsSheet->getCellByColumnAndRow($i, 1);
				$this->config->xlsSheet->getColumnDimension($cell->getColumn())->setAutoSize(true);
			}		
		}
	
	
		$this->config->xlsSheet = $this->config->excel->createSheet();
		$this->config->xlsSheet->setTitle($sheetName);
		$this->info("Writing header excel... $sheetName");
		$this->config->currExcelLine = 1;
		$colnum = 0;
		foreach ($values as $value) {
			$this->config->xlsSheet->setCellValueByColumnAndRow($colnum, $this->config->currExcelLine, utf8_encode($value));

			$cell = $this->config->xlsSheet->getCellByColumnAndRow($colnum, $this->config->currExcelLine);
			$styleArray = array('font' => array('bold' => true) ,'alignment' => array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT));
			$this->config->xlsSheet->getStyle($cell->getCoordinate())->applyFromArray($styleArray);
			$colnum++;
		}
		$this->config->currExcelLine++;
		$this->config->excelMaxCol = $colnum;
	}
	
	
	public function writeExcelItem($values) {
		$colnum = 0;
		$this->debug("Writing excel line item at {$this->config->currExcelLine}");
		foreach ($values as $value) {
			$this->config->xlsSheet->setCellValueByColumnAndRow($colnum, $this->config->currExcelLine, utf8_encode($value));
			$colnum++;
		}
		$this->config->currExcelLine++;
	}
		
	
	public function getExcelColumnVars() {
		return array('extension' => null, 'name' => null);
	}
	
	public function excelWriteHeader() {
		$this->writeExcelHeader(array_keys($this->getExcelColumnVars()), get_class($this));
	}
	
	public function excelWriteItem() {
		$vars = $this->getExcelColumnVars();
		$valueArr = array();
		foreach ($vars as $varName => $data) {
			if (!is_null($data)) {
				$value = $data;
			} else {
				$value = $this->{$varName};
			}
			$valueArr[] = $value;
		}
	
		$this->writeExcelItem($valueArr);
	}
	
	
	public function parse() {
	}
	
	public function findRefs() {
	}
	
	
	public function applyConfigToFreePBX() {
	
	
	
	}	
	
	function readXMLAttrString($name) {
		return utf8_decode((string)$this->xml[$name]);
	}
	
	function readXMLAttrInt($name) {
		return utf8_decode((int)$this->xml[$name]);
	}
	
	function explodeAndClean($txt) {
		$ret = array();
		$partes = explode(",", $txt);
		foreach ($partes as $parte) {
			$parte = trim($parte);
			if (!$parte) continue;
			$ret[] = $parte;
		}
		return $ret;
	}
	
	public function getArrayItemInfo($arr) {
		if (count($arr) == 0) return "";
		$rgsTxt = array();
		foreach ($arr as $rgInfo) {
			$item = $rgInfo['item']->name . "[" . $rgInfo['item']->extension . "]" . "-" . "(" . $rgInfo['prio'] . ")";
			$rgsTxt[] = $item;
		}
		return implode(", ", $rgsTxt);
	
	}
	
	public function getOrderedArrayItemInfo($arr) {
		$ord = $arr;
		uasort($ord, array('VOIPXmlConfiguredElement', 'arrayItemInfoSorter'));
		
		$ret = array();
		foreach ($ord as $item) {
			$ret[] = $item['item'];
		}
		
		return $ret;
	}
	
	static function arrayItemInfoSorter($aa, $bb) {
		$a = $aa['prio'];
		$b = $bb['prio'];
		if ($a == $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;		
	}
	
	public function getPrefixOrEmpty($pref) {
		if (!$pref) return "";
		return $pref.":";
	}
	
	public function translateChars($txt, $trSpaces=false) {
		$txt= strtr($txt,
		"ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ_",
		"SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy ");
		
		if ($trSpaces) {
			$txt= strtr($txt,
			" ",
			"_");
		}
		return $txt;
	}
	
	function splitParentheses($txt) {
		$ret = array();
		$partes = explode("(", $txt);
		$ret[] = $partes[0];
		
		$theprio = 1;
		if ($partes[1]) {
			$again = explode(")", $partes[1]);
			$theprio = (int)$again[0];
		}
		$ret[] = $theprio ? $theprio : 1;
		
		return $ret;
	}
	
	public function error($txt) {
		global $errors;
		echo "-=[";
		if (consoleColor()) echo "\033[31;1m";
		echo "ERROR";
		if (consoleColor()) echo "\033[0m";
		echo "]=-=[";
		if (consoleColor()) echo "\033[37;1m";
		echo "$txt";
		if (consoleColor()) echo "\033[0m";
		echo "\n";
		$errors[] = $txt;
	}
	
	public function info($txt, $returnEnd = true) {
		echo "-=[";
		if (consoleColor()) echo "\033[36;1m";
		echo "INFO";
		if (consoleColor()) echo "\033[0m";
		echo "]=-=[";
		if (consoleColor()) echo "\033[37;1m";
		echo "$txt";
		if (consoleColor()) echo "\033[0m";
		if ($returnEnd) echo "\n";
	}
	
	public function sayOK() {
		if (consoleColor()) echo "\033[32;1m";
		echo "OK";
		if (consoleColor()) echo "\033[0m";
		echo "!";
	}
	
	public function warn($txt) {
		echo "-=[";
		if (consoleColor()) echo "\033[33;1m";
		echo "WARN";
		if (consoleColor()) echo "\033[0m";
		echo "]=-=[";
		if (consoleColor()) echo "\033[37;1m";
		echo "$txt";
		if (consoleColor()) echo "\033[0m";
		echo "\n";
	}
	
	public function debug($txt) {
		if (!isDebug()) return;
		echo "-=[";
		if (consoleColor()) echo "\033[37;1m";
		echo "DEBUG";
		if (consoleColor()) echo "\033[0m";
		echo "]=-=[";
		if (consoleColor()) echo "\033[37;1m";
		echo "$txt";
		if (consoleColor()) echo "\033[0m";
		echo "\n";
	}	
	
}
?>