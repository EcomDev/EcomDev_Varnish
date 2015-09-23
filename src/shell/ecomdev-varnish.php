<?php

$shellDirectory = realpath(dirname($_SERVER['SCRIPT_FILENAME']));

require_once $shellDirectory . DIRECTORY_SEPARATOR . 'abstract.php';
require_once dirname($shellDirectory) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

/**
 * Shell script for generation of vcl configuration
 * and controlling installment from cli
 *
 */
class EcomDev_Varnish_Shell extends Mage_Shell_Abstract
{
    /**
     * Script action
     *
     * @var string
     */
    protected $_action;

    /**
     * Do not include Mage class via constructor
     *
     * @var bool
     */
    protected $_includeMage = false;

    /**
     * Map of arguments for shell script,
     * for making possible using shortcuts
     *
     * @var array
     */
    protected $_actionArgsMap = array(
        'vcl:generate' => array(
            'file' => 'f',
            'config' => 'c',
        ),
        'cache:ban' => array(
            'header' => 'h',
            'value' => 'v'
        ),
        'cache:ban:objects' => array(),
        'cache:ban:list' => array()
    );

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f ecomdev-varnish.php -- <action> <options>

  -h --help             Shows usage

Defined <action>s:

  vcl:generate          Generates vcl file
    -f --file               File to which output the changes, if not specified it outputs to STDOUT
    -c --config             JSON configuration file with varnish VCL options
    -t --template           Template file for rendering. By default ecomdev/varnish/vcl/config.phtml

  cache:ban             Bans varnish pages by request
    -h --header             Option name for ban. By default it uses req.url
    -v --value              Value for ban.

  cache:ban:objects     Bans objects stored in ban queue

  cache:ban:list        Outputs ban list on all the servers

USAGE;
    }

    /**
     * Parses actions for shell script
     *
     */
    protected function _parseArgs()
    {
        foreach ($_SERVER['argv'] as $index => $argument) {
            if (isset($this->_actionArgsMap[$argument])) {
                $this->_action = $argument;
                unset($_SERVER['argv'][$index]);
                break;
            }
            unset($_SERVER['argv'][$index]);
        }

        parent::_parseArgs();
    }

    /**
     * Retrieves arguments (with map)
     *
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed|bool
     */
    public function getArg($name, $defaultValue = false)
    {
        if (parent::getArg($name) !== false) {
            return parent::getArg($name);
        }

        if ($this->_action && isset($this->_actionArgsMap[$this->_action][$name])) {
            $value = parent::getArg($this->_actionArgsMap[$this->_action][$name]);
            if ($value === false) {
                return $defaultValue;
            }
            return $value;
        }

        return $defaultValue;
    }

    /**
     * Runs scripts itself
     *
     */
    public function run()
    {
        if ($this->_action === null) {
            die($this->usageHelp());
        }

        $reflection = new ReflectionClass(__CLASS__);
        $methodName = 'run' . uc_words($this->_action, '', ':');
        if ($reflection->hasMethod($methodName)) {
            try {
                Mage::app('admin');
                $this->$methodName();
            } catch (Exception $e) {
                fwrite(STDERR, "Error: \n{$e->getMessage()}\n");
                exit(1);
            }
        } else {
            die($this->usageHelp());
        }
    }

    /**
     * Returns block instance
     *
     * @return EcomDev_Varnish_Block_Vcl_Config
     */
    protected function getConfigBlock()
    {
        $designPackage = Mage::getSingleton('core/design_package');
        $designPackage->setArea('shell');
        $block = Mage::app()->getLayout()->createBlock('ecomdev_varnish/vcl_config');
        return $block;
    }

    /**
     * Generates VCL configuration
     *
     */
    protected function runVclGenerate()
    {
        $config = Mage::getModel(
            'ecomdev_varnish/vcl_config_json',
            array('file' => $this->getArg('config'))
        );

        $block = $this->getConfigBlock();
        if ($this->getArg('template')) {
            $block->setTemplate($this->getArg('template'));
        }

        $block->setConfig($config);

        $file = $this->getArg('file');

        if ($file) {
            $file = fopen($file, 'w');
        } else {
            $file = STDOUT;
        }

        fwrite($file, $block->toHtml());
    }

    /**
     * Ban cache item
     *
     */
    protected function runCacheBan()
    {
        $connector = Mage::getSingleton('ecomdev_varnish/connector');
        $header = $this->getArg('header');

        if (empty($header)) {
            $header = 'req.url';
        }

        $value = $this->getArg('value');
        $expression = $header . ' ~ ' . $value;
        $connector->getVarnishPool()->ban($expression);

        fwrite(STDOUT, sprintf('Executed %s command on all servers %s', $expression, PHP_EOL));
    }

    /**
     * Ban cache items in configuration cache list
     *
     */
    protected function runCacheBanObjects()
    {
        Mage::getSingleton('ecomdev_varnish/observer')->backgroundBan();
        fwrite(STDOUT, sprintf('Tags have been banned %s', PHP_EOL));
    }

    /**
     * Bans cache list
     *
     */
    protected function runCacheBanList()
    {
        $connector = Mage::getSingleton('ecomdev_varnish/connector');
        foreach ($connector->getVarnishPool()->getServers() as $server) {
            fwrite(STDOUT, sprintf('Ban list for server %s %s', (string)$server, PHP_EOL));
            fwrite(STDOUT, $server->execute('ban.list'));
        }
    }
}

$shell = new EcomDev_Varnish_Shell();
$shell->run();
