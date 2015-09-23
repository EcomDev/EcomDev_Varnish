<?php

class EcomDev_Varnish_ProbeController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Remove all un-needed logic for session starts and area setup
     *
     * @return $this
     */
    public function preDispatch()
    {
        return $this;
    }

    /**
     * In case if this page returns result longer than 100ms,
     * it means backend is unhealthy
     *
     */
    public function indexAction()
    {
        $this->getResponse()->setHttpResponseCode(200);
        $this->getResponse()->setBody('OK');
    }

    /**
     * Remove all post dispatch processes, as probe should not trigger any log writes
     *
     * @return $this
     */
    public function postDispatch()
    {
        return $this;
    }
}
