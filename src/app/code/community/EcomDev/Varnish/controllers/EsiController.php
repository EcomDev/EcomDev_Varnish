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

        $params = $this->getRequest()->getParams();
        $requiredParams = array(
            'handles', 'package', 'theme', 'checksum'
        );

        if (array_diff($requiredParams, array_keys($params)) !== array()
            || empty($params['handles'])
            || !Mage::helper('ecomdev_varnish')->validateChecksum($params)) {
            // Output empty screen if parameters are not valid
            print_r($params);
            $this->getResponse()->setHttpResponseCode(404);
            $this->getResponse()->setBody('Invalid ESI configuration');
            return;
        }
        
        $handles = explode(',', $params['handles']);

        if (!empty($params['store'])) {
            // Apply a store view for ESI include
            Mage::app()->setCurrentStore($params['store']);
        }

        $designPackage = Mage::getSingleton('core/design_package');

        $designPackage->setPackageName($params['package']);
        $designPackage->setTheme('default', $params['theme']);
        $this->loadLayout($handles);

        if (isset($params['ttl'])) {
            $this->_getHelper()->addTtl((int)$params['ttl']);
        }

        if (!empty($params['block'])
            && $this->getLayout()->getBlock($params['block'])) {
            $this->getResponse()
                ->setBody(
                    $this->getLayout()->getBlock($params['block'])->toHtml()
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

