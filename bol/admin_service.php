<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Facebook Connect Admin Service
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.fbconnect.bol
 * @since 1.0
 */

class FBCONNECT_BOL_AdminService extends FBCONNECT_BOL_ServiceBase
{
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return FBCONNECT_BOL_AdminService
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     *
     * @return Facebook
     */
    public function getFaceBook()
    {
        $facebook = parent::getFaceBook();
        $facebook->setAccessToken($this->getFaceBookAccessDetails()->appAccessToken);

        return $facebook;
    }

    public function configureApplication()
    {
        $appId = $this->getFaceBook()->getAppId();
        
        $logoUrl = OW::getPluginManager()->getPlugin('fbconnect')->getStaticUrl() . 'img/logo.png';
        $iconUrl = OW::getPluginManager()->getPlugin('fbconnect')->getStaticUrl() . 'img/icon.png';
	$urlInfo = parse_url(OW_URL_HOME);

        $props = array(
            'icon_url' => $iconUrl,
            'logo_url' => $logoUrl,
            'website_url' => OW_URL_HOME,
            'mobile_web_url' => OW_URL_HOME,
            'app_domains' => array($urlInfo["host"])
        );

        try
        {
            $this->getFaceBook()->api("/" . $appId . "/", "post", $props);
        }
        catch ( Exception $e )
        {
            return false;
        }

        return true;
    }
}