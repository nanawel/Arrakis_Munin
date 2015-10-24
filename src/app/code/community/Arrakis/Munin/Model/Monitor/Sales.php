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
 * Class Arrakis_Munin_Model_Monitor_Sales
 *
 * @category   Arrakis
 * @package    Arrakis_Munin
 * @author     Anael Ollier <nanawel {at} gmail NOSPAM {dot} com>
 *
 * Provided graphs:
 *  - order count by state
 *  - recent orders count (last hour)
 */
class Arrakis_Munin_Model_Monitor_Sales extends Arrakis_Munin_Model_Monitor_Abstract
{
    protected function _getWebsites($withDefault = false)
    {
        return Mage::app()->getWebsites($withDefault, true);
    }

    protected function _getWebsiteAndStores($withDefault = false)
    {
        $collection = Mage::getModel('core/website')
            ->getCollection()
            ->joinGroupAndStore();
        return $collection;
    }

    /*-------------------------------------------------------------------------
     * ORDER COUNT
     *-----------------------------------------------------------------------*/

    public function getOrdercountbystateGraphConfig()
    {
        $config = array(
            'graph_title'   => $this->buildGraphTitle('Orders By State'),
            'graph_args'    => '--base 1000 -l 0',
            'graph_vlabel'  => $this->__('# of orders'),
            'graph_info'    => $this->__('This graph shows the number of orders by store and status.'),
            'graph_scale'   => 'no'
        );
        foreach($this->_getWebsites() as $website) {
            $subArray = array(
                '' => $website->getName()
            );
            foreach($website->getStoreCollection() as $store) {
                foreach(Mage::getSingleton('sales/order_config')->getStates() as $state => $label) {
                    $subArray['store_' . $store->getCode() . '_' . $state . '.label'] = $this->__("%s (%s)", $store->getName(), $label);
                }
            }
            $config['website_' . $website->getCode() . '.label'] = $subArray;
        }
        return $config;
    }

    public function getOrdercountbystateGraphValues()
    {
        $return = array();
        $connection = $this->getReadConnection();
        $orderTable = Mage::getSingleton('core/resource')->getTableName('sales/order');

        // TOTAL (by website)
        $sql = "SELECT cs.website_id AS website, COUNT(*) AS value FROM $orderTable AS so
                JOIN core_store AS cs ON cs.store_id = so.store_id
                GROUP BY cs.website_id";
        $totalOrderCountByWebsite = $connection->fetchAssoc($sql);

        foreach($this->_getWebsites() as $website) {
            $subArray = array(
                '' => isset($totalOrderCountByWebsite[$website->getId()]) ? $totalOrderCountByWebsite[$website->getId()]['value'] : 0
            );
            foreach($website->getStoreCollection() as $store) {
                $sql = "SELECT state, COUNT(*) AS value FROM sales_flat_order
                        WHERE store_id = {$store->getId()}
                        GROUP BY state";
                $orderCountByState = $connection->fetchAssoc($sql);

                foreach(Mage::getSingleton('sales/order_config')->getStates() as $state => $label) {
                    $subArray['store_' . $store->getCode() . '_' . $state . '.value'] = isset($orderCountByState[$state]) ? $orderCountByState[$state]['value'] : 0;
                }
            }
            $return['website_' . $website->getCode() . '.value'] = $subArray;
        }
        return $return;
    }

    /*-------------------------------------------------------------------------
     * RECENT ORDER COUNT
     *-----------------------------------------------------------------------*/

    public function getRecentordercountGraphConfig()
    {
        $config = array(
            'graph_title'   => $this->buildGraphTitle('Recent Orders'),
            'graph_args'    => '--base 1000 -l 0',
            'graph_vlabel'  => $this->__('# of orders'),
            'graph_info'    => $this->__('This graph shows the number of orders created in the last hour.'),
            'graph_scale'   => 'no'
        );
        foreach($this->_getWebsites() as $website) {
            $subArray = array(
                '' => $website->getName()
            );
            foreach($website->getStoreCollection() as $store) {
                    $subArray['store_' . $store->getCode()  . '.label'] = $this->__("%s", $store->getName());
            }
            $config['website_' . $website->getCode() . '.label'] = $subArray;
        }
        return $config;
    }

    public function getRecentordercountGraphValues()
    {
        $return = array();
        $connection = $this->getReadConnection();

        $oneHourAgo = new Zend_Date();
        $oneHourAgo->subHour(1);
        $oneHourAgo = $this->getResource()->formatDate($oneHourAgo);
        foreach($this->_getWebsites() as $website) {
            $sql = Mage::getResourceModel('sales/order_collection')
                ->addFieldToFilter('created_at', array('from' => $oneHourAgo))
                ->addFieldToFilter('store_id', array('in' => $website->getStoreIds()))
                ->getSelectCountSql();
            $recentOrderCount = $connection->fetchOne($sql);

            $subArray = array(
                '' => $recentOrderCount
            );

            foreach($website->getStoreCollection() as $store) {
                $sql = Mage::getResourceModel('sales/order_collection')
                    ->addFieldToFilter('created_at', array('from' => $oneHourAgo))
                    ->addFieldToFilter('store_id', $store->getId())
                    ->getSelectCountSql();
                $recentOrderCount = $connection->fetchOne($sql);

                $subArray['store_' . $store->getCode() . '.value'] = $recentOrderCount;
            }
            $return['website_' . $website->getCode() . '.value'] = $subArray;
        }
        return $return;
    }
}