<?php
class ValidatorUSState {
    private static $states = array (
			    "AK" =>	true,
				"AL" => true,
				"AR" => true,
				"AZ" => true,
				"CA" => true,
				"CO" => true,
				"CT" => true,
				"DC" => true,
				"DE" => true,
				"FL" => true,
				"GA" => true,
				"HI" => true,
				"IA" => true,
				"ID" => true,
				"IL" => true,
				"IN" => true,
				"KS" => true,
				"KY" => true,
				"LA" => true,
				"MA" => true,
				"MD" => true,
				"ME" => true,
				"MI" => true,
				"MN" => true,
				"MO" => true,
				"MS" => true,
				"MT" => true,
				"NC" => true,
				"ND" => true,
				"NE" => true,
				"NH" => true,
				"NJ" => true,
				"NM" => true,
				"NV" => true,
				"NY" => true,
				"OH" => true,
				"OK" => true,
				"OR" => true,
				"PA" => true,
				"PR" => true,
				"RI" => true,
				"SC" => true,
				"SD" => true,
				"TN" => true,
				"TX" => true,
				"UT" => true,
				"VA" => true,
				"VI" => true,
				"VT" => true,
				"WA" => true,
				"WI" => true,
				"WV" =>	true,
				"WY" =>	true
			);
	public function getStates() {
        return self::$states;                            	
	}
    /**
     * @param Doctrine_Record $record
     * @param string $key
     * @param mixed $value
     * @param string $args
     * @return boolean
     */
    public function validate(Doctrine_Record $record, $key, $value, $args) {
        return isset(self::$states[$value]);
	}
}

