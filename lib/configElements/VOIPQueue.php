<?php
class VOIPQueue extends VOIPXmlConfiguredElement  {
	// <queue extension="3001" name="Fila do STC" prefix="STC" recording="wav" sla="60" announce="30" />
	public $prefix = null;
	public $recording = null;
	public $sla = null;
	public $announce = null;
	public $staticUsers = array();

	public function parse() {
		$this->prefix = $this->readXMLAttrString("prefix");
		$this->recording = $this->readXMLAttrString("recording");
		$this->sla = $this->readXMLAttrInt("sla");
		$this->announce = $this->readXMLAttrInt("announce");
	}
	
	public function getExcelColumnVars() {
		return array(
			'extension' => null, 
			'name' => null,
			'strategy' => null,
			'prefix' => null,
			'recording' => null,
			'sla' => null,
			'announce' => null,
			'users' => $this->getArrayItemInfo($this->staticUsers)
			);
	}
		
	
	public function applyConfigToFreePBX() {
		/*
		$this->config->queryExec("DELETE from queues_config WHERE extension = '{$this->extension}'");
		$this->config->queryExec("DELETE from queues_details WHERE id = '{$this->extension}'");
		*/
		$this->config->insert("queues_config", array(
			'extension' => "{$this->extension}",
			'descr' => $this->translateChars($this->name),
			'grppre' => $this->getPrefixOrEmpty($this->prefix),
			'alertinfo' => "",
			'ringing' => "0",
			'maxwait' => "",
			'password' => "",
			'ivr_id' => "none",
			'dest' => "app-blackhole,busy,1",
			'cwignore' => "2",
			'queuewait' => "0",
			'use_queue_context' => "0",
			'togglehint' => "0",
			'qregex' => "",
			'agentannounce_id' => "0",
			'joinannounce_id' => "0"
			));
		
		$this->config->addQueueDetailInsert($this->extension, "announce-frequency", "30");
		$this->config->addQueueDetailInsert($this->extension, "announce-holdtime", "yes");
		$this->config->addQueueDetailInsert($this->extension, "announce-position", "yes");
		$this->config->addQueueDetailInsert($this->extension, "autofill", "no");
		$this->config->addQueueDetailInsert($this->extension, "eventmemberstatus", "no");
		$this->config->addQueueDetailInsert($this->extension, "eventwhencalled", "no");
		$this->config->addQueueDetailInsert($this->extension, "joinempty", "yes");
		$this->config->addQueueDetailInsert($this->extension, "leavewhenempty", "no");
		$this->config->addQueueDetailInsert($this->extension, "maxlen", "0");
		$this->config->addQueueDetailInsert($this->extension, "monitor-format", "wav");
		$this->config->addQueueDetailInsert($this->extension, "monitor-join", "yes");
		$this->config->addQueueDetailInsert($this->extension, "periodic-announce-frequency", "0");
		$this->config->addQueueDetailInsert($this->extension, "queue-callswaiting", "queue-callswaiting");
		$this->config->addQueueDetailInsert($this->extension, "queue-thankyou", "queue-thankyou");
		$this->config->addQueueDetailInsert($this->extension, "queue-thereare", "queue-thereare");
		$this->config->addQueueDetailInsert($this->extension, "queue-youarenext", "queue-youarenext");
		$this->config->addQueueDetailInsert($this->extension, "reportholdtime", "yes");
		$this->config->addQueueDetailInsert($this->extension, "retry", "5");
		$this->config->addQueueDetailInsert($this->extension, "ringinuse", "no");
		$this->config->addQueueDetailInsert($this->extension, "servicelevel", "60");
		$this->config->addQueueDetailInsert($this->extension, "strategy", "ringall");
		$this->config->addQueueDetailInsert($this->extension, "timeout", "15");
		$this->config->addQueueDetailInsert($this->extension, "weight", "0");
		$this->config->addQueueDetailInsert($this->extension, "wrapuptime", "0");
		
		// Membros estáticos:
		foreach ($this->getOrderedArrayItemInfo($this->staticUsers) as $user) {
			$this->config->addQueueDetailInsert($this->extension, "member", "Local/{$user->extension}@from-queue/n,0");
		}
	}
	
}
?>