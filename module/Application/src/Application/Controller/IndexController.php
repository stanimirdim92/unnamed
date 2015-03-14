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
 * @category   Application\Index
 * @package    ZendPress
 * @author     Stanimir Dimitrov <stanimirdim92@gmail.com>
 * @copyright  2015 Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    0.03
 * @link       TBA
 */
namespace Application\Controller;

use Zend\Session\Container;

use Custom\Plugins\Functions;
use Custom\Plugins\Mailing;
class IndexController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * @var null $cache holds any other session information, contains warning, success and error vars that are shown just once and then reset
     * @return Zend\Session\Container
     */
    protected $cache = null;

    /**
     * @var null $view creates instance to view model
     * @return Zend\View\Model\ViewModel
     */
    protected $view = null;

    /**
     * @var null $translation holds language data as well as all translations
     * @return Zend\Session\Container
     */
    protected $translation = null;

    /**
     * DRY variable to hold the language. Easier to work with
     *
     * @var null
     * @return int $this->translation->language
     */
    protected $langTranslation = null;
    protected $gdgg = null;

    /**
     * Used to detect actions without IDs. Inherited in all other classes
     */
    const NO_ID = 'Not found';

    /**
     * constructor
     */
    public function __construct()
    {
        $this->view = new \Zend\View\Model\ViewModel();
        $this->initCache();
        $this->initTranslation();
    }

    /**
     * Initialize any variables before controller actions
     *
     * @param \Zend\Mvc\MvcEvent $e
     */
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        parent::onDispatch($e);
        $this->initMenus();
        $this->initViewVars();
    }

/****************************************************
 * START OF ALL INIT FUNCTIONS
 ****************************************************/

    /**
     * initialize any session variables in this method
     * @return void
     */
    protected function initCache()
    {
        if(!$this->cache)
        {
            $this->cache = new Container("cache");
            $this->view->cache = $this->cache;
        }
    }

    /**
     * initialize any view related stuff
     * @return void
     */
    protected function initViewVars()
    {
        $lang = $this->getTable("Language")->getLanguage($this->langTranslation);
        $this->view->translation = $this->translation;
        $this->view->languages = $this->getTable("Language")->fetchList(false, array(), array("active" => 1), "AND", null, "name ASC");
        $this->view->langName = $lang->getName();
        $this->view->controller = $this->getParam('__CONTROLLER__');
        $this->view->action = $this->getParam('action');
        $this->view->baseURL = $this->getRequest()->getUri()->getHost().$this->getRequest()->getRequestUri();
    }

    /**
     * initialize languages and language-related stuff like translations.
     * @return  void
     */
    protected function initTranslation()
    {
        if(!isset($this->translation->language))
        {
            $this->translation = Functions::initTranslations(1, true);
            $this->translation->language = 1;
        }
        // keeping it simple and DRY
        $this->langTranslation = ((int) $this->translation->language > 0 ? $this->translation->language : 1);
    }

    /**
     * initialize languages and language-related stuff like translations.
     * @return  void
     */
    protected function initMenus()
    {
        $menu = $this->getTable("Menu")->fetchList(false, array(), array("parent" => 0, "menutype" => 0, "language" => $this->langTranslation), "AND", null, "menuOrder ASC");
        $submenus = array();

        foreach($menu as $submenu)
        {
            $submenus[$submenu->id] = $this->getTable("Menu")->fetchList(false, array(), array("parent" => (int) $submenu->id, "menutype" => 0, "language" => $this->langTranslation), "AND", null, "menuOrder ASC");
        }
        $this->view->menus = $menu;
        $this->view->submenus = $submenus;
    }

/****************************************************
 * START OF ALL MAIN/SHARED FUNCTIONS
 ****************************************************/

    /**
     * @param null $name
     * @return Ambigous <object, multitype:>
     */
    protected function getTable($name = null)
    {
        if (!is_string($name) || !$name)
        {
            throw new \InvalidArgumentException(__METHOD__ . ' must be string and must not be empty');
        }
        return $this->getServiceLocator()->get($name . "Table");
    }

    /**
     * See if user is logged in.
     *
     * @throws AuthorizationException
     * @return void
     */
    protected function checkIdentity()
    {
        $auth = new \Zend\Authentication\AuthenticationService();
        if($auth->hasIdentity())
        {
            if(($auth->getIdentity()->role === 1 || $auth->getIdentity()->role === 10) && $auth->getIdentity()->logged === true)
            {
                return $this->redirect()->toUrl("/");
            }
            $this->clearUserData($auth); // something is wrong, clear all user data
        }
    }

    private function clearUserData($auth)
    {
        $this->cache->getManager()->getStorage()->clear();
        $this->translation->getManager()->getStorage()->clear();
        $authSession = new Container('cache');
        $authSession->getManager()->getStorage()->clear();
        $auth->clearIdentity();
        unset($this->cache->user, $authSession);
        $this->cache = null;
        throw new \Custom\Error\AuthorizationException($this->translation->ERROR_AUTHORIZATION);
    }

    /**
     * Shorthand method for getting params from URLs. Makes code easier to modify and avoids DRY code
     *
     * @param String $paramName
     * @param null $default
     * @return mixed
     */
    protected function getParam($paramName = null, $default = null)
    {
        $param = $this->params()->fromPost($paramName, 0);
        if(!$param)
        {
            $param = $this->params()->fromRoute($paramName, null);
        }
        if(!$param)
        {
            $param = $this->params()->fromQuery($paramName, null);
        }
        if(!$param)
        {
            return $default;
        }
        return trim($param);
    }

    /**
     * @param null $message holds the generated error(s)
     * @return string|array
     */
    protected function setErrorNoParam($message = null)
    {
        if(!empty($message))
        {
            $this->cache->error = $message;
        }
        else if ($message === self::NO_ID)
        {
            $this->cache->error = $this->translation->NO_ID_SET;
        }
        else
        {
            $this->cache->error = $this->translation->ERROR_STRING;
        }
        $this->view->setTerminal(true);
    }

    protected function setErrorCode($code = 404)
    {
        $this->getResponse()->setStatusCode($code);
        $this->view->setTemplate('layout/error-layout');
        return $this->view;
    }

    /**
     * SEO
     *
     * @param null $obj  returns Content object
     * @return  void
     */
    protected function setMetaTags($obj = null)
    {
        $description = $keywords = $text = $preview = $title = null;

        if ($obj)
        {
            if ($obj->current()->getMenuObject() instanceof \Admin\Model\Menu)
            {
                $description = $obj->current()->getMenuObject()->getDescription();
                $keywords = $obj->current()->getMenuObject()->getKeywords();
            }
            $text = $obj->current()->getText();
            $preview = $obj->current()->getPreview();
            $title = $obj->current()->getTitle();
        }

        // must be set from db
        (empty($description) ? $description = "lorem ipsum dolar sit amet" : $description);
        (empty($text) ? $text = "lorem ipsum dolar sit amet" : $text);
        (empty($keywords) ? $keywords = "lorem, ipsum, dolar, sit, amet" : $keywords);
        (empty($preview) ? $preview = "" : $preview);
        (empty($title) ? $title = "ZendPress" : $title);

        $placeholder = $this->getServiceLocator()->get('ViewHelperManager')->get('placeholder')->getContainer("customHead");
        $placeholder->append("<meta itemprop='name' content='ZendPress'>\r\n");
        $placeholder->append("<meta itemprop='description' content='".substr(strip_tags($text), 0, 150)."..."."'>\r\n");
        $placeholder->append("<meta itemprop='title' content='".$title."'>\r\n");
        $placeholder->append("<meta itemprop='image' content='".$preview."'>\r\n");

        $vhm = $this->getServiceLocator()->get('ViewHelperManager')->get('headMeta');
        $vhm->appendName('keywords', $keywords);
        $vhm->appendName('description', $description);
        $vhm->appendProperty('og:image', $preview);
        $vhm->appendProperty("og:title", $title);
        $vhm->appendProperty("og:description", $description);
    }

/****************************************************
 * START OF ALL ACTION METHODS
 ****************************************************/

    public function indexAction()
    {
        return $this->view;
    }

    public function contactAction()
    {
        $form = new \Application\Form\ContactForm();
        $form->get("email")->setLabel($this->translation->EMAIL);
        $form->get("name")->setLabel($this->translation->NAME)->setAttribute("placeholder", $this->translation->NAME);
        $form->get("subject")->setLabel($this->translation->SUBJECT)->setAttribute("placeholder", $this->translation->SUBJECT);
        $form->get("captcha")->setLabel($this->translation->CAPTCHA)->setAttribute("placeholder", $this->translation->ENTER_CAPTCHA);
        $form->get("message")->setLabel($this->translation->MESSAGE)->setAttribute("placeholder", $this->translation->ENTER_MESSAGE);

        $this->view->form = $form;
        if($this->getRequest()->isPost())
        {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if($form->isValid())
            {
                $formData = $form->getData();
                $to = "stanimirdim92@gmail.com"; // must be set from db
                try
                {
                    $result = Mailing::sendMail($to, '', $formData['subject'], $formData['message'], $formData['email'], $formData['name']);
                    $this->cache->success = $this->translation->CONTACT_SUCCESS;
                }
                catch (\Exception $e)
                {
                    $this->cache->error = $this->translation->CONTACT_ERROR;
                    $this->setErrorNoParam($e->getMessage());
                }
                return $this->redirect()->toUrl("/contact");
            }
            else
            {
                $error = array();
                foreach($form->getMessages() as $msg)
                {
                    foreach ($msg as $key => $value)
                    {
                        $error[] = $value;
                    }
                }
                $this->setErrorNoParam($error);
                return $this->redirect()->toUrl("/contact");
            }
        }
        return $this->view;
    }
}

?>