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
 * @copyright  Copyright (c) 2020 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * ESI Tag Placeholder
 *
 * @method string getBlockUrl()
 * @method string getHtmlId()
 * @method string getTtl()
 * @method $this setTtl(string $ttl)
 */
class EcomDev_Varnish_Block_Esi_Tag extends Mage_Core_Block_Template
{
    const ESI_TAG = '';

    protected $_handles = array();

    /**
     * Constructor sets a default ESI block template
     *
     */
    protected function _construct()
    {
        $this->setTemplate('ecomdev/varnish/esi.phtml');
    }

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
            'theme' => Mage::getSingleton('core/design_package')->getTheme('default') ?: 'default',
            'store' => Mage::app()->getStore()->getCode(),
            'block' => $this->getBlockName()
        );

        if ($this->getTtl()) {
            $params['ttl'] = (string)$this->getTtl();
        }

        if ($this->hasData('referrer') && !$this->getData('referrer')) {
            $params['filter_referrer'] = '1';
        }

        if ($this->hasData('cookies') && !$this->getData('cookies')) {
            $params['filter_cookies'] = '1';
        }

        $params['checksum'] = Mage::helper('ecomdev_varnish')->getChecksum($params);
        $params['_secure'] = Mage::app()->getStore()->isCurrentlySecure();

        $url = $this->getUrl('varnish/esi/handle', $params);

        // Replace http with https,
        // So varnish can process ESI requests correctly and take domain name into account
        if (strpos($url, 'https://') === 0) {
            $url = 'http:' . substr($url, 6);
        }

        $this->setBlockUrl($url);
        $this->setHtmlId($this->getBlockAlias() . '-placeholder');
        
        return parent::_beforeToHtml();
    }

    /**
     * Returns block json
     *
     * @return string[]
     */
    public function getBlockJson()
    {
        $result = array(
            'htmlId' => $this->getHtmlId(),
            'url' => $this->getBlockUrl()
        );
        
        return $this->helper('core')->jsonEncode($result);
    }
}
