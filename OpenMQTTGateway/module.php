<?
    // Klassendefinition
    class OpenMQTTGateway extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		$this->ConnectParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");
	
		
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString('MQTTBaseTopic', 'OpenMQTTGateway');
        	$this->RegisterPropertyString('MQTTTopic', '');
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableString("Uptime", "Uptime", "", 20);
		$this->RegisterVariableString("Type", "Typ", "", 30);
		$this->RegisterVariableString("Version", "Version", "", 40);
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 50);
		$this->RegisterVariableInteger("RSSI", "RSSI", "", 60);
		
		$this->RegisterVariableString("SSID", "SSID", "", 80);
		$this->RegisterVariableString("BSSID", "BSSID", "", 90);
		$this->RegisterVariableString("IP", "IP", "", 100);
		$this->RegisterVariableString("MAC", "MAC", "", 110);
		
		/**
		Payload: {"uptime":7803,"discovery":false,"freemem":103880,"mqttport":"1024","mqttsecure":false,"freestack":1760,"lowpowermode":-1,"interval":55555,"intervalcnct":3600000,"scnct":119,"modules":["BT"]}
	
		**/
        }
       	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox", "caption" => "Aktiv"); 
		
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "MQTTBaseTopic", "caption" => "MQTT Base Topic");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "MQTTTopic", "caption" => "MQTT Topic");
		
		$arrayActions = array(); 
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	} 
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
		
		 //Setze Filter für ReceiveData
		$Filter1 = preg_quote('"Topic":"' . $this->ReadPropertyString('MQTTBaseTopic') . '/' . $this->ReadPropertyString('MQTTTopic') . '"');
		$Filter2 = preg_quote('"Topic":"symcon/' . $this->ReadPropertyString('MQTTBaseTopic') . '/' . $this->ReadPropertyString('MQTTTopic') . '/');
		
		$this->SendDebug('Filter ', '.*(' . $Filter1 . '|' . $Filter2 . ').*', 0);
        	$this->SetReceiveDataFilter('.*(' . $Filter1 . '|' . $Filter2 . ').*');
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		
		}	   
	}      
	    
	public function ReceiveData($JSONString) 
	{
		// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
		
		$QualityOfService = utf8_decode($Data->QualityOfService);
		$Retain = utf8_decode($Data->Retain);
		$Topic = utf8_decode($Data->Topic);
		$Payload = utf8_decode($Data->Payload);
		
		$PayloadData = json_decode($Payload);
		
		$this->SetValue("LastUpdate", time() );
		
		$Uptime = utf8_decode($PayloadData->uptime);
		$this->SetValueWhenChanged("Uptime", $Uptime);
		
		$Type = utf8_decode($PayloadData->env);
		$this->SetValueWhenChanged("Type", $Type);
		
		$Version = utf8_decode($PayloadData->version);
		$this->SetValueWhenChanged("Version", $Version);
		
		$Temperature = utf8_decode($PayloadData->tempc);
		$this->SetValueWhenChanged("Temperature", $Temperature);
		
		$RSSI = utf8_decode($PayloadData->rssi);
		$this->SetValueWhenChanged("RSSI", $RSSI);
		
		$SSID = utf8_decode($PayloadData->SSID);
		$this->SetValueWhenChanged("SSID", $SSID);
		
		$BSSID = utf8_decode($PayloadData->BSSID);
		$this->SetValueWhenChanged("BSSID", $BSSID);
		
		$IP = utf8_decode($PayloadData->ip);
		$this->SetValueWhenChanged("IP", $IP);
		
		$MAC = utf8_decode($PayloadData->mac);
		$this->SetValueWhenChanged("MAC", $MAC);
	}
	    
	private function SetValueWhenChanged($Ident, $Value)
    	{
        	if ($this->GetValue($Ident) != $Value) {
            		$this->SetValue($Ident, $Value);
        	}
    	}    
}
?>
