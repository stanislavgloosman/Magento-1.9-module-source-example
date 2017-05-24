<?php

class Innscience_Altronimport_Model_Connection extends Mage_Core_Model_Abstract
{
    
	public static $ftp = ''; 
	
	private $_host = '';
	private $_user = '';
	private $_pass = '';
	
	private $_filename = '';
	
	private $_XML = '';
	
    public function _construct()
    {
		self::$ftp = new Varien_Io_Sftp();
		self::$ftp->open(
			array(
					'host'      => $this->_host,
					'username'  => $this->_user,
					'password'  => $this->_pass,
				)
			);
	}
	
	public function getXMLPath(){
		return $this->_XML;
	}
	
	public function setXMLPath($filename){
		$this->_filename = $filename;
		$this->_XML = Mage::getBaseDir().'/alltronproduct/'.$filename;
	}
	
	public function getFilename(){
		
		return $this->_filename;
	}
	
	public function initProcess()
	{
		if(!file_exists($this->getXMLPath()))
			self::$ftp->read($this->getFilename(),$this->getXMLPath());		
	}
		
}