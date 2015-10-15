<?php

interface EcomDev_Varnish_Block_MessagesInterface
{
    /**
     * Returns list of
     *
     * @return string[][]
     */
    public function getMessagesByStorage();
}
