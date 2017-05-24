<?php
class Innscience_Altronimport_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
	{
		echo date('H:i:s');
    }

    public function startAction()
	{
		Mage::getModel('altronimport/product')->newProductImport();
    }

    public function changeskuAction()
	{
		Mage::getModel('altronimport/product')->changeskuProduct();
	}

	public function stockAction()
	{
		Mage::getModel('altronimport/stock')->stockImport();
	}

	public function priceAction(){
			
		Mage::getModel('altronimport/price')->productPrice();
	}
	
	public function stockdownloadAction() {
		
		Mage::getModel('altronimport/stock')->downloadStockFile();
	}

	public function deletealtronAction(){
		Mage::getModel('altronimport/product')->deleteAltronProducts();
	}
	
	public function productdownloadAction(){
		Mage::getModel('altronimport/product')->downloadProductFile();
	}
	
	public function processzipAction(){
		Mage::getModel('altronimport/product')->processZip();
	}
	
	public function extractzipAction(){
		Mage::getModel('altronimport/product')->extracZipArchieve();
	}

	public function reindexAction(){
		Mage::getModel('altronimport/product')->importReindex();
	}

	public function updateimagesAction(){
		Mage::getModel('altronimport/product')->updateProductImages();
	}
}
