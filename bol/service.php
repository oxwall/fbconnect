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
 * Facebook Connect Service
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.fbconnect.bol
 * @since 1.0
 */

class FBCONNECT_BOL_Service extends FBCONNECT_BOL_ServiceBase
{
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return FBCONNECT_BOL_Service
     */
    public static function getInstance()
    {
        if ( null === self::$classInstance )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }


    private $jsInitialized = false, $scope = 'email,public_profile';

    /**
     *
     * @var FBCONNECT_BOL_FieldDao
     */
    private $fieldDao;

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();

        $this->fieldDao = FBCONNECT_BOL_FieldDao::getInstance();
    }

    public function initializeJs($scope = null, $shareData = null )
    {
        if ($this->jsInitialized)
        {
            return;
        }
        $document = OW::getDocument();

        $document->addScript(OW::getPluginManager()->getPlugin('fbconnect')->getStaticJsUrl() . 'fb.js');

        $loginParams = array(
            'scope' => $this->scope
        );

        $fbLibUrl = '//connect.facebook.net/en_US/sdk.js';

        $uri = OW::getRequest()->getRequestUri();

        $loginRouteUrl = OW::getRouter()->urlForRoute('fbconnect_login');
        $synchronizeRouteUrl = OW::getRouter()->urlForRoute('fbconnect_synchronize');

        $shareData['backUri'] = urlencode($uri);

        // OW_FB js object definition
        $options = array(
            'onLoginUrl' => OW::getRequest()->buildUrlQueryString($loginRouteUrl, $shareData),
            'onSynchronizeUrl' => OW::getRequest()->buildUrlQueryString($synchronizeRouteUrl, array('backUri' => urlencode($uri)))
        );

        $js = UTIL_JsGenerator::newInstance();
        $js->newObject(array('window', 'OW_FB'), 'OW_FBConstructor', array($fbLibUrl, $loginParams, $options));

        $access = $this->getFaceBookAccessDetails();

        $fbParams = array(
            'appId' => $access->appId,
            'status' => true, // check login status
            'cookie' => true, // enable cookies to allow the server to access the session
            'xfbml'  => true, // parse XFBML
            'channelURL' => OW::getRouter()->urlForRoute('fbconnect_xd_receiver'), // channel.html file
            'oauth' => true, // enable OAuth 2.0
            'version' => 'v2.1'
        );

        $js->callFunction(array('OW_FB', 'init'), array($fbParams));
        $document->addOnloadScript((string) $js);

        $this->jsInitialized = true;
    }

    public function fbRequireUser($requiredPermissions = array())
    {
        $user = $this->getFaceBook()->getUser();
        if ( empty($user) )
        {
            $loginUrl = $this->getFaceBook()->getLoginUrl(array(
                'scope' => $this->scope
            ));

            throw new RedirectException($loginUrl);
        }

        return $user;
    }

    public function fbGetFieldValueList($fbUserId, array $fields)
    {
        if (!$fbUserId)
        {
            throw new InvalidArgumentException('Invalid Argument $fbUserId');
        }

        if (empty($fields))
        {
            return array();
        }
        
        $info = $this->getFaceBook()->api("/" . $fbUserId);
        
        $out = array();
        foreach ( $fields as $field )
        {
            switch ($field)
            {
                case "pic_big":
                    $out[$field] = "http://graph.facebook.com/{$fbUserId}/picture?type=large&?return_ssl_resources=0&redirect=false";
                    
                    break;
                case "pic_square":
                    $out[$field] = "http://graph.facebook.com/{$fbUserId}/picture?type=large&return_ssl_resources=0&redirect=false";
                    
                    break;
                default:
                    $out[$field] = isset($info[$field]) ? $info[$field] : null;
            }
        }
        
        return $out;
    }

    public function requestQuestionValueList($fbUserId, $questionNameList = null, $userId = null)
    {
        $fieldDtoList = empty($questionNameList)
            ? $this->fieldDao->findAll()
            : $this->fieldDao->findListByQuestionList($questionNameList);

        $converterList = array();

        $fbFields = array();
        foreach ($fieldDtoList as $fieldDto)
        {
            /* @var $fieldDto FBCONNECT_BOL_Field */

            $allowedFBFields = $this->getPossibleFbFieldList($fieldDto->question);

            if ( in_array($fieldDto->fbField, $allowedFBFields) )
            {
                $fbFields[$fieldDto->fbField] = $fieldDto->fbField;
            }
        }
        
        $fbFields = array_values($fbFields);
        $fbFieldValues = $this->fbGetFieldValueList($fbUserId, $fbFields);

        $out = array();
        foreach ($fieldDtoList as $fieldDto)
        {
            /* @var $fieldDto FBCONNECT_BOL_Field */
            if ( empty($fbFieldValues[$fieldDto->fbField]) )
            {
                continue;
            }
            
            $class = $fieldDto->converter;
            if (empty($converterList[$class]))
            {
                $converter = new $class($userId);
            }
            $out[$fieldDto->question] = $converter->convert($fieldDto->question, $fieldDto->fbField, $fbFieldValues[$fieldDto->fbField]);
        }

        return $out;
    }

    public function getOWQuestionDtoList()
    {
        $aliases = $this->findAliasList();

        $questions = BOL_QuestionService::getInstance()->findAllQuestions();

        $out = array();
        foreach ($questions as $question)
        {
            /* @var $question BOL_Question */
            $isText = in_array($question->presentation, array(
                BOL_QuestionService::QUESTION_PRESENTATION_TEXT,
                BOL_QuestionService::QUESTION_PRESENTATION_TEXTAREA,
                BOL_QuestionService::QUESTION_PRESENTATION_URL
            ));
            $hasAlias = !empty($aliases[$question->name]);

            if ($isText || $hasAlias)
            {
                $out[] = $question;
            }
        }

        return $out;
    }

    public function getPossibleFbFieldList($questionName = null)
    {
        switch ($questionName)
        {
            case 'username':
                return array('name');
            case 'email':
                return array('email');
        }

        return array('first_name', 'middle_name', 'last_name', 'name', 'link', "pic_square", "pic_big");
    }

    public function assignQuestion($question, $fbField, $converter = 'FBCONNECT_FC_TextFieldConverter')
    {
        $fieldDto = $this->fieldDao->findByQuestion($question);
        if ($fieldDto === null)
        {
            $fieldDto = new FBCONNECT_BOL_Field();
        }

        $fieldDto->question = $question;
        $fieldDto->fbField = $fbField;
        $fieldDto->converter = $converter;

        $this->fieldDao->save($fieldDto);
    }

    public function unsetQuestion($question)
    {
        $fieldDto = $this->fieldDao->findByQuestion($question);

        if ($fieldDto === null)
        {
            return;
        }

        $this->fieldDao->delete($fieldDto);
    }

    public function findAliasDtoList()
    {
        return $this->fieldDao->findAll();
    }

    public function findAliasList()
    {
        $out = array();
        $aliases = $this->findAliasDtoList();
        foreach($aliases as $alias)
        {
            $out[$alias->question] = $alias->fbField;
        }

        return $out;
    }
}