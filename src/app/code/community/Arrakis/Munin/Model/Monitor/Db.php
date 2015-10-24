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
 * Class Arrakis_Munin_Model_Monitor_Db
 *
 * @category   Arrakis
 * @package    Arrakis_Munin
 * @author     Anael Ollier <nanawel {at} gmail NOSPAM {dot} com>
 *
 * Provided graphs:
 *  - biggest tables
 */
class Arrakis_Munin_Model_Monitor_Db extends Arrakis_Munin_Model_Monitor_Abstract
{
    /*-------------------------------------------------------------------------
     * TABLES SIZE
     *-----------------------------------------------------------------------*/

    public function getBiggesttablesGraphConfig()
    {
        $config = array(
            'graph_title'   => $this->buildGraphTitle('Biggest tables'),
            'graph_args'    => '--base 1024 -l 0',
            'graph_vlabel'  => $this->__('Size in MB'),
            'graph_info'    => $this->__('This graph shows the size of the 10 biggest tables in the database.'),
            'graph_scale'   => 'no'
        );

        foreach($this->_getBiggestTables() as $tableName => $tableSize) {
            $config[$tableName . '.label'] = $tableName;
        }
        return $config;
    }

    public function getBiggesttablesGraphValues()
    {
        $return = array();
        foreach($this->_getBiggestTables() as $tableName => $tableSize) {
            $return[$tableName . '.value'] = $tableSize;
        }
        return $return;
    }

    protected function _getBiggestTables()
    {
        $dbName = (string) Mage::app()->getConfig()->getNode('global/resources/default_setup/connection/dbname');

        $connection = $this->getReadConnection();
        $sql = "SELECT table_name AS \"table\",
                ROUND(((data_length + index_length) / 1024 / 1024), 2) \"size\"
                FROM information_schema.TABLES
                WHERE table_schema = \"$dbName\"
                ORDER BY (data_length + index_length) DESC
                LIMIT 10;";

        $return = array();
        $res = $connection->fetchAssoc($sql);
        foreach($res as $row) {
            $return[$row['table']] = $row['size'];
        }
        return $return;
    }
}