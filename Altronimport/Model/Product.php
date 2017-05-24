<?php

class Innscience_Altronimport_Model_Product extends Innscience_Altronimport_Model_Connection
{
	const ALLTRON_PRODUCT_XML = 'ArtikeldatenV2.xml';
	public $priceProcent;
    
    public function _construct()
    {
		$this->setXMLPath(self::ALLTRON_PRODUCT_XML);
		parent::_construct();
		$this->priceProcent = 1.12;	
	}
	
	public function newProductImport()
	{
		$this->initProcess();
		$this->parseXML();	
	}
	
	public function parseXML() {
		$start_time = microtime(TRUE);

		// Prices 
		$price_xmlObj = simplexml_load_file('alltronproduct/PreisdatenV2.XML');

		$syncPrices = array();
		foreach ($price_xmlObj as $item) {
			if( isset( $item->LITM ) && !empty( $item->LITM ) )
			$syncPrices[ (int) $item->LITM] = (float) $item->price->INPR;
		}

		unset($price_xmlObj);

		// Products
		$xml = $this->getXMLPath();
		$xmlObj = simplexml_load_file($xml);
		foreach($xmlObj->item as $item) {
			// Only needle category from Altron 
			if( ( $this->checkCategory( $item->part_catagory->CAT1 ) ) ) {
				$this->createProduct($item, $syncPrices);
			}
			
	    }

		$end_time = microtime(TRUE);
		$time_taken = $end_time - $start_time;
		$time_taken = round($time_taken,5);
		Mage::log('Sync done. Page generated in ' . $time_taken . ' seconds.', null, 'alltron.log');
	}
	
	public function createProduct($item, $syncPrices) {
			/* Mapping
				LITM	Artikelposition
				EITM	GTIN
				MITM	MPN
				WTXT	Title
				DES2	Short Description
				WTX2	Description
				GWGH	Weight
				MAFT	Manufacture
				STQU	Stock
				CAT1	Headcategorie
				CAT2	Subcategorie
				CAT3	Subcategorie
				CATA	it's the final categorie where the product is in
				INPR	Price
			*/
			$productSku = $item->LITM;
			$productGtin = $item->EITM;

			$productLoad = Mage::getModel('catalog/product')->loadByAttribute('sku', 'A-' . $productSku);

			// PRICE CALC
			$priceAltron = (float) $syncPrices[ (int) $productSku];
			$priceAfter  = round( (float) $priceAltron * $this->priceProcent + 2, 2);
			
			// QTY CHECK
			$stqu = $item->additional_information->STQU;
		    if( $stqu <= 0) {
		        $stqu = 0;
		        $is_stock = 0;
		        $status = 2;
		    }
		    else {
		      	$stqu = $item->additional_information->STQU;
		      	$is_stock = 1;
		      	$status = 1;
		    }

			if(!$productLoad) {

				// CREATE PRODUCT
				$product = Mage::getModel('catalog/product');

				// Scope
				$product->setStoreId(0)
						->setWebsiteIds(array(1))
						->setAttributeSetId(4)
						->setTypeId('simple')
						->setStatus(1)
						->setTaxClassId(2)
						->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
						->setPrice($priceAfter)
						->setGtin($item->part_number->EITM)
						->setMpn($item->part_number->MITM)
						->setName($item->part_description->WTXT)
						->setShortDescription($item->part_description->DES2)
						->setDescription($item->part_description->WTX2)
						->setWeight($item->additional_information->GWGH)
						->setManufacturer($this->retrieveOptionId('manufacturer',$item->additional_information->MAFT))
						->setConditionFeed($this->retrieveOptionId('condition_feed','new'))
						->setDistributor("Alltron");

				// Stock
				$product->setStockData(array(
	                    'use_config_manage_stock' => 0,
	                    'manage_stock'=>1,
	                    'min_sale_qty'=>1, 
	                    'use_config_max_sale_qty'=>1,
	                    'is_in_stock' => $is_stock,
	                    'qty' => $stqu
					)
				);
				
				$product->setSku('A-' . $productSku);
				$product->setEstimatedShippingTime("1-2 Werktage");
				$product->setQty($item->STQU);		
				// Categories
				$categories[] = $this->getCategoryId($this->categoryMapping($item->part_catagory->CAT1));
				$categories[] = $this->getCategoryId($this->categoryMapping($item->part_catagory->CAT2));
				$categories[] = $this->getCategoryId($this->categoryMapping($item->part_catagory->CAT3));
				$categories[] = $this->getCategoryId($this->categoryMapping($item->part_catagory->CATA));			
				$product->setCategoryIds($categories);
				
				// Images
				$images = array(
				    'thumbnail'   => '' . $item->LITM . '.jpg',
				    'small_image' => '' . $item->LITM . '.jpg',
				    'image'       => '' . $item->LITM . '.jpg',
				);
				 
				$dir = Mage::getBaseDir('media') . DS . 'Alle_Bilder_2/';

				foreach ($images as $imageType => $imageFileName) {
				    $path = $dir . $imageFileName;
				    if (file_exists($path)) {
				        try {
				            $product->addImageToMediaGallery($path, $imageType, false);
				        } catch (Exception $e) {
				            Mage::log('Import product exception. Method: addImageToMediaGallery ' . $e->getMessage() . '.', null, 'alltron.log');
				        }
				    }
				}
				
				$product->save();
			
			} else {
				// UPDATE PRODUCT
				$productId = $productLoad->getId();
				$product = Mage::getModel('catalog/product')->load($productId);
				$resource = $product->getResource();

				// ATTRIBUTES SAVER
				$product->setData('price', $priceAfter);
				$resource->saveAttribute($product, 'price');	
				
				$stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);

				if ($stockItem->getId() > 0) {
					$stockItem->setQty($stqu);
					$stockItem->setIsInStock($is_stock);
					$stockItem->save();
				}
			}		
	}

	// Run only from this categories
	public function checkCategory($categoryName) {
		$categories = array(
		'Maus &amp; Tastatur',
		'Consumer Electronics',
		'Telco',
		'Gaming-Stuhl',
		'Gaming-Zubehör');

		if( in_array($categoryName, $categories) )
			return true;
		else
			return false;
	}

	public function categoryMapping($categoryName) {
		$categoryMap = array(
			'PCs (alle Bauformen)'	=> 'Gaming Systeme',
			'Notebooks'				=> 'Gaming Systeme',
			'PC-Komponenten'		=> 'PC-Komponenten',
			'Arbeitsspeicher'		=> 'Arbeitsspeicher',
			'Prozessoren'			=> 'Prozessoren',
			'Mainboards'			=> 'Mainboards',
			'PC-Mainboards'			=> 'Intel',
			'PC-Grafikkarten'		=> 'Grafikkarten',
			'PC-Netzteile'			=> 'Netzteile',
			'Festplatten & SSD'		=> 'Laufwerke',
			'HDD'					=> 'Festplattem HDDs',
			'SSD'					=> 'Solid State Drives',
			'PC-Gehäuse/Barebones'	=> 'Gehäuse & Modding',
			'PC-Gehäuse (Tower/Desktops)' => 'Computer Gehäuse',
			'Kühler für CPU'		=> 'CPU-Kühler',
			'Gehäuselüfter'			=> 'Lüfter',
			'Wasserkühlung'			=> 'Wasserkühler',
			'Spielkonsole'			=> 'Konsolen',
			'Consumer Electronics' 	=> 'Unterhaltungselektronik'
		);
		
		return $categoryMap[$categoryName] ? $categoryMap[$categoryName] : $categoryName;
	}
	
	public function retrieveOptionId($attributeCode,$attributeValue) {
		$productModel = Mage::getModel('catalog/product');
		$attr = $productModel->getResource()->getAttribute($attributeCode);
		$optionId = '';
			if ($attr->usesSource()) {
				$optionId = $attr->getSource()->getOptionId($attributeValue);
			}
		return $optionId;
	}
	
	public function getCategoryId($categoryName) {
		$_category = Mage::getResourceModel('catalog/category_collection')
        ->addFieldToFilter('name', $categoryName)
        ->getFirstItem();
		return $categoryId = $_category->getId();	
	}

	public function importReindex() {
		for ($i = 1; $i <= 9; $i++) {
		    $process = Mage::getModel('index/process')->load($i);
		    $process->reindexAll();
		}
		Mage::log('Reindex all. Finish. Done', null, 'alltron.log');
	}
}