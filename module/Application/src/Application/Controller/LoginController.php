<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.16
 *
 * @link       TBA
 */

namespace Application\Controller;

use Zend\Authentication\AuthenticationService;
use Zend\Session\Container;
use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Mvc\MvcEvent;
use Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter;
use Application\Model\ResetPassword;
use Application\Form\LoginForm;
use Application\Form\ResetPasswordForm;
use Application\Form\NewPasswordForm;
use Application\Exception\RuntimeException;

final class LoginController extends IndexController
{
    /**
     * @var SD\Adapter|BjyProfiler\Db\Adapter\ProfilingAdapter $adapter
     */
    private $adapter = null;

    /**
     * @var ResetPasswordForm $resetPasswordForm
     */
    private $resetPasswordForm = null;

    /**
     * @var NewPasswordForm $newPasswordForm
     */
    private $newPasswordForm = null;

    /**
     * @var LoginForm $loginForm
     */
    private $loginForm = null;

    /**
     * @param LoginForm $contactForm
     * @param SD\Adapter|BjyProfiler\Db\Adapter\ProfilingAdapter $adapter
     * @param ResetPasswordForm $resetPasswordForm
     * @param NewPasswordForm $newPasswordForm
     */
    public function __construct(
        LoginForm $loginForm = null,
        $adapter = null,
        ResetPasswordForm $resetPasswordForm = null,
        NewPasswordForm $newPasswordForm = null
    ) {
        parent::__construct();
        $this->loginForm = $loginForm;
        $this->adapter = $adapter;
        $this->resetPasswordForm = $resetPasswordForm;
        $this->newPasswordForm = $newPasswordForm;
    }

    /**
     * @param MvcEvent $e
     */
    public function onDispatch(MvcEvent $e)
    {
        parent::onDispatch($e);

        /*
         * If user is logged and tries to access one of the given actions
         * he will be redirected to the root url of the website.
         * For resetpassword and newpassword actions we assume that the user is not logged in.
         */
        if (APP_ENV !== 'development') {
            $this->UserData()->checkIdentity();
        }
    }

    /**
     * Get database and check if given email and password matches.
     *
     * @param array $options
     *
     * @return DbTable|Adapter
     */
    private function getAuthAdapter(array $options = [])
    {
        $credentialCallback = function ($passwordInDatabase, $passwordProvided) {
            return password_verify($passwordProvided, $passwordInDatabase);
        };

        $authAdapter = new CallbackCheckAdapter($this->adapter, "user", "email", "password", $credentialCallback);
        $authAdapter->setIdentity((string) $options["email"]);
        $authAdapter->setCredential((string) $options["password"]);

        return $authAdapter;
    }

    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        $this->getView()->setTemplate("application/login/index");

        /**
         * @var LoginForm $form
         */
        $form = $this->loginForm;

        $form->get("login")->setValue($this->translate("SIGN_IN"));
        $form->get("email")->setLabel($this->translate("EMAIL"));
        $form->get("password")->setLabel($this->translate("PASSWORD"));
        $this->getView()->form = $form;
        return $this->getView();
    }

    public function processloginAction()
    {
        /*
         * Check if we have a POST request
         */
        if (!$this->getRequest()->isPost()) {
            return $this->logoutAction();
        }

        /**
         * @var LoginForm $form
         */
        $form = $this->loginForm;
        $form->setInputFilter($form->getInputFilter());
        $form->setData($this->getRequest()->getPost());

        /*
         * See if form is valid
         */
        if (!$form->isValid()) {
            $this->setLayoutMessages($form->getMessages(), 'error');
            return $this->logoutAction();
        }

        $adapter = $this->getAuthAdapter($form->getData());
        $auth = new AuthenticationService();
        $result = $auth->authenticate($adapter);

        /*
         * See if authentication is valid
         */
        if (!$result->isValid()) {
            $this->setLayoutMessages($result->getMessages(), 'error');
        } else {
            $role = 1;
            $url = "/";
            $includeRows = ['id', 'ip', 'name', 'surname', 'email', 'deleted', 'image', 'admin', 'language'];
            $excludeRows = ['password', 'registered', 'lastLogin', 'birthDate', 'hideEmail', ];
            $data = $adapter->getResultRowObject($includeRows, $excludeRows);
            $user = $this->getTable('user')->getUser($data->id)->current();

            /*
             * If account is disabled/banned (call it w/e you like) clear user data and redirect
             */
            if ((int) $user->getDeleted() === 1) {
                $this->setLayoutMessages($this->translate("LOGIN_ERROR"), 'error');
                return $this->logoutAction();
            }

            /*
             * See if user is admin
             */
            if ((int) $user->getAdmin() === 1) {
                $role = 10;
                $url = "/admin";
            }

            $user->setLastLogin(date("Y-m-d H:i:s", time()));
            $remote = new RemoteAddress();
            $user->setIp($remote->getIpAddress());
            $this->getTable('user')->saveUser($user);

            $data->role = (int) $role;
            $data->logged = true;
            Container::getDefaultManager()->regenerateId();

            $auth->getStorage()->write($data);
            return $this->redirect()->toUrl($url);
        }
    }

    /**
     * The resetpasswordAction has generated a random token string.
     * In order to reset the account password, we need to take that token and validate it first.
     * If everything is fine, we let the user to reset his password.
     *
     * @throws RuntimeException
     */
    protected function newpasswordAction()
    {
        $this->getView()->setTemplate("application/login/newpassword");

        $token = (string) $this->getParam('token', null);
        $func = $this->getFunctions();

        /*
         * Check string bytes length
         */
        if ($func::strLength($token) !== 64) {
            throw new RuntimeException($this->translate("TOKEN_MISMATCH"));
        }

        /**
         * See if token exist or has expired
         */
        $tokenExist = $this->getTable("resetpassword")->fetchList(["user", "token", "date"], "token = '{$token}' AND date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->current();
        if (!$tokenExist) {
            return $this->setLayoutMessages($this->translate("LINK_EXPIRED"), 'error');
        }

        /**
         * @var NewPasswordForm $form
         */

        $form = $this->newPasswordForm;
        $form->get("password")->setLabel($this->translate("PASSWORD"))->setAttribute("placeholder", $this->translate("PASSWORD"));
        $form->get("repeatpw")->setLabel($this->translate("REPEAT_PASSWORD"))->setAttribute("placeholder", $this->translate("REPEAT_PASSWORD"));
        $form->get("resetpw")->setValue($this->translate("RESET_PW"));

        /*
         * temporary create new view variable to hold the user id.
         * After the password is reset the variable is destroyed.
         */
        $this->getView()->resetpwUserId = $tokenExist["user"];
        $this->getView()->form = $form;
        return $this->getView();
    }

    public function newpasswordprocessAction()
    {
        $func = $this->getFunctions();

        /**
         * @var NewPasswordForm $form
         */
        $form = $this->newPasswordForm;

        if ($this->getRequest()->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $pw = $func::createPassword($formData->password);
                if (!empty($pw)) {
                    $user = $this->getTable("user")->getUser($this->getView()->resetpwUserId)->current();
                    $remote = new RemoteAddress();
                    unset($this->getView()->resetpwUserId);
                    $user->setPassword($pw);
                    $user->setIp($remote->getIpAddress());
                    $this->getTable("user")->saveUser($user);
                    $this->setLayoutMessages($this->translate("NEW_PW_SUCCESS"), 'success');
                } else {
                    $this->setLayoutMessages($this->translate("PASSWORD_NOT_GENERATED"), 'error');
                }
            } else {
                $this->setLayoutMessages($form->getMessages(), 'error');
            }
            return $this->redirect()->toUrl("/login");
        }
    }

    /**
     * Show the reset password form. After that see if there is a user with the entered email
     * if there is one, send him an email with a new password reset link and a token, else show error messages.
     */
    protected function resetpasswordAction()
    {
        $this->getView()->setTemplate("application/login/resetpassword");

        /**
         * @var  ResetPasswordForm $form
         */
        $form = $this->resetPasswordForm;

        $form->get("resetpw")->setValue($this->translate("RESET_PW"));
        $form->get("email")->setLabel($this->translate("EMAIL"));
        $this->getView()->form = $form;
        if ($this->getRequest()->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $existingEmail = $this->getTable("User")->fetchList(false, [], ["email" => $formData["email"]])->current();
                if (count($existingEmail) === 1) {
                    $func = $this->getFunctions();
                    $token = $func::generateToken();
                    $resetpw = new ResetPassword();
                    $remote = new RemoteAddress();
                    $resetpw->setToken($token);
                    $resetpw->setUser($existingEmail->getId());
                    $resetpw->setDate(date("Y-m-d H:i:s", time()));
                    $resetpw->setIp($remote->getIpAddress());
                    $this->getTable("resetpassword")->saveResetPassword($resetpw);
                    $message = $this->translate("NEW_PW_TEXT")." ".$_SERVER["SERVER_NAME"]."/login/newpassword/token/{$token}";
                    $result = $this->Mailing()->sendMail($formData["email"], $existingEmail->getFullName(),  $this->translate("NEW_PW_TITLE"), $message, $this->systemSettings("general", "system_email"), $this->systemSettings("general", "site_name"));
                    if (!$result) {
                        $this->setLayoutMessages($this->translate("EMAIL_NOT_SENT"), 'error');
                    } else {
                        $this->setLayoutMessages($this->translate("PW_SENT")." <b>".$formData["email"]."</b>", 'success');
                    }
                } else {
                    $this->setLayoutMessages($this->translate("EMAIL")." <b>".$formData["email"]."</b> ".$this->translate("NOT_FOUND"), 'warning');
                }
            } else {
                $this->setLayoutMessages($form->getMessages(), 'error');
            }
        }
        return $this->getView();
    }

    /**
     * Clear all sessions.
     */
    protected function logoutAction()
    {
        $this->translation->getManager()->getStorage()->clear();
        $this->translation = new Container("translations");
        $auth = new AuthenticationService();
        $auth->clearIdentity();
        return $this->redirect()->toUrl("/");
    }
}
