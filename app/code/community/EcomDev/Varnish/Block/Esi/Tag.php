<?php
/**
 * Varnish extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_Varnish
 * @copyright  Copyright (c) 2014 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * ESI Tag Placeholder
 */
class EcomDev_Varnish_Block_Esi_Tag extends Mage_Core_Block_Template
{
    const ESI_TAG = '';
    
    protected function _construct()
    {
        $this->setTemplate('ecomdev/varnish/esi.phtml');
    }
    
    protected $_handles = array();
    
    /**
     * Adds handles for rendering ESI include
     * 
     * @param string $handle
     * @return $this
     */
    public function addHandle($handle)
    {
        $this->_handles[] = $handle;
        return $this;
    }

    /**
     * Retrieves list fo added handles for ESI include
     * 
     * @return array
     */
    public function getHandles()
    {
        return $this->_handles;
    }

    /**
     * Outputs ESI tag
     * 
     * @return string
     */
    protected function _beforeToHtml()
    {
        Mage::helper('ecomdev_varnish')->setIsEsiUsed(true);
        $handles = implode(',', $this->_handles);
        $params = array(
            'handles' => $handles,
            'package' => Mage::getSingleton('core/design_package')->getPackageName(),
            'theme' => Mage::getSingleton('core/design_package')->getTheme('default'),
            'checksum' => md5($handles . $this->getBlockName() . Mage::helper('ecomdev_varnish')->getEsiKey()),
            'block' => $this->getBlockName()
        );
        
        
        $url = $this->getUrl('varnish/esi/handle', $params);
        $this->setBlockUrl(parse_url($url, PHP_URL_PATH));
        $this->setHtmlId($this->getBlockAlias() . '-placeholder');
        
        return parent::_beforeToHtml();
    }
    
    public function getBlockJson()
    {
        $result = array(
            'htmlId' => $this->getHtmlId(),
            'url' => $this->getBlockUrl()
        );
        
        return $this->helper('core')->jsonEncode($result);
    }
}