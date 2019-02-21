<?php

class FBCONNECT_CTRL_CompleteProfile extends BASE_CTRL_CompleteProfile
{
    public function fillRequiredQuestions( $params )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }

        $user = OW::getUser()->getUserObject();

        $accountType = BOL_QuestionService::getInstance()->findAccountTypeByName($user->accountType);

        if ( empty($accountType) )
        {
            throw new Redirect404Exception();
        }

        $language = OW::getLanguage();

        $event = new OW_Event( OW_EventManager::ON_BEFORE_USER_COMPLETE_PROFILE, array( 'user' => $user ) );
        OW::getEventManager()->trigger($event);

        // -- Edit form --

        $form = new EditQuestionForm('requiredQuestionsForm', $user->id);
        $form->setId('requiredQuestionsForm');

        $editSubmit = new Submit('submit');
        $editSubmit->addAttribute('class', 'ow_button ow_ic_save');

        $editSubmit->setValue($language->text('base', 'continue_button'));

        $form->addElement($editSubmit);

        $questions = $this->questionService->getEmptyRequiredQuestionsList($user->id);

        if ( FBCONNECT_BOL_Service::getInstance()->isEmailAlias($user->id) )
        {
            $questions[] = (array) BOL_QuestionService::getInstance()->findQuestionByName('email');
        }

        if ( empty($questions) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('base_default_index'));
        }

        $section = null;
        $questionArray = array();
        $questionNameList = array();

        foreach ( $questions as $sort => $question )
        {
            if ( $section !== $question['sectionName'] )
            {
                $section = $question['sectionName'];
            }

            $questionArray[$section][$sort] = $questions[$sort];
            $questionNameList[] = $questions[$sort]['name'];
        }

        $this->assign('questionArray', $questionArray);

        $questionValues = $this->questionService->findQuestionsValuesByQuestionNameList($questionNameList);

        $form->addQuestions($questions, $questionValues, array());

        if ( OW::getRequest()->isPost() )
        {
            if ( $form->isValid($_POST) )
            {
                $this->saveRequiredQuestionsData($form->getValues(), $user->id);
            }
        }
        else
        {
            OW::getDocument()->addOnloadScript(" OW.info(".  json_encode(OW::getLanguage()->text('base', 'complete_profile_info')).") ");
        }

        $this->addForm($form);

        $language->addKeyForJs('base', 'join_error_username_not_valid');
        $language->addKeyForJs('base', 'join_error_username_already_exist');
        $language->addKeyForJs('base', 'join_error_email_not_valid');
        $language->addKeyForJs('base', 'join_error_email_already_exist');
        $language->addKeyForJs('base', 'join_error_password_not_valid');
        $language->addKeyForJs('base', 'join_error_password_too_short');
        $language->addKeyForJs('base', 'join_error_password_too_long');

        //include js
        $onLoadJs = " window.edit = new OW_BaseFieldValidators( " .
            json_encode(array(
                'formName' => $form->getName(),
                'responderUrl' => OW::getRouter()->urlFor("BASE_CTRL_Edit", "ajaxResponder"))) . ",
                " . UTIL_Validator::EMAIL_PATTERN . ", " . UTIL_Validator::USER_NAME_PATTERN . ", " . $user->id . " ); ";

        OW::getDocument()->addOnloadScript($onLoadJs);

        $jsDir = OW::getPluginManager()->getPlugin("base")->getStaticJsUrl();
        OW::getDocument()->addScript($jsDir . "base_field_validators.js");
    }
}

