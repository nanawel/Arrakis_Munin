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
 * TODO: implement all collection-related methods
 * 
 * @author 
 */
class Arrakis_Munin_Model_Resource_Monitor_Collection extends Varien_Data_Collection
{
    protected $_itemObjectClass = 'Arrakis_Munin_Model_Monitor_Abstract';
    
    /**
     * Initialize collection
     *
     */
    public function _construct()
    {
        
    }
    
    /**
     * Load enabled monitors
     *
     * @return  Varien_Data_Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if (!$this->isLoaded()) {
            $this->_items = array();
            $items = array();
            $models = Mage::app()->getConfig()->getNode(Arrakis_Munin_Constants::XML_PATH_MONITORS);
            foreach($models as $modelConfig) {
                if ($modelConfig) {
                    foreach ($modelConfig->children() as $code => $node) {
                        $items[$code] = Mage::getResourceModel('arrakis_munin/monitor')->factory($code);
                    }
                }
            }
            usort($items, array(__CLASS__, '_compareModels'));
            $this->_items = $items;
        }
        return $this;
    }

    protected static function _compareModels($a, $b)
    {
        return $a->getSortOrder() - $b->getSortOrder();
    }
}