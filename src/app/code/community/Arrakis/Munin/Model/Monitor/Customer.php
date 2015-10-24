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
 * Class Arrakis_Munin_Model_Monitor_Customer
 *
 * @category   Arrakis
 * @package    Arrakis_Munin
 * @author     Anael Ollier <nanawel {at} gmail NOSPAM {dot} com>
 *
 * Provided graphs:
 *  - customer count per website
 */
class Arrakis_Munin_Model_Monitor_Customer extends Arrakis_Munin_Model_Monitor_Abstract
{
    /*-------------------------------------------------------------------------
     * CUSTOMER COUNT
     *-----------------------------------------------------------------------*/

    public function getCustomercountGraphConfig()
    {
        $config = array(
            'graph_title'   => $this->buildGraphTitle('Customers'),
            'graph_args'    => '--base 1000 -l 0',
            'graph_vlabel'  => $this->__('# of customers'),
            'graph_info'    => $this->__('This graph shows the number of customers per website.'),
            'graph_scale'   => 'no'
        );

        foreach($this->_getWebsites() as $website) {
            $config['website_' . $website->getCode() . '.label'] = $website->getName();
        }
        return $config;
    }

    public function getCustomercountGraphValues()
    {
        $return = array();

        $connection = $this->getReadConnection();
        $customerTable = Mage::getSingleton('core/resource')->getTableName('customer/customer');
        $websiteTable = Mage::getSingleton('core/resource')->getTableName('core/website');

        $sql = "SELECT cw.code AS website_code,, COUNT(*) AS customer_count FROM $customerTable AS ce
            JOIN $websiteTable AS cw ON ce.website_id = cw.website_id
            GROUP BY cw.website_id;";

        foreach($connection->fetchAssoc($sql) as $row) {
            $return['website_' . $row['website_code'] . '.value'] = $row['customer_count'];
        }

        return $return;
    }
}