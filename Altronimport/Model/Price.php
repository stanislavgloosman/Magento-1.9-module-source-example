<?php

class Innscience_Altronimport_Model_Price extends Innscience_Altronimport_Model_Connection
{
	const ALLTRON_PRODUCT_XML = 'PreisdatenV2.XML';
	
    
    public function _construct()
    {
		$this->setXMLPath(self::ALLTRON_PRODUCT_XML);
		parent::_construct();
		
	}
	
	public function productPrice()
	{
		$this->initProcess();
		$this->parseXML();
		
	} 
	
	public function parseXML() {
		
		$xml = $this->getXMLPath();
		$xmlObj = simplexml_load_file($xml);
		foreach($xmlObj->item as $item) {
			$this->updatePrice($item);
		}
	}
	
	public function updatePrice($item) {
		$productSku = $item->LITM;
			$productLoad = Mage::getModel('catalog/product')->loadByAttribute('sku', $productSku);
			if($productLoad){
				$product = Mage::getModel('catalog/product')->load($productLoad->getId());
				$product->setPrice(round((($item->price->ECPR*12)/100),2));
				$product->save();
			}
		
	}
	
	
}