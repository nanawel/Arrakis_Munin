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

abstract class Arrakis_Munin_Model_Monitor_Abstract extends Mage_Core_Model_Abstract
{
    const DEFAULT_GRAPH_CATEGORY = 'magento';

    public function _construct()
    {
        $this->_init('arrakis_munin/monitor');
    }

    /**
     * Return Munin config
     *
     * @return array
     */
    public function getGraphConfig($graph = null)
    {
        if (!$graph) {
            $graph = $this->getGraph();
        }
        $config = $this->_getConfigForGraph($graph);

        // Add default Munin category if not set
        if (!isset($config['graph_category'])) {
            $config['graph_category'] = self::DEFAULT_GRAPH_CATEGORY;
        }

        return $this->_flatten($config, true);
    }

    protected function _getConfigForGraph($graph)
    {
        $methodName = 'get' . ucfirst($graph) . 'GraphConfig';
        if (!method_exists($this, $methodName)) {
            Mage::throwException($this->__('Invalid graph type "%s"', $graph));
        }
        return $this->$methodName();
    }
    
    /**
     * Return Munin monitor values
     *
     * @return array
     */
    public function getGraphValues($graph = null)
    {
        if (!$graph) {
            $graph = $this->getGraph();
        }
        return $this->_flatten($this->_getValuesForGraph($graph));
    }

    protected function _getValuesForGraph($graph)
    {
        $methodName = 'get' . ucfirst($graph) . 'GraphValues';
        if (!method_exists($this, $methodName)) {
            Mage::throwException($this->__('Invalid graph type "%s"', $graph));
        }
        return $this->$methodName();
    }

    /**
     * INPUT:
     *      array(
     *          'key1' => 'value1'
     *          'key2' => array(
     *              ''        => 'value2'
     *              'subkey1' => 'subvalue1'
     *              'subkey2' => 'subvalue2'
     *          )
     *      )
     *
     * OUTPUT:
     *     array(
     *          'key1'        => 'value1'
     *          'key2'        => 'value2'
     *          ' +- subkey1' => 'subvalue1'
     *          ' +- subkey2' => 'subvalue2'
     *      )
     *
     * @param array $configOrValues
     */
    protected function _flatten(array $configOrValues, $indent = false)
    {
        foreach($configOrValues as $key => $row) {
            if (is_array($row)) {
                $subArray = $row;
                if (isset($subArray[''])) {
                    $configOrValues[$key] = $subArray[''];
                }
                else {
                    $configOrValues[$key] = '';
                }
                foreach($subArray as $subKey => $subRow) {
                    if ($subKey !== '') {
                        self::array_insert_after($configOrValues, $key, array($subKey => $indent ? chr(160) . '+- ' . $subRow : $subRow));
                    }
                }
            }
        }
        return $configOrValues;
    }

    /**
     * Insert values from $insert after $position into $array
     *
     * @author Halil Özgür (with adaptation)
     * @source http://stackoverflow.com/a/18781630
     *
     * @param array      $array
     * @param int|string $position
     * @param mixed      $insert
     */
    public static function array_insert_after(&$array, $position, $insert)
    {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos   = array_search($position, array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos + 1),
                $insert,
                array_slice($array, $pos)
            );
        }
    }

    public function buildGraphTitle($title)
    {
        return $this->__("$title %s", (($this->_getInstanceName() ? "({$this->_getInstanceName()})" : '')));
    }

    protected function _getInstanceName()
    {
        if ($name = $this->getData('instanceConfig/label')) {
            return $name;
        }
        return '';
    }

    public function getAvailableGraphs()
    {
        $graphs = array();
        $tmp = array();
        foreach(get_class_methods($this) as $method) {
            if (preg_match('/^get(([A-Z][a-z0-9]*)Graph(Config|Values))/', $method, $matches)) {

                // Make sure both GraphConfig and GraphValues are declared
                if (!isset($tmp[strtolower($matches[2])])) {
                    $tmp[strtolower($matches[2])] = $matches[1];
                }
                else {
                    $graphs[] = strtolower($matches[2]);
                }
            }
        }
        sort($graphs);
        return $graphs;
    }
    
    /**
     * Translate
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        return call_user_func_array(array(Mage::helper('arrakis_munin'), '__'), $args);
    }

    public function log($message)
    {
        Mage::log($message, null, 'munin.log');
    }

    /**
     *
     * @return Varien_Db_Adapter_Interface
     */
    public function getReadConnection()
    {
        return Mage::getSingleton('core/resource')->getConnection('core_read');
    }

    protected function _getWebsites($withDefault = false)
    {
        return Mage::app()->getWebsites($withDefault, true);
    }

    protected function _getWebsiteAndStores($withDefault = false)
    {
        $collection = Mage::getModel('core/website')
            ->getCollection()
            ->joinGroupAndStore();
        //TODO $withDefault
        return $collection;
    }
}