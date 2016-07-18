<?php

$plugin = OW::getPluginManager()->getPlugin('fbconnect');

function FBCONNECT_Autoloader( $className )
{
    if ( strpos($className, 'FBCONNECT_FC_') === 0 )
    {
        $file = OW::getPluginManager()->getPlugin('fbconnect')->getRootDir() . DS . 'classes' . DS . 'converters.php';
        require_once $file;

        return true;
    }
}
spl_autoload_register('FBCONNECT_Autoloader');

$eventHandler = new FBCONNECT_CLASS_EventHandler();
$eventHandler->genericInit();