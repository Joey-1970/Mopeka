<?
    // Klassendefinition
    class MopekaGasSensor extends IPSModule 
    {
	// https://community.symcon.de/t/hilfe-bei-der-umsetzung-von-java-in-php/125424
	// https://community.victronenergy.com/questions/52274/venus-raspberry-pi-read-other-ble-device.html
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		$this->ConnectParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");
	
		
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("MAC", "00:00:00:00:00:00");
		$this->RegisterPropertyInteger("GasBottleValue", 0);
		$this->RegisterPropertyInteger("IndividualLevel", 36);
		
		// Profile anlegen
		$this->RegisterProfileFloat("Mopeka.sek", "Clock", "", " sek", 0, 20, 1, 2);
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableFloat("BatteryVoltage", "Betterie Spannung", "~Volt", 20);
		$this->RegisterVariableInteger("BatteryPercentage", "Batterie Prozentual", "~Intensity.100", 30);
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 40);
		$this->RegisterVariableInteger("RSSI", "RSSI", "", 50);
		$this->RegisterVariableInteger("GasLevel", "Gas Füllstand", "~Intensity.100", 60);
		$this->RegisterVariableInteger("QualityStars", "Qualitäts Sterne", "", 70);
		$this->RegisterVariableFloat("UpdateRate", "Update Rate", "Mopeka.sek", 80);
		$this->RegisterVariableBoolean("SyncPressed", "Sync gedrückt", "~Switch", 90);
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
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "MAC", "caption" => "MAC", "validate" => "^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$");

		$arrayOptions = array();
		$arrayOptions[] = array("label" => "3 kg", "value" => 200); // Wert ungeprüft
		$arrayOptions[] = array("label" => "5 kg", "value" => 300); // Wert ungeprüft
		$arrayOptions[] = array("label" => "11 kg", "value" => 420);
		$arrayOptions[] = array("label" => "33 kg", "value" => 600); // Wert ungeprüft
		$arrayOptions[] = array("label" => "Individuell", "value" => 0);
		$arrayElements[] = array("type" => "Select", "name" => "GasBottleValue", "caption" => "Gasflasche-Typ", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "IndividualLevel", "caption" => "Individueller Level", "minimum" => 0, "maximum" => 100, "suffix" => "cm");

		$arrayActions = array(); 
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	} 
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
		
			
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
	

	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
 		switch ($Message) {
			case IPS_KERNELSTARTED:
			
				break;
			
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
		$ID = utf8_decode($PayloadData->id);
	
		If ($ID == strtoupper($this->ReadPropertyString("MAC"))) {
			$this->SetValue("LastUpdate", time() );
			
			$OldTime = floatval($this->GetBuffer("UpdateRate"));
			$this->SetValueWhenChanged("UpdateRate", microtime(true) - $OldTime);
			$this->SetBuffer("UpdateRate", microtime(true));
			
			$RAW_Data = utf8_decode($PayloadData->manufacturerdata);
			$DataArray = array();
			$DataArray = $this->hex2ByteArray($RAW_Data);
			
			$RSSI = utf8_decode($PayloadData->rssi);
			$this->SetValueWhenChanged("RSSI", $RSSI);
			
			
			If (($DataArray[1] == 0x0d) AND (count($DataArray) == 25)) { // Standard
				$this->DataEvaluationGasStandard(serialize($DataArray));
				$this->SendDebug("ReceiveData", "ID-Treffer: ".$ID." RSSI: ".$RSSI." Roh-Daten: ".$RAW_Data, 0);
			}
			elseIf (($DataArray[1] == 0x59) AND (count($DataArray) == 12)) { // Pro
				$this->DataEvaluationGasPro(serialize($DataArray));
			}
			else {
				$this->SendDebug("ReceiveData", "Unbekannter Sensortyp!", 0);
			}
		}	
		
	}
	
	private function DataEvaluationGasStandard(string $Data)   
	{
		$DataArray = array();
		$DataArray = unserialize($Data); //$this->hex2ByteArray($Data);
		$this->SendDebug("DataEvaluationGasStandard", serialize($DataArray), 0);
		
		$Battery = ($DataArray[5] / 256.0) * 2.0 + 1.5;
		$this->SetValueWhenChanged("BatteryVoltage", $Battery);
		
		$BatteryPercentage = (($Battery - 2.2) / 0.65) * 100.0;
		$BatteryPercentage = min(100, max(0, $BatteryPercentage));
		$this->SetValueWhenChanged("BatteryPercentage", $BatteryPercentage);
		
		$Temperature = (($DataArray[6] & 0x3f)- 25.0) * 1.776964;
		If ($Temperature == 0) {
			$this->SetValueWhenChanged("Temperature", -40);
		} else {
			$Temperature = max(-40, $Temperature);
			$this->SetValueWhenChanged("Temperature", $Temperature);
		}
		
		//$UpdateRate = ($DataArray[6] & 0x40);
		//$this->SetValueWhenChanged("UpdateRate", boolval($UpdateRate));
		
		$SyncPressed = ($DataArray[6] & 0x80);
		$this->SetValueWhenChanged("SyncPressed", boolval($SyncPressed));
		
		$adv = array();
		$w = 7;
		$last_time = 0;
		$ndx = 0;

  		for ($q = 0; $q < 12; $q +=1 ) {
			$bitpos = $q * 10;
			$bytepos = floor($bitpos / 8);
			$off = $bitpos % 8;
			$v = $DataArray[$w + $bytepos] + $DataArray[$w + $bytepos + 1] * 256;
			$v = $v >> $off;
			$dt = ($v & 0x1f) + 1;
			$v = $v >> 5;
			$amp = $v & 0x1f;
			$this_time = $last_time + $dt;
			$last_time = $this_time;
			if ($this_time > 255) {
			  break;
			}
			if (!$amp) {
			  continue;
			}
			$amp -= 1;
			$amp *= 4;
			$amp += 6;
			$adv[$ndx] = array("a" => $amp, "i" => $this_time * 2);
			$ndx += 1;
		}
       
		$this->SendDebug("DataEvaluationGasStandard", serialize($adv), 0);
		
	}		
      
	private function DataEvaluationGasPro(string $Data)   
	{
		$DataArray = array();
		$DataArray = unserialize($Data); //$this->hex2ByteArray($Data);
		//$this->SendDebug("DataEvaluationGasPro", serialize($DataArray), 0);
		
		/*
		MA MA HW BAT TEMP Q  Q  MAC MAC MAC XACEL YACEL
        	1  2  3  4   5    6  7  8   9   10  11    12
		59 00 03 5d  2c   c1 83 db  79  c2  c4    f6
		*/
		
		$Battery = (($DataArray[4] & 0x7F) / 32);
		$this->SetValueWhenChanged("BatteryVoltage", $Battery);
		
		$BatteryPercentage = (($Battery - 2.2) / 0.65) * 100.0;
		$BatteryPercentage = min(100, max(0, $BatteryPercentage));
		$this->SetValueWhenChanged("BatteryPercentage", $BatteryPercentage);
		
		$Temperature_RAW = $DataArray[5] & 0x7f;
		$Temperature = $Temperature_RAW - 40;
		If ($Temperature == 0) {
			$this->SetValueWhenChanged("Temperature", -40);
		} else {
			$Temperature = max( -40, $Temperature);
			$this->SetValueWhenChanged("Temperature", $Temperature);
		}
		
		//$UpdateRate = ($DataArray[5] & 0x40);
		//$this->SetValueWhenChanged("UpdateRate", boolval($UpdateRate));
		
		$SyncPressed = ($DataArray[5] & 0x80);
		$this->SetValueWhenChanged("SyncPressed", boolval($SyncPressed));
		
		
		$QualityStars = $DataArray[7] >> 6;
		$QualityStars = min(3, max(0, $QualityStars));
		$this->SetValueWhenChanged("QualityStars", $QualityStars);
		
		$TankLevel = (($DataArray[7] << 8) + $DataArray[6]) & 0x3FFF;
        
		$TankLevel_mm = $TankLevel * (0.573045 + (-0.002822 * $Temperature_RAW) + (-0.00000535 * $Temperature_RAW * $Temperature_RAW));
       
		$TankLevel_rel = ($TankLevel_mm / $this->ReadPropertyInteger("GasBottleValue") ) * 100; // 400 Konstante die noch angepasst werden muss
     
		$this->SetValueWhenChanged("GasLevel", $TankLevel_rel);
        
		
		
	}	
	    
	private function hex2ByteArray($hexString) 
	{
  		$string = hex2bin($hexString);
  	return unpack('C*', $string);
	}
	    
	private function SetValueWhenChanged($Ident, $Value)
    	{
        	if ($this->GetValue($Ident) != $Value) {
            		$this->SetValue($Ident, $Value);
        	}
    	}    
	    
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}

}
?>
