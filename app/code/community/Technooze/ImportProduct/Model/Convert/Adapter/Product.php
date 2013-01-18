<?php
class Technooze_ImportProduct_Model_Convert_Adapter_Product extends Mage_Catalog_Model_Convert_Adapter_Product
{
    protected $defaults = array(
        'store' => 'admin',
        'websites' => 'base',
        'attribute_set' => 'Default',
        'type' => 'simple',
        'has_options' => '0',
        'featured' => '',
        'page_layout' => 'No layout updates',
        'gift_message_available' => '0',
        'custom_design' => '',
        'options_container' => 'Block after Info Column',
        'cost' => '',
        'minimal_price' => '',
        'weight' => '0.001',
        'custom_layout_update' => '',
        'color' => '',
        'status' => 'Enabled',
        'tax_class_id' => 'Taxable Goods',
        'visibility' => 'Catalog, Search',
        'qty' => '1',
        'min_qty' => '0',
        'use_config_min_qty' => '1',
        'is_qty_decimal' => '0',
        'backorders' => '0',
        'use_config_backorders' => '1',
        'min_sale_qty' => '1',
        'use_config_min_sale_qty' => '1',
        'max_sale_qty' => '100',
        'use_config_max_sale_qty' => '1',
        'is_in_stock' => '1',
        'low_stock_date' => '0000-00-00 00:00:00',
        'notify_stock_qty' => '1',
        'use_config_notify_stock_qty' => '1',
        'manage_stock' => '0',
        'use_config_manage_stock' => '1',
        'stock_status_changed_auto' => '0',
        'use_config_qty_increments' => '1',
        'qty_increments' => '0',
        'use_config_enable_qty_inc' => '1',
        'enable_qty_increments' => '0',
        'stock_status_changed_automatically' => '0',
        'use_config_enable_qty_increments' => '1',
        'store_id' => '1',
        'product_type_id' => 'simple',
        'product_status_changed' => '',
        'product_changed_websites' => '',
        'is_recurring' => 'No',
        'enable_googlecheckout' => 'Yes',
        'description' => 'coming soon...',
        'short_description' => 'coming soon...',
        'page_layout' => '',
        'msrp_enabled' => '',
        'msrp_display_actual_price_type' => '',
        'msrp,country_orgin' => '',
        'enable_googlecheckout' => '',
        'is_recurring' => '',
        'special_from_date' => '',
        'special_to_date' => '',
        'custom_design_from' => '',
        'custom_design_to' => '',
        'news_from_date' => '',
        'news_to_date' => '',
    );

    protected $_retailers = array();

    protected $_categoryCache = array();
   	/*	Add category and sub category.	 */
       protected function _addCategories($categories, $store)
       {

   		 $rootId = $store->getRootCategoryId();
           $storeId = 1;

           if (!$rootId) {
               /* If store is not created that means admin then assign 1 to storeId */
   			$storeId = 1;
   		 	$rootId = Mage::app()->getStore($storeId)->getRootCategoryId();
           }

   		if($categories=="")
           {
               return array();
           }
           $rootPath = '1/'.$rootId;
           if (empty($this->_categoryCache[$store->getId()])) {
               $collection = Mage::getModel('catalog/category')->getCollection()
                   ->setStore($store)
                   ->addAttributeToSelect('name');
               $collection->getSelect()->where("path like '".$rootPath."/%'");

               foreach ($collection as $cat) {
                   $pathArr = explode('/', $cat->getPath());
                   $namePath = '';
                   for ($i=2, $l=sizeof($pathArr); $i<$l; $i++) {
                       $name = $collection->getItemById($pathArr[$i])->getName();
                       $namePath .= (empty($namePath) ? '' : '/').trim($name);
                   }
                   $cat->setNamePath($namePath);
               }

               $cache = array();
               foreach ($collection as $cat) {
                   $cache[strtolower($cat->getNamePath())] = $cat;
                   $cat->unsNamePath();
               }
               $this->_categoryCache[$store->getId()] = $cache;
           }
           $cache =& $this->_categoryCache[$store->getId()];

         //$message = Mage::helper('catalog')->__('debug row, root id is "%s"' . "\n", print_r($cache, true));
         //Mage::throwException($message);

           $catIds = array();
           foreach (explode(',', $categories) as $categoryPathStr) {
               $categoryPathStr = preg_replace('#\s*/\s*#', '/', trim($categoryPathStr));

               if (!empty($cache[strtolower($categoryPathStr)])) {
                   $catpath = $cache[strtolower($categoryPathStr)]->getpath();
                   $catIds[] = str_replace('/', ',', str_replace("{$storeId}/{$rootId}/", '', $catpath));
                   continue;
               }

               $path = $rootPath;
               $namePath = '';
               foreach (explode('/', $categoryPathStr) as $catName) {
                   $namePath .= (empty($namePath) ? '' : '/').strtolower($catName);
                   if (empty($cache[$namePath])) {
                       $cat = Mage::getModel('catalog/category')
                           ->setStoreId($store->getId())
                           ->setPath($path)
                           ->setName($catName)
                           ->setIsActive(1)
                           ->save();
                       $cache[$namePath] = $cat;
                   }
                   $catId = $cache[$namePath]->getId();
                   $path .= '/'.$catId;
               }
               if ($catId) {
                   $catIds[] = $catId;
               }
           }
           return join(',', $catIds);
       }

     /**
     * Save product (import)
     *
     * @param array $importData
     * @throws Mage_Core_Exception 
     * @return bool
     */
	  protected $custom_options = array();
	  
	  
    public function saveRow(array $importData)
    {
        /*
         * If category_name column is not set, that means its not STC-02.csv dump for wholesaler.
         * So, original function is enough to save data
         */
        /*if(!isset($importData['category_name']))
        {
            return parent::saveRow($importData);
        }*/

        /*if(empty($this->_retailers)) {
            // Get all the existing manufacturers
            $collection = Mage::getModel('stores/location')->getCollection();
            foreach ( $collection as $option)
            {
                $this->_retailers[strtolower($option->getData('title'))] = $option->getData('stores_id');
            }
        }*/

        $retailers = array();

        $product = $this->getProductModel()
            ->reset();

        $importData = array_merge($this->defaults, $importData);

        /*if(
            (isset($importData['category_name']) && $importData['category_name'] == 'Movies_DVD')
            ||
            (isset($importData['cat2']) && $importData['cat2'] == 'Movies_DVD')
            ||
            (isset($importData['cat3']) && $importData['cat3'] == 'Movies_DVD')
        )
        {
            $message = Mage::helper('catalog')->__('Skip import row, Movies Category is requested to be skipped!', 'store');
            Mage::throwException($message);
        }*/

        if(empty($importData['weight']))
        {
            $importData['weight'] = '0.001';
        }

        /*workaround for 30% price increase than that of data provided.*/
        //$importData['price'] = round((float)$importData['price'] + ((float)$importData['price'] * 0.3), 2, PHP_ROUND_HALF_UP);
        //$importData['price'] = (string)$importData['price'];

        if (empty($importData['store'])) {
            if (!is_null($this->getBatchParams('store'))) {
                $store = $this->getStoreById($this->getBatchParams('store'));
            } else {
                $message = Mage::helper('catalog')->__('Skip import row, required field "%s" not defined', 'store');
                Mage::throwException($message);
            }
        }
        else {
             $store = $this->getStoreByCode($importData['store']);
        }

        if ($store === false) {
            $message = Mage::helper('catalog')->__('Skip import row, store "%s" field not exists', $importData['store']);
            Mage::throwException($message);
        }

        //$category_name = trim($importData['category1'].'/'.$importData['category2'].'/'.$importData['category3'], '/');

        if(!isset($importData['category_ids']))
        {
            if(!isset($importData['category_name']))
            {
                $message = Mage::helper('catalog')->__('Skip import row, one of required fields "%s" and "%s" not defined', 'category_ids', 'category_name');
                Mage::throwException($message);
            }
            $importData['category_ids'] = $this->_addCategories($importData['category_name'], $store);
        }

        if (empty($importData['sku'])) {
            $message = Mage::helper('catalog')->__('Skip import row, required field "%s" not defined', 'sku');
            Mage::throwException($message);
        }
        $product->setStoreId($store->getId());
        $productId = $product->getIdBySku($importData['sku']);

        if ($productId) {
            $product->load($productId);
        }
        else {
            $productTypes = $this->getProductTypes();
            $productAttributeSets = $this->getProductAttributeSets();
 
            /**
             * Check product define type
             */
            if (empty($importData['type']) || !isset($productTypes[strtolower($importData['type'])])) {
                $value = isset($importData['type']) ? $importData['type'] : '';
                $message = Mage::helper('catalog')->__('Skip import row, is not valid value "%s" for field "%s"', $value, 'type');
                Mage::throwException($message);
            }
            $product->setTypeId($productTypes[strtolower($importData['type'])]);

            /**
             * Check product define attribute set
             */
            if (empty($importData['attribute_set']) || !isset($productAttributeSets[$importData['attribute_set']])) {
                $value = isset($importData['attribute_set']) ? $importData['attribute_set'] : '';
                $message = Mage::helper('catalog')->__('Skip import row, is not valid value "%s" for field "%s"', $value, 'attribute_set');
                Mage::throwException($message);
            }
            $product->setAttributeSetId($productAttributeSets[$importData['attribute_set']]);
 
            foreach ($this->_requiredFields as $field) {
                $attribute = $this->getAttribute($field);
                if (!isset($importData[$field]) && $attribute && $attribute->getIsRequired()) {
                    $message = Mage::helper('catalog')->__('Skip import row, required field "%s" for new products not defined', $field);
                    Mage::throwException($message);
                }
            }
        }
 
        $this->setProductTypeInstance($product);
 
        if (isset($importData['category_ids'])) {
            $product->setCategoryIds($importData['category_ids']);
        }

        if(empty($this->_ignoreFields))
        {
            $this->_ignoreFields = array(
                'test'
            );
        }

        foreach ($this->_ignoreFields as $field) {
            if (isset($importData[$field])) {
                unset($importData[$field]);
            }
        }

       if ($store->getId() != 0) {
           $websiteIds = $product->getWebsiteIds();
           if (!is_array($websiteIds)) {
               $websiteIds = array();
           }
           if (!in_array($store->getWebsiteId(), $websiteIds)) {
               $websiteIds[] = $store->getWebsiteId();
           }
           $product->setWebsiteIds($websiteIds);
       }

       if (isset($importData['websites'])) {
           $websiteIds = $product->getWebsiteIds();
           if (!is_array($websiteIds)) {
               $websiteIds = array();
           }
           $websiteCodes = explode(',', $importData['websites']);
           foreach ($websiteCodes as $websiteCode) {
               try {
                   $website = Mage::app()->getWebsite(trim($websiteCode));
                   if (!in_array($website->getId(), $websiteIds)) {
                       $websiteIds[] = $website->getId();
                   }
               }
               catch (Exception $e) {}
           }
           $product->setWebsiteIds($websiteIds);
           unset($websiteIds);
       }

        /*$importData['image'] = str_replace(array('/', '\\'), DS, str_ireplace('http://images.example.com', '', $importData['image']));

        if(!file_exists(Mage::getBaseDir('media') . DS . 'import' . $importData['image']))
        {
            if($tmpImage = file_get_contents('http://images.example.com' . str_replace('\\','/',$importData['image'])))
            {
                $localImage = Mage::getBaseDir('media') . DS . 'import' . $importData['image'];
                if($fp  = $this->fopen_recursive($localImage, 'w+'))
                {
                    if(fputs($fp, $tmpImage))
                    {
                        fclose($fp);
                        unset($tmpImage);
                    }
                } else {
                    $message = Mage::helper('catalog')->__("Skip import row, failed to open image for write ({$localImage}).", $field);
                    Mage::throwException($message);
                }
            } else {
                $message = Mage::helper('catalog')->__("Skip import row, failed to get remote image ({$tmpImage}).", $field);
                Mage::throwException($message);
            }
        }*/

        $importData['image'] = '/'. trim($importData['image'], '/');

        if(empty($importData['description']))
        {
            $importData['description'] = $importData['short_description'];
            $importData['short_description'] = '';
        }

        if(empty($importData['small_image']))
        {
            $importData['small_image'] = $importData['image'];
        } else {
            $importData['small_image'] = '/'. trim($importData['small_image'], '/');
        }

        if(empty($importData['thumbnail']))
        {
            $importData['thumbnail'] = $importData['image'];
        } else
        {
            $importData['thumbnail'] = '/'. trim($importData['thumbnail'], '/');
        }

        /*
         * remove old images to avoid duplicates
         */
        if(file_exists(Mage::getBaseDir('media') . DS . 'import' . $importData['image']))
        {
            //Remove all images for product before uploading new images
            //check if gallery attribute exists then remove all images if it exists
            //Get products gallery attribute
            $attributes = $product->getTypeInstance()->getSetAttributes();
            if (isset($attributes['media_gallery'])) {
                $gallery = $attributes['media_gallery'];
                //Get the images
                $galleryData = $product->getMediaGallery();
                if(isset($galleryData['images'])) {
                    foreach ($galleryData['images'] as $image) {
                        //If image exists
                        if ($gallery->getBackend()->getImage($product, $image['file'])) {
                            $gallery->getBackend()->removeImage($product, $image['file']);

                            $oldImage = str_replace(array('/', '\\'), DS, (Mage::getBaseDir('media') . DS . 'catalog' . DS . 'product' . $image['file']));

                            if(file_exists($oldImage))
                            {
                                unlink($oldImage);
                            }
                        }
                    }
                }
            }
            #$gallery->clearMediaAttribute($product, array('image','small_image','thumbnail'));
            //END Remove Images
        }

        foreach ($importData as $field => $value) {
            if (in_array($field, $this->_inventoryFields)) {
                continue;
            }
            if (is_null($value)) {
                continue;
            }
            if (in_array($field, $this->_imageFields)) {
                continue;
            }
            if (strtolower($field) == 'retailers' && !empty($value)) {
                $retailers[$product->getId()] = explode(',', (string)$value);
            }
            $attribute = $this->getAttribute($field);
            if (!$attribute) {

                if(strpos($field,':')!==FALSE && strlen($value)) {
                   $values=explode('|',$value);
                   if(count($values)>0) {
                      @list($title,$type,$is_required,$sort_order) = explode(':',$field);
                      $title = ucfirst(str_replace('_',' ',$title));
                      $custom_options[] = array(
                         'is_delete'=>0,
                         'title'=>$title,
                         'previous_group'=>'',
                         'previous_type'=>'',
                         'type'=>$type,
                         'is_require'=>$is_required,
                         'sort_order'=>$sort_order,
                         'values'=>array()
                      );
                      foreach($values as $v) {
                         $parts = explode(':',$v);
                         $title = $parts[0];
                         if(count($parts)>1) {
                            $price_type = $parts[1];
                         } else {
                            $price_type = 'fixed';
                         }
                         if(count($parts)>2) {
                            $price = $parts[2];
                         } else {
                            $price =0;
                         }
                         if(count($parts)>3) {
                            $sku = $parts[3];
                         } else {
                            $sku='';
                         }
                         if(count($parts)>4) {
                            $sort_order = $parts[4];
                         } else {
                            $sort_order = 0;
                         }
                         switch($type) {
                            case 'file':
                                 break;

                            case 'field':
                            case 'area':
                               $custom_options[count($custom_options) - 1]['max_characters'] = $sort_order;


                            case 'date':
                            case 'date_time':
                            case 'time':
                               $custom_options[count($custom_options) - 1]['price_type'] = $price_type;
                               $custom_options[count($custom_options) - 1]['price'] = $price;
                               $custom_options[count($custom_options) - 1]['sku'] = $sku;
                               break;

                            case 'drop_down':
                            case 'radio':
                            case 'checkbox':
                            case 'multiple':
                            default:
                               $custom_options[count($custom_options) - 1]['values'][]=array(
                                  'is_delete'=>0,
                                  'title'=>$title,
                                  'option_type_id'=>-1,
                                  'price_type'=>$price_type,
                                  'price'=>$price,
                                  'sku'=>$sku,
                                  'sort_order'=>$sort_order,
                               );
                               break;
                         }
                      }
                   }
                }

                continue;
            }

            $isArray = false;
            $setValue = $value;

            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = explode(self::MULTI_DELIMITER, $value);
                $isArray = true;
                $setValue = array();
            }

            if ($value && $attribute->getBackendType() == 'decimal') {
                $setValue = $this->getNumber($value);
            }


            if ($attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions(false);

                if ($isArray) {
                    foreach ($options as $item) {
                        if (in_array($item['label'], $value)) {
                            $setValue[] = $item['value'];
                        }
                    }
                }
                else {
                    $setValue = false;
                    foreach ($options as $item) {
                        if (is_array($item['value'])) {
                            foreach ($item['value'] as $subValue) {
                                if (isset($subValue['value']) && $subValue['value'] == $value) {
                                    $setValue = $value;
                                }
                            }
                        } else if ($item['label'] == $value) {
                            $setValue = $item['value'];
                        }
                    }
                }
            }

            $product->setData($field, $setValue);
        }

        if (!$product->getVisibility()) {
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        }
 
        $stockData = array();
        $inventoryFields = isset($this->_inventoryFieldsProductTypes[$product->getTypeId()])
            ? $this->_inventoryFieldsProductTypes[$product->getTypeId()]
            : array();

        foreach ($inventoryFields as $field) {
            if (isset($importData[$field])) {
                if (in_array($field, $this->_toNumber)) {
                    $stockData[$field] = $this->getNumber($importData[$field]);
                }
                else {
                    $stockData[$field] = $importData[$field];
                }
            }
        }
        $product->setStockData($stockData);
 
        $imageData = array();
        foreach ($this->_imageFields as $field) {
            if (!empty($importData[$field]) && $importData[$field] != 'no_selection') {
                if (!isset($imageData[$importData[$field]])) {
                    $imageData[$importData[$field]] = array();
                }
                $imageData[$importData[$field]][] = $field;
            }
        }
 		
 		if(Mage::getVersion() < "1.5.0.0"){
			foreach ($imageData as $file => $fields) {
				try {
					$product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . $file, $fields);
				}
				catch (Exception $e) {}
			}
		}
		else
		{
             /*
              Code for image upload in version 1.5.x.x and above
             */
             $mediaGalleryBackendModel = $this->getAttribute('media_gallery')->getBackend();

            $arrayToMassAdd = array();

            foreach ($product->getMediaAttributes() as $mediaAttributeCode => $mediaAttribute) {
                if (isset($importData[$mediaAttributeCode])) {
                    $file = $importData[$mediaAttributeCode];
                    if (trim($file) && !$mediaGalleryBackendModel->getImage($product, $file)) {
                        $arrayToMassAdd[] = array('file' => trim($file), 'mediaAttribute' => $mediaAttributeCode);
                    }
                }
            }

            $addedFilesCorrespondence =
                $mediaGalleryBackendModel->addImagesWithDifferentMediaAttributes($product, $arrayToMassAdd, Mage::getBaseDir('media') . DS . 'import', false, false);

            foreach ($product->getMediaAttributes() as $mediaAttributeCode => $mediaAttribute) {
                $addedFile = '';
                if (isset($importData[$mediaAttributeCode . '_label'])) {
                    $fileLabel = trim($importData[$mediaAttributeCode . '_label']);
                    if (isset($importData[$mediaAttributeCode])) {
                        $keyInAddedFile = array_search($importData[$mediaAttributeCode],
                            $addedFilesCorrespondence['alreadyAddedFiles']);
                        if ($keyInAddedFile !== false) {
                            $addedFile = $addedFilesCorrespondence['alreadyAddedFilesNames'][$keyInAddedFile];
                        }
                    }

                    if (!$addedFile) {
                        $addedFile = $product->getData($mediaAttributeCode);
                    }
                    if ($fileLabel && $addedFile) {
                        $mediaGalleryBackendModel->updateImage($product, $addedFile, array('label' => $fileLabel));
                    }
                }
            }
		}
 
		/**
		 * Allows you to import multiple images for each product.
		 * Simply add a 'gallery' column to the import file, and separate
		 * each image with a semi-colon.
		 */
	        try {
	                $galleryData = explode(';',$importData["gallery"]);
	                foreach($galleryData as $gallery_img)
					/**
					 * @param directory where import image resides
					 * @param leave 'null' so that it isn't imported as thumbnail, base, or small
					 * @param false = the image is copied, not moved from the import directory to it's new location
					 * @param false = not excluded from the front end gallery
					 */
	                {
	                        $product->addImageToMediaGallery(Mage::getBaseDir('media') . DS . 'import' . $gallery_img, null, false, false);
	                }
	            }
	        catch (Exception $e) {}        
		/* End Modification */
 
        $product->setIsMassupdate(true);
        $product->setExcludeUrlRewrite(true);
 
        $product->save();
		 /* Add the custom options specified in the CSV import file 	*/
		
		if(isset($custom_options)){
            if(count($custom_options)) {
               foreach($custom_options as $option) {
                  try {
                    $opt = Mage::getModel('catalog/product_option');
                    $opt->setProduct($product);
                    $opt->addOption($option);
                    $opt->saveOptions();
                  }
                  catch (Exception $e) {}
               }
            }
		}

        /*
         * Add product retailers
         */
        foreach($retailers as $k => $v)
        {
            $collection = Mage::getModel('stores/products')->getCollection();
            $collection->addFieldToFilter('products_id', $k)->load();
            foreach($collection as $old_store)
            {
                $old_store->delete();
            }
            foreach($v as $_retailer)
            {
                if(!is_integer($_retailer)) {
                    $_retailer = trim(strtolower($_retailer));
                    if(!isset($this->_retailers[$_retailer]))
                    {
                        $message = Mage::helper('catalog')->__("Skip import row, retailer id '<strong>{$_retailer}</strong>' is not integer.");
                        Mage::throwException($message);
                    } else {
                        $_retailer = $this->_retailers[$_retailer];
                    }
                }

                $data = array('stores_id' => $_retailer, 'products_id' => $k);
                $model = Mage::getModel('stores/products')
                    ->setId(false)
                    ->setData($data)
                ;

                try {
                    $insertId = $model->save()->getId();
                } catch (Exception $e){
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
        }
        return true;
    }

    function fopen_recursive($path, $mode, $chmod=0755){
        $path = str_replace('\\', '/', $path);

      preg_match('`^(.+)/([a-zA-Z0-9_\-]+\.[a-z]+)$`i', $path, $matches);
      $directory = $matches[1];
      $file = $matches[2];

      if (!is_dir($directory)){
        if (!mkdir($directory, $chmod, 1)){
        return FALSE;
        }
      }
      $path = str_replace('/', DS, $path);
     return fopen ($path, $mode);
    }
}
