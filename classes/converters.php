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

class FBCONNECT_FC_Username extends FBCONNECT_CLASS_ConverterBase
{
    private $tryCount = 1, $username = null;

    public function __construct($userId = null)
    {
        parent::__construct($userId);

        if ( !empty($userId) )
        {
            $this->username = BOL_UserService::getInstance()->getUserName($userId);
        }
    }

    public function convert( $question, $fbField, $value )
    {
        $this->tryCount++;
        list($fn, $ln) = explode(' ', strtolower($value));
        $username = $fn . mb_substr($ln, 0, 1);

        if ( !empty($this->username) && $this->username == $username )
        {
            return $username;
        }

        if ( BOL_UserService::getInstance()->isExistUserName($username) )
        {
            return $this->convert($question, $fbField, $username . $this->tryCount);
        }

        return $username;
    }
}

class FBCONNECT_FC_TextFieldConverter extends FBCONNECT_CLASS_ConverterBase
{

    public function convert( $question, $fbField, $value )
    {
        return $value;
    }
}

class FBCONNECT_FC_Date extends FBCONNECT_CLASS_ConverterBase
{

    public function convert( $question, $fbField, $value )
    {
        if ( empty($value) )
        {
            return null;
        }

        $date = explode('/', $value);
        $year = empty($date[2]) ? 0 : $date[2];
        // YYYY/MM/DD
        return $year . '/' . $date[0] . '/' . $date[1];
    }
}

class FBCONNECT_FC_Picture extends FBCONNECT_CLASS_ConverterBase
{

    public function convert( $question, $fbField, $value )
    {
        $dir = OW::getPluginManager()->getPlugin('FBCONNECT')->getPluginFilesDir();
        $fileName = $dir . 'tmp_pic_' . md5($question . $value . time()) . '.jpg';

        $imageDataStr = file_get_contents($value);
        $imageData = json_decode($imageDataStr, true);
        $imageUrl = $imageData["data"]["url"];
        $imageContent = file_get_contents($imageUrl);
                
        if ( !$imageContent )
        {
        	return null;
        }

        if ( file_put_contents($fileName, $imageContent) )
        {
            return $fileName;
        }

        return null;
    }
}

