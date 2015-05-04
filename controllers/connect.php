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
 * Facebook Connect Controller
 *
 * @author Sergey Kambalin <greyexpert@gmail.com>
 * @package ow_plugins.fbconnect.controllers
 * @since 1.0
 */
class FBCONNECT_CTRL_Connect extends OW_ActionController
{
    /**
     *
     * @var FBCONNECT_BOL_Service
     */
    private $service;

    public function init()
    {
        $this->service = FBCONNECT_BOL_Service::getInstance();
    }

    public function login( $params )
    {
        $backUri = empty($_GET['backUri']) ? '' : urldecode($_GET['backUri']);
        $backUrl = OW_URL_HOME . $backUri;
        
        $language = OW::getLanguage();

        $fbUser = $this->service->fbRequireUser();

        $authAdapter = new FBCONNECT_CLASS_AuthAdapter($fbUser);

        // Login and redirect if already registered
        if ( $authAdapter->isRegistered() )
        {
            $authResult = OW::getUser()->authenticate($authAdapter);
            if ( $authResult->isValid() )
            {
                OW::getFeedback()->info($language->text('fbconnect', 'login_success_msg'));
            }
            else
            {
                OW::getFeedback()->error($language->text('fbconnect', 'login_failure_msg'));
            }

            $this->redirect($backUrl);
        }

        //Register if not registered
        

        $questions = $this->service->requestQuestionValueList($fbUser);
        
        if ( empty($questions["email"]) || empty($questions["username"]) )
        {
            OW::getFeedback()->error($language->text('fbconnect', 'join_incomplete'));
            
            $this->redirect($backUrl);
        }

        $username = $questions['username'];
        $password = uniqid();

        $userByEmail = BOL_UserService::getInstance()->findByEmail($questions['email']);

        if ( $userByEmail !== null )
        {
            OW::getUser()->login($userByEmail->id);
            OW::getFeedback()->info($language->text('fbconnect', 'login_success_msg'));

            $this->redirect($backUrl);
        }
        
        $validUsername = UTIL_Validator::isUserNameValid($username);
        $username = $validUsername ? $username : uniqid("user_");
        
        try
        {
            $user = BOL_UserService::getInstance()->createUser($username, $password, $questions['email'], null, true);
            
            if ( !$validUsername )
            {
                $user->username = "user_" . $user->id;
                
                BOL_UserService::getInstance()->saveOrUpdate($user);
            }
            
            unset($questions['username']);
            unset($questions['email']);
        }
        catch ( Exception $e )
        {
            switch ( $e->getCode() )
            {
                case BOL_UserService::CREATE_USER_DUPLICATE_EMAIL:
                    OW::getFeedback()->error($language->text('fbconnect', 'join_dublicate_email_msg'));
                    $this->redirect($backUrl);
                    break;

                case BOL_UserService::CREATE_USER_INVALID_USERNAME:
                    OW::getFeedback()->error($language->text('fbconnect', 'join_incorrect_username'));
                    $this->redirect($backUrl);
                    break;

                default:
                    OW::getFeedback()->error($language->text('fbconnect', 'join_incomplete'));
                    $this->redirect($backUrl);
            }
        }

        if ( !empty($questions['picture_big']) )
        {
            BOL_AvatarService::getInstance()->setUserAvatar($user->id, $questions['picture_big'], array('isModerable' => false, 'trackAction' => false ));

            unset($questions['picture_small']);
            unset($questions['picture_medium']);
            unset($questions['picture_big']);
        }

        BOL_QuestionService::getInstance()->saveQuestionsData(array_filter($questions), $user->id);

        $authAdapter->register($user->id);

        $authResult = OW_Auth::getInstance()->authenticate($authAdapter);
        if ( $authResult->isValid() )
        {
            $event = new OW_Event(OW_EventManager::ON_USER_REGISTER, array(
                'method' => 'facebook',
                'userId' => $user->id,
                'params' => $_GET
            ));
            OW::getEventManager()->trigger($event);

            OW::getFeedback()->info($language->text('fbconnect', 'join_success_msg'));
        }
        else
        {
            OW::getFeedback()->error($language->text('fbconnect', 'join_failure_msg'));
        }

        $this->redirect($backUrl);
    }

    public function xdReceiver()
    {
        $cache_expire = 60*60*24*365;
        header("Pragma: public");
        header("Cache-Control: maxage=".$cache_expire);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$cache_expire) . ' GMT');

        echo '<script src="//connect.facebook.net/en_US/all.js"></script>';

        exit();
    }
}