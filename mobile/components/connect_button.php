<?php

class FBCONNECT_MCMP_ConnectButton extends FBCONNECT_CMP_ConnectButton
{
    public function __construct() 
    {
        parent::__construct();
        
        $tpl = OW::getPluginManager()->getPlugin("fbconnect")->getMobileCmpViewDir() . "connect_button.html";
        $this->setTemplate($tpl);
    }
}