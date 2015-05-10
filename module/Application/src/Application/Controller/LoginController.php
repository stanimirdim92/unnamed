<?php
/**
 * MIT License
 * ===========
 *
 * Copyright (c) 2015 Stanimir Dimitrov <stanimirdim92@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category   Application\Login
 * @package    ZendPress
 * @author     Stanimir Dimitrov <stanimirdim92@gmail.com>
 * @copyright  2015 Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    0.03
 * @link       TBA
 */

namespace Application\Controller;


use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Zend\Http\PhpEnvironment\RemoteAddress;

use Application\Form\LoginForm;
use Application\Form\ResetPasswordForm;
use Application\Form\NewPasswordForm;
use Application\Model\ResetPassword;

use Custom\Plugins\Mailing;
use Custom\Plugins\Functions;
require '/vendor/Custom/Plugins/Password.php';

class LoginController extends IndexController
{
    /**
     * User access
     */
    const ROLE_USER = 1;

    /**
     * Admin access
     */
    const ROLE_ADMIN = 10;

    /**
     * @var Zend\Db\Adapter\Adapter
     */
    private $adapter = null;

    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        parent::onDispatch($e);
        $this->checkIdentity();
    }

    /**
     * Get database and check if supplied username and password matches.
     *
     * @param array $options
     * @var Zend\Crypt\Password\Bcrypt
     * @var Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter
     * @return DbTable|Adapter
     */
    private function getAuthAdapter(array $options = [])
    {
        $credentialCallback = function ($passwordInDatabase, $passwordProvided) {
            return password_verify($passwordProvided, $passwordInDatabase);
        };

        $authAdapter = new \Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter($this->getAdapter(), "user", "email", "password", $credentialCallback);
        $authAdapter->setIdentity($options["email"]);
        $authAdapter->setCredential($options["password"]);

        return $authAdapter;
    }

    /**
     * @var Zend\Db\Adapter\Adapter
     * @return Adapter
     */
    private function getAdapter()
    {
        if (!$this->adapter) {
            $this->adapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        }
        return $this->adapter;
    }

    /**
     * @var Zend\Form\Form
     * @return LoginForm
     */
    public function indexAction()
    {
        $form = new LoginForm(['action' => '/login/processlogin', 'method' => 'post']);
        $form->get("login")->setValue($this->translation->LOGIN);
        $form->get("email")->setLabel($this->translation->EMAIL);
        $form->get("password")->setLabel($this->translation->PASSWORD);
        $this->view->form = $form;
        return $this->view;
    }

    public function processloginAction()
    {
        // Check if we have a POST request
        if (!$this->getRequest()->isPost()) {
            return $this->logoutAction("/login");
        }

        // Get our form and validate it
        $form = new LoginForm(['action' => '/login/processlogin', 'method' => 'post']);
        $form->setInputFilter($form->getInputFilter());
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            $this->formErrors($form->getMessages());
            return $this->logoutAction("/login");
        }

        $adapter = $this->getAuthAdapter($form->getData());
        $auth = new AuthenticationService();
        // TODO add setStorage
        $result = $auth->authenticate($adapter);
        if (!$result->isValid()) {
            $this->cache->error = $this->translation->LOGGIN_ERROR;
            return $this->redirect()->toUrl("/login");
        } else {
            $role = self::ROLE_USER;
            $includeRows = ['id', 'name', 'username', 'email', 'deleted', 'image', 'hideEmail', 'userClass', 'ban', 'admin', 'language', 'country'];
            $excludeRows = ['ip', 'password', 'registered', 'lastLogin', 'birthDate', 'salt'];
            $data = $adapter->getResultRowObject($includeRows, $excludeRows);
            $user = $this->getTable('user')->getUser($data->id);

            if ($user->getDeleted()) {
                $this->cache->error = $this->translation->LOGGIN_ERROR;
                return $this->logoutAction("/login");
            }
            if ($user->getAdmin() === 1) {
                $role = self::ROLE_ADMIN;
            }

            $user->setServiceManager(null);
            $user->setLastLogin(date("Y-m-d H:i:s", time()));
            $remote = new RemoteAddress();
            $user->setIp($remote->getIpAddress());
            $this->getTable('user')->saveUser($user);

            $data->role = (int) $role;
            $data->logged = true;
            $auth->getStorage()->write($data);
            $authSession = new Container('ul'); //user login
            $authSession->setExpirationSeconds(7200); // 2hrs
            return $this->redirect()->toUrl("/");
        }
    }

    public function newpasswordAction()
    {
        $token = (string) $this->getParam('id', null);
        if (Functions::strLength($token) !== 64) {
            throw new \Exception("Wrong string");
        }

        $tokenExist = $this->getTable("resetpassword")->fetchList(false, ["user", "token", "date"], ["token" => $token, "date" => ">= DATE_SUB(NOW(), INTERVAL 24 HOUR)"], "AND");
        if (count($tokenExist) !== 1) {
            $this->setErrorNoParam($this->translation->LINK_EXPIRED);
            return $this->redirect()->toUrl("/login");
        }

        $form = new NewPasswordForm($tokenExist);
        $form->get("password")->setLabel($this->translation->PASSWORD)->setAttribute("placeholder", $this->translation->PASSWORD);
        $form->get("repeatpw")->setLabel($this->translation->REPEAT_PASSWORD)->setAttribute("placeholder", $this->translation->REPEAT_PASSWORD);
        $form->get("resetpw")->setValue($this->translation->RESET_PW);

        /**
         * temporary create new view variable to hold the user id.
         * After the password is reset the variable is destroyed.
         * Hidden fields will work, but they are more easier to hack.
         */
        $this->cache->resetpwUserId = $tokenExist->current()->user;
        $this->view->form = $form;
        return $this->view;
    }

    public function newpasswordprocessAction()
    {
        $form = new NewPasswordForm(['action' => '/login/newpasswordprocess', 'method' => 'post']);

        if ($this->getRequest()->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $user = $this->getTable("user")->getUser($this->cache->resetpwUserId);
                unset($this->cache->resetpwUserId);
                $pw = Functions::createPassword($formData["password"]);
                if (!empty($pw)) {
                    $user->setSalt("");
                    $user->setPassword($pw);
                    $user->setIp($remote->getIpAddress());
                    $this->getTable("user")->saveUser($user);
                    $this->cache->success = $this->translation->NEW_PW_SUCCESS;
                    return $this->redirect()->toUrl("/login");
                }
                throw new Exception\RuntimeException($this->translation->PASSWORD_NOT_GENERATED);
            } else {
                $this->formErrors($form->getMessages());
                return $this->redirect()->toUrl("/login");
            }
        }
    }

    public function resetpasswordAction()
    {
        $form = new ResetPasswordForm(['action' => '/login/resetpassword', 'method' => 'post']);
        $form->get("resetpw")->setValue($this->translation->RESET_PW);
        $form->get("email")->setLabel($this->translation->EMAIL);
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $existingEmail = $this->getTable("User")->fetchList(false, "email = '".$formData['email']."'");

                if (count($existingEmail) === 1) {
                    $token = Functions::generateToken(48); // returns 64 characters long string
                    $user = $this->getTable("User")->getUser($existingEmail->current()->getId());
                    unset($existingEmail);
                    $resetpw = new ResetPassword();
                    $remote = new RemoteAddress();
                    $resetpw->setToken($token);
                    $resetpw->setUser($user->getId());
                    $resetpw->setDate(date("Y-m-d H:i:s", time()));
                    $resetpw->setIp($remote->getIpAddress());
                    $this->getTable("resetpassword")->saveResetPassword($resetpw);

                    $message = $this->translation->NEW_PW_TEXT." ".$_SERVER["SERVER_NAME"]."/login/newpassword/id/{$token}";
                    $result = Mailing::sendMail($formData['email'], $user->toString(),  $this->translation->NEW_PW_TITLE, $message, "noreply@".$_SERVER["SERVER_NAME"], $_SERVER["SERVER_NAME"]);
                    if (!$result) {
                        $this->cache->error = $this->translation->EMAIL_NOT_SENT;
                        return $this->redirect()->toUrl("/login/resetpassword");
                    }

                    $this->cache->success = $this->translation->PW_SENT." ".$formData['email'];
                    $this->view->setTerminal(true);
                    return $this->redirect()->toUrl("/");
                } else {
                    $this->setErrorNoParam($this->translation->EMAIL." <b>".$formData["email"]."</b> ".$this->translation->NOT_FOUND);
                    return $this->redirect()->toUrl("/login/resetpassword");
                }
            } else {
                $this->formErrors($form->getMessages());
                return $this->redirect()->toUrl("/login");
            }
        }
        return $this->view;
    }

    /**
     * Create new instance of $cache and set it to empty|null
     *
     * Clear all sessions (cache, translations etc.)
     * @param string $redirectTo
     * @return void
     */
    protected function logoutAction($redirectTo = null)
    {
        $this->cache->getManager()->getStorage()->clear();
        $this->translation->getManager()->getStorage()->clear();
        $this->cache = new Container("cache");
        $this->translation = new Container("translations");
        $authSession = new Container('ul');
        $authSession->getManager()->getStorage()->clear();
        $auth = new AuthenticationService();
        $auth->clearIdentity();
        if (!$redirectTo) {
            $redirectTo = "/";
        }
        return $this->redirect()->toUrl($redirectTo);
    }
}
