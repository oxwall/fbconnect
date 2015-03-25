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

$plugin = OW::getPluginManager()->getPlugin('fbconnect');

$dbPrefix = OW_DB_PREFIX;

$sql = 
<<<EOT
CREATE TABLE IF NOT EXISTS `{$dbPrefix}fbconnect_field` (
  `id` int(11) NOT NULL auto_increment,
  `question` varchar(50) NOT NULL,
  `fbField` varchar(100) NOT NULL,
  `converter` varchar(100) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;

INSERT INTO `{$dbPrefix}fbconnect_field` (`id`, `question`, `fbField`, `converter`) VALUES
(1, 'realname', 'name', 'FBCONNECT_FC_TextFieldConverter'),
(2, 'username', 'name', 'FBCONNECT_FC_Username'),
(3, 'email', 'email', 'FBCONNECT_FC_TextFieldConverter'),
(4, 'picture_small', 'pic_square', 'FBCONNECT_FC_Picture'),
(5, 'picture_big', 'pic_big', 'FBCONNECT_FC_Picture');

EOT;

OW::getDbo()->query($sql);


OW::getConfig()->addConfig('fbconnect', 'api_key', '', 'Facebook Api Key');
OW::getConfig()->addConfig('fbconnect', 'app_id', '', 'Facebook Application ID');
OW::getConfig()->addConfig('fbconnect', 'api_secret', '', 'Facebook Application Secret');

OW::getPluginManager()->addPluginSettingsRouteName('fbconnect', 'fbconnect_configuration_settings');

BOL_LanguageService::getInstance()->importPrefixFromZip($plugin->getRootDir() . 'langs.zip', 'fbconnect');