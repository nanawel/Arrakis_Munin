<?php
/*
 * Copyright 2015 Anael Ollier
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Class Arrakis_Munin_Model_Monitor_Catalog
 *
 * @category   Arrakis
 * @package    Arrakis_Munin
 * @author     Anael Ollier <nanawel {at} gmail NOSPAM {dot} com>
 *
 * Provided graphs:
 *  - products count by type
 *  - products count by website
 */
class Arrakis_Munin_Model_Monitor_Catalog extends Arrakis_Munin_Model_Monitor_Abstract
{
    protected function _getProductTypes()
    {
        return Mage_Catalog_Model_Product_Type::getTypes();
    }

    protected function _getProductMainTable()
    {
        return Mage::getSingleton('core/resource')->getTableName('catalog/product');
    }

    /*-------------------------------------------------------------------------
     * PRODUCTS COUNT BY TYPE
     *-----------------------------------------------------------------------*/

    public function getProductcountbytypeGraphConfig()
    {
        $config = array(
            'graph_title'   => $this->buildGraphTitle('Catalog size'),
            'graph_args'    => '--base 1000 -l 0',
            'graph_vlabel'  => $this->__('# of products'),
            'graph_info'    => $this->__('This graph shows the number of products in Magento\'s catalog, by type.'),
            'graph_scale'   => 'no'
        );
        foreach($this->_getProductTypes() as $productType => $type) {
            $config[$productType . '.label'] = $type['label'];
        }
        return $config;
    }

    public function getProductcountbytypeGraphValues()
    {
        $productTable = $this->_getProductMainTable();
        
        $sql = "SELECT type_id AS product_type, COUNT(*) AS value FROM $productTable GROUP BY type_id";
        $connection = $this->getReadConnection();
        
        $res = $connection->fetchAssoc($sql);
        
        $return = array();
        foreach($this->_getProductTypes() as $productType => $type) {
            $return["$productType.value"] = isset($res[$productType]) ? $res[$productType]['value'] : 0;
        }
        
        return $return;
    }

    /*-------------------------------------------------------------------------
     * PRODUCTS COUNT BY WEBSITE
     *-----------------------------------------------------------------------*/

    public function getProductcountbywebsiteGraphConfig()
    {
        $config = array(
            'graph_title'   => $this->buildGraphTitle('Catalog size'),
            'graph_args'    => '--base 1000 -l 0',
            'graph_vlabel'  => $this->__('# of products'),
            'graph_info'    => $this->__('This graph shows the number of products in Magento\'s catalog, by website.'),
            'graph_scale'   => 'no'
        );

        foreach($this->_getWebsites() as $website) {
            $subArray = array(
                '' => $website->getName()
            );
            foreach($website->getStoreCollection() as $store) {
                //Retrieve enabled products count for current store
                $subArray['store_' . $store->getCode() . '_enabled.label'] = $this->__("%s (enabled)", $store->getName());
            }
            $config['website_' . $website->getCode() . '.label'] = $subArray;
        }

        return $config;
    }

    public function getProductcountbywebsiteGraphValues()
    {
        $return = array();
        $connection = $this->getReadConnection();
        $productWebsiteTable = Mage::getSingleton('core/resource')->getTableName('catalog/product_website');

        // TOTAL (by website)
        $sql = "SELECT cw.code AS website, COUNT(*) AS value FROM $productWebsiteTable AS cpw
                JOIN core_website AS cw ON cpw.website_id = cw.website_id
                GROUP BY cw.website_id";
        $totalProductCountByWebsite = $connection->fetchAssoc($sql);

        foreach($this->_getWebsites() as $website) {
            $subArray = array(
                '' => isset($totalProductCountByWebsite[$website->getCode()]) ? $totalProductCountByWebsite[$website->getCode()]['value'] : 0
            );
            foreach($website->getStoreCollection() as $store) {
                /* @var $coll Mage_Catalog_Model_Resource_Product_Collection */
                $coll = Mage::getResourceModel('catalog/product_collection');
                $select= $coll->addWebsiteFilter($website)
                    ->setStore($store)
                    ->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->getSelectCountSql();
                $enabledProductCount = $connection->fetchOne($select);

                $subArray['store_' . $store->getCode() . '_enabled.value'] = $enabledProductCount;
            }
            $return['website_' . $website->getCode() . '.value'] = $subArray;
        }
        return $return;
    }
}