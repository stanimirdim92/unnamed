<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.15
 *
 * @link       TBA
 */

namespace Admin\Controller;

use Admin\Model\User;
use Admin\Form\UserForm;
use Zend\Json\Json;
use Zend\View\Model\JsonModel;
use Admin\Exception\AuthorizationException;

final class UserController extends IndexController
{
    /**
     * @var UserForm $userForm
     */
    private $userForm = null;

    /**
     * @param UserForm $userForm
     */
    public function __construct(UserForm $userForm = null)
    {
        parent::__construct();
        $this->userForm = $userForm;
    }

    /**
     * @param MvcEvent $e
     */
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        parent::onDispatch($e);
        $this->addBreadcrumb(["reference"=>"/admin/user", "name"=>$this->translate("USERS")]);
    }

    /**
     * This action shows the list with all users.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $this->getView()->setTemplate("admin/user/index");
        $paginator = $this->getTable("user")->fetchList(true, [], ["deleted" => 0], null, null, "id DESC");
        $paginator->setCurrentPageNumber((int)$this->getParam("page", 1));
        $paginator->setItemCountPerPage(50);
        $this->getView()->paginator = $paginator;
        return $this->getView();
    }

    /**
     * This action presents a modify form for User object with a given id.
     * Upon POST the form is processed and saved.
     *
     * @return ViewModel
     */
    protected function modifyAction()
    {
        $this->getView()->setTemplate("admin/user/modify");
        $user = $this->getTable("user")->getUser((int)$this->getParam("id", 0))->current();
        $this->getView()->user = $user;
        $this->addBreadcrumb(["reference"=>"/admin/user/modify/{$user->getId()}", "name"=> $this->translate("MODIFY_USER")." &laquo;".$user->getName()."&raquo;"]);
        $this->initForm($this->translate("MODIFY_USER"), $user);
        return $this->getView();
    }

    /**
     * This is common function used by add and modify actions (to avoid code duplication).
     *
     * @param string $label
     * @param User $user
     */
    private function initForm($label= '', User $user = null)
    {
        if (!$user instanceof User) {
            throw new AuthorizationException($this->translate("ERROR_AUTHORIZATION"));
        }

        $form = $this->userForm;
        $form->get("submit")->setValue($label);
        $form->bind($user);
        $this->getView()->form = $form;

        if ($this->getRequest()->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $existingEmail = $this->getTable("user")->fetchList(false, ["email"], ["email" => $formData->getEmail()]);
                if (count($existingEmail) > 1) {
                    $this->setLayoutMessages($this->translate("EMAIL_EXIST")." <b>".$formData->getEmail()."</b> ".$this->translate("ALREADY_EXIST"), 'info');
                } else {
                    $this->getTable("user")->saveUser($user);
                    $this->setLayoutMessages("&laquo;".$user->getFullName()."&raquo; ".$this->translate("SAVE_SUCCESS"), "success");
                }
            } else {
                $this->setLayoutMessages($form->getMessages(), 'error');
            }
            return $this->redirect()->toRoute('admin/default', ['controller' => 'user']);
        }
    }

    /**
     * @return ViewModel
     */
    protected function disabledAction()
    {
        $this->getView()->setTemplate("admin/user/disabled");
        $paginator = $this->getTable("user")->fetchList(true, [], ["deleted" => 1], null, null, "id DESC");
        $paginator->setCurrentPageNumber((int)$this->getParam("page", 1));
        $paginator->setItemCountPerPage(50);
        $this->getView()->paginator = $paginator;
        return $this->getView();
    }

    /**
     * In case that a user account has been disabled and it needs to be enabled call this action.
     */
    protected function enableAction()
    {
        $this->getTable("user")->toggleUserState((int)$this->getParam("id", 0), 0);
        $this->setLayoutMessages($this->translate("USER_ENABLE_SUCCESS"), "success");
        return $this->redirect()->toRoute('admin/default', ['controller' => 'user']);
    }

    /**
     * Instead if deleting a user account from the database, we simply disabled it.
     */
    protected function disableAction()
    {
        $this->getTable("user")->toggleUserState((int)$this->getParam("id", 0), 1);
        $this->setLayoutMessages($this->translate("USER_DISABLE_SUCCESS"), "success");
        return $this->redirect()->toRoute('admin/default', ['controller' => 'user']);
    }

    /**
     * this action shows user details from the provided id.
     *
     * @return ViewModel
     */
    protected function detailAction()
    {
        $this->getView()->setTemplate("admin/user/detail");
        $user = $this->getTable("user")->getUser((int)$this->getParam("id", 0))->current();
        $this->getView()->user = $user;
        $this->addBreadcrumb(["reference"=>"/admin/user/detail/".$user->getId()."", "name"=>"&laquo;". $user->getFullName()."&raquo; ".$this->translate("DETAILS")]);
        return $this->getView();
    }

    /**
     * return the list of users that match a given criteria.
     *
     * @return JsonModel
     */
    protected function searchAction()
    {
        $search = (string) $this->getParam('usersearch', null);
        if (isset($search)) {
            if ($this->getRequest()->isXmlHttpRequest()) {
                $this->getView()->setTerminal(true);
                $where = "`name` LIKE '%{$search}%' OR `surname` LIKE '%{$search}%' OR `email` LIKE '%{$search}%' OR `registered` LIKE '%{$search}%'";
                $results = $this->getTable("user")->fetchList(false, [], $where, "OR", null, "id DESC");

                $json = [];
                foreach ($results as $result) {
                    $json[] = Json::encode($result);
                }

                return new JsonModel([
                    'usersearch' => $json,
                    'cancel' => $this->translate("CANCEL"),
                    'deleteuser' => $this->translate("DELETE"),
                    'modify' => $this->translate("MODIFY_USER"),
                    'details' => $this->translate("DETAILS"),
                    'delete_text' => $this->translate("DELETE_CONFIRM_TEXT"),
                ]);
            }
        }
    }
}
