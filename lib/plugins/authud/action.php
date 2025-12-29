<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;
use dokuwiki\Logger;
use dokuwiki\Form;

/**
 * DokuWiki Plugin authud (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Lukasz Biegaj <ud@x93.org>
 */
class action_plugin_authud extends ActionPlugin
{
    /**
     * @inheritDoc 
     */
    public function register(EventHandler $controller)
    {
        // we need to alter the login form
        $controller->register_hook('FORM_LOGIN_OUTPUT', 'BEFORE', $this, 'handleFormLoginOutput');
    }

    /**
     * Alter the login form to implement two-step authentication
     *
     * @param Event $event
     * @param array $param
     */
    public function handleFormLoginOutput(Event $event, $param)
    {
        global $INPUT, $ID, $auth;

        if (!is_a($auth, 'auth_plugin_authud')) { return; // UD not even used
        }
        /**
         * @var helper_plugin_authud $helper 
         */
        $helper = $this->loadHelper('authud');

        // Check external session
        $userData = $helper->validateSession();
        if ($userData) {
            // Check if local user exists
            if ($auth->getUserData($userData['user_id']) !== false) {
                // Auto-login existing user
                // we will just redirect user and let the externalAuth handle it
                echo "<script>window.location.href = '".wl($ID, '', true, '&')."';</script>";
                return;
            } else {
                // STATE B: Show nickname selection form
                $this->modifyFormForNickname($event);
                return;
            }
        }

        // No external session - redirect to registration
        $this->redirectToExternalRegistration($event);
    }

    /**
     * Modify form to show only nickname input field
     *
     * @param Event $event
     */
    private function modifyFormForNickname(Event $event)
    {
        /**
         * @var dokuwiki\Form\Form $form 
         */
        $form =& $event->data;

        // Remove password field
        $passPos = $form->findPositionByAttribute('name', 'p');
        if ($passPos !== false) {
            $form->removeElement($passPos);
        }

        // Remove "remember me" checkbox
        $rememberPos = $form->findPositionByAttribute('name', 'r');
        if ($rememberPos !== false) {
            $form->removeElement($rememberPos);
        }

        // Find username field and clear its value
        $userPos = $form->findPositionByAttribute('name', 'u');
        if ($userPos !== false) {
            $element = $form->getElementAt($userPos);
            $element->val(''); // Empty field
            // Update label
            $label = $element->getLabel();
            if ($label) {
                $label->val($this->getLang('choose_nickname'));
            }
        }

        $form->addHTML('<div class="info">' . $this->getLang('nickname_instructions') . '</div>', 2);
    }


    /**
     * Redirect to external registration
     *
     * @param Event $event
     */
    private function redirectToExternalRegistration(Event $event)
    {
        global $ID;
        /**
         * @var dokuwiki\Form\Form $form 
         */
        $form =& $event->data;
        // remove existing elements
        for ($i=$form->elementCount(); $i>0; $i--) {
            $form->removeElement($i);
        }
        // add js redirect to login/registration
        $url = "/login.html?next=".urlencode(wl($ID, ['do' => 'login']));
        $form->addHTML("<script>window.location.href = '$url';</script>");
    }
}
