<?php

class Innscience_Altronimport_Model_Stock extends Innscience_Altronimport_Model_Connection
{
	const ALLTRON_PRODUCT_XML = 'LagerbestandV2.xml';
	
    
    public function _construct()
    {
		$this->setXMLPath(self::ALLTRON_PRODUCT_XML);
		parent::_construct();
		
	}
	
	public function stockImport()
	{
		$this->initProcess();
		$this->parseXML();
		
	} 
	
	public function parseXML() {
		
		$xml = $this->getXMLPath();
		$xmlObj = simplexml_load_file($xml);
		foreach($xmlObj->item as $item) {
			$this->updateStock($item);
		}
	}
	
	public function updateStock($item)
	{
			
			$productSku = $item->LITM;
			$productLoad = Mage::getModel('catalog/product')->loadByAttribute('sku', $productSku);
			if($productLoad){
				$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productLoad->getId());
				if ($stockItem->getId() > 0) {
					$qty = (int)$item->STQU;
					$stockItem->setQty($qty);
					$stockItem->setIsInStock((int)($qty > 0));
					
					$stockItem->save();
				}
			
			}
			  
	}
}