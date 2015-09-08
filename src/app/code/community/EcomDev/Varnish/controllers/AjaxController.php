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
 * Ajax dynamic block reloader
 */
class EcomDev_Varnish_AjaxController extends Mage_Core_Controller_Front_Action
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
    
    public function reloadAction()
    {
        // Prevent ESI execution in esi callback
        $this->_getHelper()->setIsEsiAllowed(false);
        $this->_getHelper()->setIsInternal(true);

        Mage::app()->setUseSessionVar(false);
        Mage::app()->setUseSessionInUrl(false);
        
        $blocks = $this->getRequest()->getParam('blocks');
        
        if (!is_array($blocks)) {
            $blocks = array_map('trim', explode(',', $blocks));
        }
        
        
        $result = array();
        $this->loadLayout();
        foreach ($blocks as $block) {
            if ($this->getLayout()->getBlock($block)) {
                $result[$block] = $this->getLayout()->getBlock($block)->toHtml();
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
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