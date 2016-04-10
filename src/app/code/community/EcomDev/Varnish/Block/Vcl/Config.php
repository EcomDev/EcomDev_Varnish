<?php

use EcomDev_Varnish_Model_Vcl_ConfigInterface as ConfigInterface;

class EcomDev_Varnish_Block_Vcl_Config
    extends Mage_Core_Block_Template
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    protected function _construct()
    {
        $this->setTemplate('ecomdev/varnish/vcl/v4config.phtml');
    }

    /**
     * Sets configuration instance
     *
     * @param ConfigInterface $config
     * @return $this
     */
    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Returns configuration instance
     *
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }


}
