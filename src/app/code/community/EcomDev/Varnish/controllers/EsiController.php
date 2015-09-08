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
 * Esi block retrieval
 * 
 */
class EcomDev_Varnish_EsiController extends Mage_Core_Controller_Front_Action
{
    /**
     * Returns helper instance for the module
     *
     * @return EcomDev_Varnish_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('ecomdev_varnish');
    }
    
    public function handleAction()
    {
        // Prevent ESI execution in esi callback
        $this->_getHelper()->setIsEsiAllowed(false);
        Mage::app()->setUseSessionVar(false);
        Mage::app()->setUseSessionInUrl(false);
        
        $handles = $this->getRequest()->getParam('handles');
        $checksum = $this->getRequest()->getParam('checksum');
        $package = $this->getRequest()->getParam('package');
        $theme = $this->getRequest()->getParam('theme');
        $block = $this->getRequest()->getParam('block');
        
        if (!$handles || !$checksum 
            || $checksum != md5($handles . $block . $this->_getHelper()->getEsiKey())) {
            // Output empty screen if parameters are not valid
            $this->getResponse()->setBody('');
            return;
        }
        
        $handles = explode(',', $handles);
        Mage::getSingleton('core/design_package')->setPackageName($package);
        Mage::getSingleton('core/design_package')->setTheme('default', $theme);

        $this->loadLayout($handles);
        if ($block && $this->getLayout()->getBlock($block)) {
            $this->getResponse()
                ->setBody(
                    $this->getLayout()->getBlock($block)->toHtml()
                );
        } else {
            $this->renderLayout();
        }
    }

    /**
     * Do nothing with custom layout handles
     * 
     * @return $this
     */
    public function addActionLayoutHandles()
    {
        return $this;
    }
}

