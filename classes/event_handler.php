<?php

class FBCONNECT_CLASS_EventHandler
{
    public function onCollectButtonList( BASE_CLASS_EventCollector $event )
    {
        $cssUrl = OW::getPluginManager()->getPlugin('FBCONNECT')->getStaticCssUrl() . 'fbconnect.css';
        OW::getDocument()->addStyleSheet($cssUrl);

        $button = new FBCONNECT_CMP_ConnectButton();
        $event->add(array('iconClass' => 'ow_ico_signin_f', 'markup' => $button->render()));
    }

    public function afterUserRegistered( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['method'] != 'facebook' )
        {
            return;
        }

        $userId = (int) $params['userId'];

        $event = new OW_Event('feed.action', array(
                'pluginKey' => 'base',
                'entityType' => 'user_join',
                'entityId' => $userId,
                'userId' => $userId,
                'replace' => true,
                ), array(
                'string' => OW::getLanguage()->text('fbconnect', 'feed_user_join'),
                'view' => array(
                    'iconClass' => 'ow_ic_user'
                )
            ));
        OW::getEventManager()->trigger($event);
    }

    public function afterUserSynchronized( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !OW::getPluginManager()->isPluginActive('activity') || $params['method'] !== 'facebook' )
        {
            return;
        }
        $event = new OW_Event(OW_EventManager::ON_USER_EDIT, array('method' => 'native', 'userId' => $params['userId']));
        OW::getEventManager()->trigger($event);
    }
    
    public function onCollectAccessExceptions( BASE_CLASS_EventCollector $e ) {
        $e->add(array('controller' => 'FBCONNECT_CTRL_Connect', 'action' => 'xdReceiver'));
        $e->add(array('controller' => 'FBCONNECT_CTRL_Connect', 'action' => 'login'));
    }
    
    public function onCollectAdminNotification( BASE_CLASS_EventCollector $e )
    {
        $language = OW::getLanguage();
        $configs = OW::getConfig()->getValues('fbconnect');

        if ( empty($configs['app_id']) || empty($configs['api_secret']) )
        {
            $e->add($language->text('fbconnect', 'admin_configuration_required_notification', array('href' => OW::getRouter()->urlForRoute('fbconnect_configuration'))));
        }
    }    
    
    public function getConfiguration( OW_Event $event )
    {
        $service = FBCONNECT_BOL_Service::getInstance();
        $appId = $service->getFaceBookAccessDetails()->appId;

        if ( empty($appId) )
        {
            return null;
        }
        
        $data = array(
            "appId" => $appId
        );
        
        $event->setData($data);
        
        return $data;
    }

    
    public function genericInit()
    {
        OW::getEventManager()->bind(BASE_CMP_ConnectButtonList::HOOK_REMOTE_AUTH_BUTTON_LIST, array($this, "onCollectButtonList"));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_REGISTER, array($this, "afterUserRegistered"));
        OW::getEventManager()->bind(OW_EventManager::ON_USER_EDIT, array($this, "afterUserSynchronized"));
        
        OW::getEventManager()->bind('base.members_only_exceptions', array($this, "onCollectAccessExceptions"));
        OW::getEventManager()->bind('base.password_protected_exceptions', array($this, "onCollectAccessExceptions"));
        OW::getEventManager()->bind('base.splash_screen_exceptions', array($this, "onCollectAccessExceptions"));
        
        OW::getEventManager()->bind('fbconnect.get_configuration', array($this, "getConfiguration"));
    }
    
    public function init()
    {
        $this->genericInit();
        
        OW::getEventManager()->bind('admin.add_admin_notification', array($this, "afterUserSynchronized"));
    }
}