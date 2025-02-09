<?
    // Klassendefinition
    class MopekaGasSensor extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		$this->ConnectParent("{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}");
			
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString('MQTTBaseTopic', 'Mopeka2MQTT');
        	$this->RegisterPropertyString('MQTTTopic', '');
		$this->RegisterPropertyInteger("Gateway", 1);
		$this->RegisterPropertyInteger("GasBottleValue", 0);
		$this->RegisterPropertyInteger("IndividualLevel", 36);
		
		// Profile anlegen
		$this->RegisterProfileFloat("Mopeka.sek", "Clock", "", " sek", 0, 20, 1, 1);
		$this->RegisterProfileFloat("Mopeka.cm", "Distance", "", " cm", 0, 100, 0.1, 1);
		$this->RegisterProfileInteger("Mopeka.RSSI", "Intensity", "", " dBm", 0, 100, 1, 1);
		$this->RegisterProfileInteger("Mopeka.RSSIText", "Intensity", "", "", 0, 3, 1);
		IPS_SetVariableProfileAssociation("Mopeka.RSSIText", 0, "Sehr guter Empfang", "Intensity", 0x00FF00);
		IPS_SetVariableProfileAssociation("Mopeka.RSSIText", 1, "Guter Empfang", "Intensity", 0x00FF00);
		IPS_SetVariableProfileAssociation("Mopeka.RSSIText", 2, "Mittelmäßiger Empfang", "Intensity", 0xFFFF00);
		IPS_SetVariableProfileAssociation("Mopeka.RSSIText", 3, "Ausreichender Empfang", "Intensity", 0xFFFF00);
		IPS_SetVariableProfileAssociation("Mopeka.RSSIText", 4, "Schlechter Empfang", "Intensity", 0xFF0000);
		IPS_SetVariableProfileAssociation("Mopeka.RSSIText", 5, "Sehr schlechter Empfang", "Intensity", 0xFF0000);
		
		/*
		Güte des RSSI	RSSI von	RSSI bis
		Sehr guter Empfang	-1 dBm	-50 dBm 0
		Guter Empfang		-51 dBm	-70 dBm 1
		Mittelmäßiger Empfang	-71 dBm	-80 dBm 2
		Ausreichender Empfang	-81 dBm	-90 dBm 3
		Schlechter Empfang	-91 dBm	-105 dBm 4
		Sehr schlechter Empfang	-106 dBm	höher 5
		*/
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableFloat("BatteryVoltage", "Batterie Spannung", "~Volt", 20);
		$this->RegisterVariableInteger("BatteryPercentage", "Batterie Prozentual", "~Intensity.100", 30);
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 40);
		$this->RegisterVariableInteger("RSSI", "RSSI", "Mopeka.RSSI", 50);
		$this->RegisterVariableInteger("RSSIText", "RSSI", "Mopeka.RSSIText", 53);
		$this->RegisterVariableFloat("GasLevel_cm", "Gas Füllstand", "Mopeka.cm", 59);
		$this->RegisterVariableInteger("GasLevel", "Gas Füllstand", "~Intensity.100", 60);
		$this->RegisterVariableInteger("QualityStars", "Qualitäts Sterne", "", 70);
		$this->RegisterVariableFloat("UpdateRate", "Update Rate", "Mopeka.sek", 80);
		$this->RegisterVariableBoolean("SyncPressed", "Sync gedrückt", "~Switch", 90);
		$this->RegisterVariableInteger("AcceloX", "Lage X-Wert", "", 100);
		$this->RegisterVariableInteger("AcceloY", "Lage Y-Wert", "", 110);
		$this->RegisterVariableBoolean("PositionWarning", "Positions Warnung", "~Switch", 120);
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
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "OpenMQTTGateway", "value" => 1);
		$arrayOptions[] = array("label" => "TheengsGateway", "value" => 2);
		$arrayElements[] = array("type" => "Select", "name" => "Gateway", "caption" => "Gateway", "options" => $arrayOptions );

		$arrayOptions = array();
		$arrayOptions[] = array("label" => "3 kg", "value" => 200); // Wert ungeprüft
		$arrayOptions[] = array("label" => "5 kg", "value" => 300); // Wert ungeprüft
		$arrayOptions[] = array("label" => "11 kg", "value" => 420);
		$arrayOptions[] = array("label" => "33 kg", "value" => 600); // Wert ungeprüft
		$arrayOptions[] = array("label" => "Individuell", "value" => 0);
		$arrayElements[] = array("type" => "Select", "name" => "GasBottleValue", "caption" => "Gasflasche-Typ", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "IndividualLevel", "caption" => "Individueller Max-Level", "minimum" => 1, "maximum" => 100, "suffix" => "cm");

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
		
		if(isset($PayloadData->id)){                                                                                                                                                                       
                        $ID = utf8_decode($PayloadData->id);
                } else {
                        return;
                }
		
		$Gateway = $this->ReadPropertyInteger("Gateway");
		$this->SetValue("LastUpdate", time() );

		$OldTime = floatval($this->GetBuffer("UpdateRate"));
		$UpdateRate = min(99, max(0, microtime(true) - $OldTime));
		$this->SetValueWhenChanged("UpdateRate", $UpdateRate);
		$this->SetBuffer("UpdateRate", microtime(true));

		$RSSI = utf8_decode($PayloadData->rssi);
		$this->SetValueWhenChanged("RSSI", $RSSI);

		If ($RSSI >= -50) {
			$RSSIText = 0;
		}
		elseif (($RSSI <= -51) AND ($RSSI >= -70)) {
			$RSSIText = 1;
		}
		elseif (($RSSI <= -71) AND ($RSSI >= -80)) {
			$RSSIText = 2;
		}
		elseif (($RSSI <= -81) AND ($RSSI >= -90)) {
			$RSSIText = 3;
		}
		elseif (($RSSI <= -91) AND ($RSSI >= -105)) {
			$RSSIText = 4;
		}
		else {
			$RSSIText = 5;
		}
		$this->SetValueWhenChanged("RSSIText", $RSSIText);
		/*
		Güte des RSSI	RSSI von	RSSI bis
		Sehr guter Empfang	-1 dBm	-50 dBm 0
		Guter Empfang		-51 dBm	-70 dBm 1
		Mittelmäßiger Empfang	-71 dBm	-80 dBm 2
		Ausreichender Empfang	-81 dBm	-90 dBm 3
		Schlechter Empfang	-91 dBm	-105 dBm 4
		Sehr schlechter Empfang	-106 dBm	höher 5
		*/

		If ($Gateway == 1) {
			$Battery = utf8_decode($PayloadData->volt);
			$this->SetValueWhenChanged("BatteryVoltage", $Battery);

			$BatteryPercentage = utf8_decode($PayloadData->batt);
			$this->SetValueWhenChanged("BatteryPercentage", $BatteryPercentage);

			$Temperature = utf8_decode($PayloadData->tempc);
			$this->SetValueWhenChanged("Temperature", $Temperature);

			//$Level_cm = str_replace(",", ".", utf8_decode($PayloadData->lvl_cm));

			$Level_cm = utf8_decode($PayloadData->lvl_cm);
			$this->SetValueWhenChanged("GasLevel_cm", $Level_cm);
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
		}
		elseIf ($Gateway == 2) {
			$RAW_Data = utf8_decode($PayloadData->manufacturerdata);
			$DataArray = array();
			$DataArray = $this->hex2ByteArray($RAW_Data);

			If (($DataArray[1] == 0x0d) AND (count($DataArray) == 25)) { // Standard
				$this->DataEvaluationGasStandard(serialize($DataArray));
				$this->SendDebug("ReceiveData", " Roh-Daten: ".$RAW_Data, 0);
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
		//$this->SendDebug("DataEvaluationGasStandard", serialize($DataArray), 0);
		
		$Battery = ($DataArray[5] / 256.0) * 2.0 + 1.5;
		$this->SetValueWhenChanged("BatteryVoltage", $Battery);
		
		$BatteryPercentage = (($Battery - 2.2) / 0.65) * 100.0;
		$BatteryPercentage = min(100, max(0, $BatteryPercentage));
		$this->SetValueWhenChanged("BatteryPercentage", $BatteryPercentage);
		
		$Temperature_RAW = ($DataArray[6] & 0x3f);
		$Temperature = (($DataArray[6] & 0x3f) - 25.0) * 1.776964;
		If ($Temperature == 0) {
			$this->SetValueWhenChanged("Temperature", -40);
		} else {
			$Temperature = max(-40, $Temperature);
			$this->SetValueWhenChanged("Temperature", $Temperature);
		}
		
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
       
		//$this->SendDebug("DataEvaluationGasStandard", serialize($adv), 0);
		
		$last = 0;
	    	$data = array();
	    	$p = $adv;
	    	if ($p) {
			$last = -20;
			for ($i = 0; $i < count($p); $i += 1) {
		    		$time = round($p[$i]["i"] * 10);
		    		$amp = round(($p[$i]["a"] / 512.0) * (159 / 128), 4);
		    		if ($last + 20 !== $time) {
					array_push($data, $last + 20, -0.02);
					array_push($data, $time - 20, -0.02);
		    		}
		    		$last = $time;
		    		array_push($data, $time, $amp);
	      		}
	      		array_push($data, $last + 20, -0.02);
	      		array_push($data, 2000, -0.02);
	    	}
		else {
			$this->SetValueWhenChanged("GasLevel", 0);
			return;
		}
		
		
		
		//$this->SendDebug("DataEvaluationGasStandard", serialize($data), 0);
		
		// Peak finden
		for ($i = 0; $i < count($data); $i += 2) {
    			If ($data[$i + 1] > 0) {
        			$TankLevel = $data[$i];
				$Peak = $data[$i + 1];
				break;
    			}
		}
		$this->SendDebug("DataEvaluationGasStandard", "TankLevel roh: ".$TankLevel, 0);
		
		
		$lpg_butane_ratio = 1;
		$c = 1040.71 - 4.87 * $Temperature_RAW - 137.5 * $lpg_butane_ratio - 0.0107 * $Temperature_RAW * $Temperature_RAW - 1.63 * $Temperature_RAW * $lpg_butane_ratio;
		$this->SendDebug("DataEvaluationGasStandard", "c: ".$c, 0);
		$this->SendDebug("DataEvaluationGasStandard", " t * c: ".($Peak * $c / 2), 0);
		
		
		If ($TankLevel > 0) {
			$TankLevel_mm = $TankLevel * (0.573045 + (-0.002822 * $Temperature_RAW) + (-0.00000535 * $Temperature_RAW * $Temperature_RAW));
			$this->SendDebug("DataEvaluationGasStandard", "TankLevel mm: ".$TankLevel_mm, 0);
			$TankLevel_rel = ($TankLevel_mm / $this->ReadPropertyInteger("GasBottleValue") ) * 100;
			$TankLevel_rel = min(100, max(0, $TankLevel_rel));
			$this->SetValueWhenChanged("GasLevel", $TankLevel_rel);
		}
		else {
			$this->SetValueWhenChanged("GasLevel", 0);
		}
		
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
				
		$SyncPressed = ($DataArray[5] & 0x80);
		$this->SetValueWhenChanged("SyncPressed", boolval($SyncPressed));
		
		
		$QualityStars = $DataArray[7] >> 6;
		$QualityStars = min(3, max(0, $QualityStars));
		$this->SetValueWhenChanged("QualityStars", $QualityStars);
		
		$TankLevel = (($DataArray[7] << 8) + $DataArray[6]) & 0x3FFF;
        	$this->SendDebug("DataEvaluationGasPro", "TankLevel roh: ".$TankLevel, 0);
		
		$TankLevel_mm = $TankLevel * (0.573045 + (-0.002822 * $Temperature_RAW) + (-0.00000535 * $Temperature_RAW * $Temperature_RAW));
       		$this->SendDebug("DataEvaluationGasPro", "TankLevel mm: ".$TankLevel_mm, 0);
		$TankLevel_rel = ($TankLevel_mm / $this->GasBottleValue() ) * 100;
     		$TankLevel_rel = min(100, max(0, $TankLevel_rel));
		$this->SetValueWhenChanged("GasLevel", $TankLevel_rel);
        
		$AcceloX = $this->TwosComplement($DataArray[11]);
		$this->SetValueWhenChanged("AcceloX", $AcceloX);
		$AcceloY = $this->TwosComplement($DataArray[12]);
		$this->SetValueWhenChanged("AcceloY", $AcceloY);
		
		$this->SendDebug("DataEvaluationGasPro", "x: ".$AcceloX." y: ".$AcceloY, 0);
		
		if (($AcceloX <= 2) AND ($AcceloX >= -2) AND ($AcceloY <= 2) AND ($AcceloY >= -2)) {
			$this->SetValueWhenChanged("PositionWarning", false);
      		} else {
        		$this->SetValueWhenChanged("PositionWarning", true);
      		}
		
	}	
	
	private function GasBottleValue() 
	{    
		$GasBottleValue = $this->ReadPropertyInteger("GasBottleValue");
		$IndividualLevel = $this->ReadPropertyInteger("IndividualLevel");
		
		$MaxLevel = 0;
		
		If ($GasBottleValue == 0) {
			$MaxLevel = $IndividualLevel * 10; // Indivudeller Max-Level in mm
		}
		else {
			$MaxLevel = $GasBottleValue; // Max-Level nach Flaschentyp in mm
		}
	return $MaxLevel; 	
	}	
	private function TwosComplement(int $Number) 
	{
    		if ($Number > 0xFF) { 
			return false; 
		}
    		if ($Number >= 0x80) {
        		return -(($Number ^ 0xFF)+1);
    		} else {
        		return $Number;
    		}
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

	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);    
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
