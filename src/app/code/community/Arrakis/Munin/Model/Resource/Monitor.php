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

class Arrakis_Munin_Model_Resource_Monitor extends Mage_Core_Model_Resource_Abstract
{
    protected $_modelConfig = null;

    /**
     * Resource initialization
     */
    protected function _construct()
    {

    }

    /**
     * Retrieve connection for read data
     */
    protected function _getReadAdapter()
    {
        return null;
    }

    /**
     * Retrieve connection for write data
     */
    protected function _getWriteAdapter()
    {
        return null;
    }

    /**
     *
     * @param string $name
     * @return Arrakis_Munin_Model_Monitor_Abstract
     */
    public function factory($name)
    {
        if (!$modelConfig = $this->getModelConfig($name)) {
            Mage::throwException(Mage::helper('arrakis_munin')->__('Unknown or invalid monitor name "%s"', $name));
        }
        $monitor = Mage::getModel($modelConfig['class'], $modelConfig);
        if ($monitor === null) {
            Mage::throwException(Mage::helper('arrakis_munin')->__('Invalid model "%s" for monitor "%s"', $modelConfig['class'], $name));
        }
        return $monitor;
    }

    /**
     * @param string $name
     * @return array|null
     */
    public function getModelConfig($name = null)
    {
        if ($this->_modelConfig === null) {
            $models = Mage::app()->getConfig()->getNode(Arrakis_Munin_Constants::XML_PATH_MONITORS);
            foreach($models as $modelConfig) {
                if ($modelConfig) {
                    foreach ($modelConfig->children() as $code => $node) {
                        $this->_modelConfig[$code] = (array) $node;
                        $this->_modelConfig[$code]['code'] = $code;
                    }
                }
            }
        }
        if ($name === null) {
            return $this->_modelConfig;
        }
        else {
            return isset($this->_modelConfig[$name]) ? $this->_modelConfig[$name] : null;
        }
    }
}