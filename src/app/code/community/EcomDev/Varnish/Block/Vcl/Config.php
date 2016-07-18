<?php

use EcomDev_Varnish_Model_Vcl_ConfigInterface as ConfigInterface;

class EcomDev_Varnish_Block_Vcl_Config
    extends Mage_Core_Block_Template
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * Varnish version
     *
     * @var int
     */
    protected $version = 4;

    protected function _construct()
    {
        $this->initTemplate();
    }

    /**
     * Initializes varnish version based template
     *
     * @return $this
     */
    private function initTemplate()
    {
        $this->setTemplate(sprintf('ecomdev/varnish/vcl/v%d/config.phtml', $this->version));
        return $this;
    }

    /**
     * Sets varnish version
     *
     * @param int $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = (int)$version;
        $this->initTemplate();
        return $this;
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
