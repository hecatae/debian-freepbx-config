<?php
class VOIPConference extends VOIPXmlConfiguredElement  {

	public function applyConfigToFreePBX() {
		/*
		$this->config->queryExec("DELETE from meetme WHERE exten = '{$this->extension}'");
		*/
		
		$this->config->insert("meetme", array(
			'exten' => "{$this->extension}",
			'options' => "oTcir",
			'userpin' => "",
			'adminpin' => "",
			'description' => $this->translateChars($this->name),
			'joinmsg_id' => "0",
			'music' => "inherit",
			'users' => "0",		
			));
	}
}
?>