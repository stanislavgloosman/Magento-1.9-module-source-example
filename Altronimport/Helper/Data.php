<?php

class Innscience_Altronimport_Helper_Data extends Mage_Core_Helper_Abstract
{
	
    public function __construct()
    {
		$this->error_file = Mage::getBaseDir('var') .'/ds/log/error.htm';
		$handle = fopen($this->error_file, "a+");	
		chmod($this->error_file, 0777);
    }
   
}