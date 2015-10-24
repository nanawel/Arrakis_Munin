<?php
require_once __DIR__ . '/../abstract.php';

/**
 * Munin Monitoring Shell Script
 *
 * @category    Arrakis
 * @package     Arrakis_Munin
 * @author
 */
class Mage_Shell_Munin extends Mage_Shell_Abstract
{
    const MUNIN_PLUGIN_NAME = 'magento';

    /**
     * Parse config-string (see usage)
     *
     * @var array
     */
    protected $_config = null;

    public function __construct()
    {
        try {
            return parent::__construct();
        }
        catch (Exception $e) {
            file_put_contents('php://stderr', "[ERROR] {$e->getMessage()}\n");
            exit(1);
        }
    }

    /**
     * Parse string with indexers and return array of indexer instances
     *
     * @param string $string
     * @return array
     */
    protected function _getMonitors()
    {
        $monitors = array();

        $collection = Mage::getResourceModel('arrakis_munin/monitor_collection');
        foreach ($collection as $monitor) {
            if ($monitor->getEnabled() === false) {
                continue;
            }
            $monitors[] = $monitor;
        }
        return $monitors;
    }

    /**
     * @return Arrakis_Munin_Model_Monitor_Abstract
     */
    protected function _getMonitor()
    {
        return $this->_getMonitorByName($this->getArg('monitor'));
    }

    protected function _getMonitorByName($name)
    {
        $monitor =  Mage::getResourceModel('arrakis_munin/monitor')->factory($name);
        if (!$monitor->getEnabled()) {
            Mage::throwException('Monitor "' . $name . '" is not enabled');
        }
        return $monitor;
    }

    protected function _getInstanceConfigAsArray()
    {
        if ($this->_config === null) {
            $this->_config = array();
            if ($c = $this->getArg('instanceConfig')) {
                if (preg_match_all("/([a-z0-9]+)='([^']*)'/i", $c, $matches)) {
                    for($i = 0; $i < count($matches[1]); $i++) {
                        $this->_config[$matches[1][$i]] = $matches[2][$i];
                    }
                }
            }
        }
        return $this->_config;
    }

    /**
     * Run script
     *
     */
    public function run()
    {
        try {
            if (!Mage::isInstalled()) {
                throw new Exception('This Magento instance is not installed');
            }
            if ($this->getArg('config')) {
                $config = $this->_getInstanceConfigAsArray();

                $monitor = $this->_getMonitor();
                $monitor->addData(array(
                    'graph'          => $this->getArg('graph'),
                    'instanceConfig' => $config
                ));

                echo $this->_formatOutput($monitor->getGraphConfig());
            }
            else if ($this->getArg('values')) {
                $config = $this->_getInstanceConfigAsArray();

                $monitor = $this->_getMonitor();
                $monitor->addData(array(
                    'graph'          => $this->getArg('graph'),
                    'instanceConfig' => $config
                ));

                $out = $this->_formatOutput($monitor->getGraphValues());
                //$monitor->log($out);
                echo $out;
            }
            else if ($this->getArg('suggest')) {
                $config = $this->_getInstanceConfigAsArray();

                $out = array();
                $monitors = $this->_getMonitors();
                /* @var Arrakis_Munin_Model_Monitor_Abstract $monitor */
                foreach($monitors as $monitor) {
                    foreach($monitor->getAvailableGraphs() as $graph) {
                        $out[] = self::MUNIN_PLUGIN_NAME . '_' . $monitor->getCode() . '_' . $graph;
                    }
                }
                $monitor->addData(array(
                    'instanceConfig' => $config
                ));

                $out = implode("\n", $out) . "\n";
                //$monitor->log($out);
                echo $out;
            }
            else {
                echo $this->usageHelp();
            }
        }
        catch (Exception $e) {
            file_put_contents(
                'php://stderr',
                '[ERROR] (' . $this->getArg('monitor') . '_' . $this->getArg('graph') . ') ' . $e->getMessage() . "\n"
            );
            exit(1);
        }
    }

    protected function _formatOutput(array $output) {
        $return = '';
        foreach($output as $key => $row) {
            $return .= "$key $row\n";
        }
        return $return;
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f munin.php -- --instance <instance> --monitor <monitor> --graph <graph> [action] --instanceConfig <config-string>

Actions:
  --config           Print monitor graph config
  --values           Print monitor graph values
  --suggest          Print monitor graph suggestion
  help               This help

  <instance>       The ID of the instance as defined in Munin plugin configuration (the "X" in "mageinstanceX")
  <monitor>        The name of the monitor class as defined in the configuration (subnodes of <monitors>)
  <graph>          The name of the graph to get data from (depends on the monitor)
  <config-string>  Configuration directives for the instance as defined in Munin plugin configuration
USAGE;
    }
}

$shell = new Mage_Shell_Munin();
$shell->run();
