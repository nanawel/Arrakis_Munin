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
 * Class Arrakis_Munin_Model_Monitor_Log
 *
 * @category   Arrakis
 * @package    Arrakis_Munin
 * @author     Anael Ollier <nanawel {at} gmail NOSPAM {dot} com>
 *
 * Provided graphs:
 *  - biggest files
 */
class Arrakis_Munin_Model_Monitor_Log extends Arrakis_Munin_Model_Monitor_Abstract
{
    public function getBiggestfilesGraphConfig()
    {
        $config = array(
            'graph_title'   => $this->buildGraphTitle('Log size'),
            'graph_args'    => $this->__('--base 1024 -l 0'),
            'graph_vlabel'  => $this->__('Size in KB'),
            'graph_info'    => $this->__('This graph shows the size of log files.'),
            'graph_scale'   => 'no'
        );

        foreach($this->_getBiggestFiles() as $filename => $filesize) {
            $fileidx = preg_replace('/[^a-z0-9_]/i', '_', $filename);
            $config[$fileidx . '.label'] = $filename;
        }
        return $config;
    }

    public function getBiggestfilesGraphValues()
    {
        $return = array();
        foreach($this->_getBiggestFiles() as $filename => $filesize) {
            $fileidx = preg_replace('/[^a-z0-9_]/i', '_', $filename);
            $return[$fileidx . '.value'] = round($filesize, 2);
        }
        return $return;
    }

    protected function _getBiggestFiles()
    {
        $return = array();
        $logDir = new Varien_Io_File();
        $logDir->open(array('path' => Mage::getBaseDir('log')));
        foreach($logDir->ls(Varien_Io_File::GREP_FILES) as $file) {
            if (substr($file['text'], -4) == '.log') {
                $return[$file['text']] = $file['size'] / 1024;
            }
        }
        arsort($return);
        return array_slice($return, 0, 10);
    }
}