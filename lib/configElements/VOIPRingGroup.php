<?php
class VOIPRingGroup extends VOIPXmlConfiguredElement  {
	// <ringgroup extension="4001" name="Desenvolvimento" strategy="ringall" prefix="DEV"/>
	public $strategy = null;
	public $prefix = null;
	public $users = array();
	public $postdest = null;
	
	public function parse() {
		$this->strategy = $this->readXMLAttrString("strategy");
		$this->prefix = $this->readXMLAttrString("prefix");
		$this->postdest = $this->readXMLAttrString("postdest");
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'strategy' => null,
			'prefix' => null,
			'users' => $this->getArrayItemInfo($this->users)
			);
	}
	
	
	
	public function applyConfigToFreePBX() {
		$u = array();
		$after = $this->getOrderedArrayItemInfo($this->users);
		foreach ($after as $user) {
			$u[] = $user->extension;
		}
		$lista = implode("-", $u);
		/*
		$this->config->queryExec("DELETE from ringgroups WHERE grpnum = '{$this->extension}'");
		*/
		$this->config->insert("ringgroups", array(
			'grpnum' => "{$this->extension}",
			'strategy' => "{$this->strategy}",
			'grptime' => "20",
			'grppre' => $this->getPrefixOrEmpty($this->prefix),
			'grplist' => "$lista",
			'annmsg_id' => "0",
			'postdest' => $this->postdest? $this->postdest :"app-blackhole,busy,1",
			'description' => $this->translateChars($this->name),
			'alertinfo' => "",
			'remotealert_id' => "0",
			'needsconf' => "",
			'toolate_id' => "0",
			'ringing' => "Ring",
			'cwignore' => "CHECKED",
			'cfignore' => ""
			));
		$this->config->astDbSet("RINGGROUP", "{$this->extension}/changecid", "default");
		$this->config->astDbSet("RINGGROUP", "{$this->extension}/fixedcid", "");
	}

}
?>