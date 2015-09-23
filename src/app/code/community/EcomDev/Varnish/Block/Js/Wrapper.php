<?php

/**
 * Java script wrapper for a template
 */
class EcomDev_Varnish_Block_Js_Wrapper
    extends Mage_Core_Block_Template
{
    /**
     * Sets default template for a placeholder
     *
     */
    protected function _construct()
    {
        $this->setTemplate('ecomdev/varnish/wrapper/placeholder.phtml');
    }

    /**
     * Returns identifier of the javascript block
     *
     * @return string
     */
    public function getWrapperId()
    {
        return $this->getNameInLayout();
    }

    /**
     * Sets block name
     *
     * @param string $blockName
     */
    public function setBlockName($blockName)
    {
        $this->setData('block_name', $blockName);
        $this->append($this->getLayout()->getBlock($blockName));
    }
}
