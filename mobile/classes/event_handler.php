<?php

class FBCONNECT_MCLASS_EventHandler extends FBCONNECT_CLASS_EventHandler
{
    public function onCollectButtonList( BASE_CLASS_EventCollector $event )
    {
	$faceBookDetails = FBCONNECT_BOL_Service::getInstance()->getFaceBookAccessDetails();

        if ( empty($faceBookDetails->appId) || empty($faceBookDetails->secret) ) return;

        $cssUrl = OW::getPluginManager()->getPlugin('FBCONNECT')->getStaticCssUrl() . 'fbconnect.css';
        OW::getDocument()->addStyleSheet($cssUrl);

        $button = new FBCONNECT_MCMP_ConnectButton();
        $event->add(array('iconClass' => 'owm_ico_signin_f', 'markup' => $button->render()));
    }
}
