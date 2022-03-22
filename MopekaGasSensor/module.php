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
		$this->RequireParent("{82347F20-F541-41E1-AC5B-A636FD3AE2D8}");
	
		
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("MAC", "00:00:00:00:00:00");
		$this->RegisterPropertyInteger("GasBottleValue", 0);
		$this->RegisterPropertyInteger("IndividualLevel", 36);
		
		// Profile anlegen
		
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("LastUpdate", "Letztes Update", "~UnixTimestamp", 10);
		$this->RegisterVariableFloat("BatteryVoltage", "Betterie Spannung", "~Volt", 20);
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 30);
		$this->RegisterVariableInteger("Signal", "Signal-Qualität", "~Intensity.100", 40);
		$this->RegisterVariableInteger("GasLevel", "Gas Füllstand", "~Intensity.100", 50);
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
		$arrayOptions[] = array("label" => "3 kg", "value" => 3);
		$arrayOptions[] = array("label" => "5 kg", "value" => 5);
		$arrayOptions[] = array("label" => "11 kg", "value" => 11);
		$arrayOptions[] = array("label" => "33 kg", "value" => 33);
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
		$Message = utf8_decode($Data->Buffer);		
		$Message = trim($Message, "\x00..\x1F");
		
		// Temporäre Auswertung
		
		If (strpos($Message, "> HCI Event: LE Meta Event") !== false) {
			// neuer Datensatz beginnt
			$this->SetBuffer("MAC", "0");
		}
		/*
		If (strpos($Message, "Address:") !== false) {
			$this->SendDebug("ReceiveData", $Message, 0);
			$this->SendDebug("ReceiveData", strtoupper($this->ReadPropertyString("MAC")), 0);
		}
		*/
		
		If (strpos($Message, strtoupper($this->ReadPropertyString("MAC"))) !== false) {
			//$this->SendDebug("ReceiveData", $Message, 0);
			$this->SendDebug("ReceiveData", "MAC stimmt ueberein", 0);
			$this->SetBuffer("MAC", "1");
		}
		
		If ( (strpos($Message, "Data: ") !== false) AND ($this->GetBuffer("MAC") == "1") ) {
			// Daten
			$Message = str_replace('Data: ', '', $Message);
			$this->SendDebug("ReceiveData", $Message, 0);
		}
		If ((strpos($Message, "RSSI: ") !== false) AND ($this->GetBuffer("MAC") == "1")) {
			// RSSI
			$Message = str_replace('RSSI: ', '', $Message);
			$this->SendDebug("ReceiveData", $Message, 0);
		}
		
		
		//$this->SendDebug("ReceiveData", $Message, 0);
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

}
?>
