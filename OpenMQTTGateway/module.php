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
	
		/**
		// Profile anlegen
		$this->RegisterProfileFloat("Mopeka.sek", "Clock", "", " sek", 0, 20, 1, 2);
		**/
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 40);
		/**
		
		Topic: home/OpenMQTTGateway_ESP32_BLE/SYStoMQTT, 
		Payload: {"uptime":7803,"version":"v1.5.1","discovery":false,"env":"esp32dev-ble","freemem":103880,"mqttport":"1024","mqttsecure":false,"tempc":48.88889,"freestack":1760,"rssi":-28,"SSID":"Paeper_Caravan","BSSID":"60:32:B1:BE:99:8E","ip":"192.168.1.106","mac":"A0:B7:65:58:DE:E4","lowpowermode":-1,"interval":55555,"intervalcnct":3600000,"scnct":119,"modules":["BT"]}

		$this->RegisterVariableFloat("BatteryVoltage", "Batterie Spannung", "~Volt", 20);
		$this->RegisterVariableInteger("BatteryPercentage", "Batterie Prozentual", "~Intensity.100", 30);
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 40);
		$this->RegisterVariableInteger("RSSI", "RSSI", "", 50);
		$this->RegisterVariableInteger("GasLevel", "Gas Füllstand", "~Intensity.100", 60);
		$this->RegisterVariableInteger("QualityStars", "Qualitäts Sterne", "", 70);
		$this->RegisterVariableFloat("UpdateRate", "Update Rate", "Mopeka.sek", 80);
		$this->RegisterVariableBoolean("SyncPressed", "Sync gedrückt", "~Switch", 90);
		$this->RegisterVariableInteger("AcceloX", "Lage X-Wert", "", 100);
		$this->RegisterVariableInteger("AcceloY", "Lage Y-Wert", "", 110);
		$this->RegisterVariableBoolean("PositionWarning", "Positions Warnung", "~Switch", 120);
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

		if (isset($Data->PacketType)) {
	    		$PacketType = utf8_decode($Data->PacketType);
		} else {
			return;
		}
		$QualityOfService = utf8_decode($Data->QualityOfService);
		$Retain = utf8_decode($Data->Retain);
		$Topic = utf8_decode($Data->Topic);
		$Payload = utf8_decode($Data->Payload);
		
		$PayloadData = json_decode($Payload);
		
		$this->SetValue("LastUpdate", time() );

		$RSSI = utf8_decode($PayloadData->rssi);
		$this->SetValueWhenChanged("RSSI", $RSSI);
		
		$Temperature = utf8_decode($PayloadData->tempc);
		$this->SetValueWhenChanged("Temperature", $Temperature);
		
		/**
		$Battery = utf8_decode($PayloadData->volt);
		$this->SetValueWhenChanged("BatteryVoltage", $Battery);

		$BatteryPercentage = utf8_decode($PayloadData->batt);
		$this->SetValueWhenChanged("BatteryPercentage", $BatteryPercentage);

		$Temperature = utf8_decode($PayloadData->tempc);
		$this->SetValueWhenChanged("Temperature", $Temperature);

		$Level_cm = floatval(utf8_decode($PayloadData->lvl_cm));
		$TankLevel_rel = (($Level_cm * 10) / $this->GasBottleValue() ) * 100;
		$TankLevel_rel = min(100, max(0, $TankLevel_rel));
		$this->SetValueWhenChanged("GasLevel", $TankLevel_rel);

		$QualityStars = utf8_decode($PayloadData->quality);
		$this->SetValueWhenChanged("QualityStars", $QualityStars);

		$SyncPressed = boolval(utf8_decode($PayloadData->sync));
		$this->SetValueWhenChanged("SyncPressed", boolval($SyncPressed));

		if(isset($PayloadData->accx)){                                                                                                                                                                       
			$AcceloX = utf8_decode($PayloadData->accx);
			$this->SetValueWhenChanged("AcceloX", $AcceloX);
		} else {
			$AcceloX = 0;
			$this->SetValueWhenChanged("AcceloX", 0);
		}

		if(isset($PayloadData->accy)){                                                                                                                                                                       
			$AcceloY = utf8_decode($PayloadData->accy);
			$this->SetValueWhenChanged("AcceloY", $AcceloY);
		} else {
			$AcceloY = 0;
			$this->SetValueWhenChanged("AcceloY", 0);
		} 

		if (($AcceloX <= 2) AND ($AcceloX >= -2) AND ($AcceloY <= 2) AND ($AcceloY >= -2)) {
			$this->SetValueWhenChanged("PositionWarning", false);
		} else {
			$this->SetValueWhenChanged("PositionWarning", true);
		}
		**/
	}
	    
	private function SetValueWhenChanged($Ident, $Value)
    	{
        	if ($this->GetValue($Ident) != $Value) {
            		$this->SetValue($Ident, $Value);
        	}
    	}    
}
?>
