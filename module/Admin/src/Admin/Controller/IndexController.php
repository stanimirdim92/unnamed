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
 * @author     Stanimir Dimitrov <stanimirdim92@gmail.com>
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 * @version    0.0.13
 * @link       TBA
 */

namespace Admin\Controller;

use Zend\Session\Container;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var ViewModel $view creates instance to view model
     */
    protected $view = null;

    /**
     * @var $menuIncrementHack Used increment the menu and stop the second show up of home, login and logout links...
     */
    private $menuIncrementHack = 0;

    /**
     * @var Container $translation holds language id and name
     */
    protected $translation = null;

    /**
     * @var array $breadcrumbs returns an array with links with the current user position on the website
     */
    private $breadcrumbs = [];

    public function __construct()
    {
        $this->view = new ViewModel();
        $this->translation = new Container("translations");

        if (!$this->translation->offSetExists("language")) {
            $this->translation->language = 1;
            $this->translation->languageName = "en";
        }
    }

    /**
     * Initialize any variables before controller actions
     *
     * @param MvcEvent $e
     * @method  IndexController::isAdmin()
     */
    public function onDispatch(MvcEvent $e)
    {
        $this->addBreadcrumb(["reference" => "/admin", "name" => $this->translate("DASHBOARD")]);
        if (APP_ENV !== 'development') {
            $this->isAdmin();
        }

        parent::onDispatch($e);
        $this->initMenus();
        $this->getView()->breadcrumbs = $this->breadcrumbs;
    }

/****************************************************
 * START OF ALL INIT FUNCTIONS
 ****************************************************/
    /**
     * Initialize menus and their submenus. 1 query to rule them all!
     *
     * @return void
     */
    private function initMenus()
    {
        $menu = $this->getTable("AdminMenu")->fetchList(false, [], []);
        if (count($menu) > 0) {
            $menus = ['menus' => [], 'submenus' => []];
            foreach ($menu as $submenus) {
                $menus['menus'][$submenus->getId()] = $submenus;
                $menus['submenus'][$submenus->getParent()][] = $submenus->getId();
            }
            $this->getView()->menu = $this->generateMenu(0, $menus);
        }
        return $this->getView();
    }

    /**
     * @param int $parent
     * @param array $menu
     */
    private function generateMenu($parent = 0, array $menu = [])
    {
        $output = "";
        if (isset($menu["submenus"][$parent])) {
            $output .= "<ul>";

            /**
             * This is a really, really ugly hack
             */
            if ($this->menuIncrementHack === 0) {
                $output .= "<li><a tabindex='1' hreflang='{$this->language("languageName")}' itemprop='url' href='&sol;admin'>{$this->translate("DASHBOARD")}</a></li>";
            }
            $this->menuIncrementHack = 1;
            
            foreach ($menu['submenus'][$parent] as $id) {
                $output .= "<li><a hreflang='{$this->language("languageName")}' class='fa {$menu['menus'][$id]->getClass()}' itemprop='url' href='/admin/{$menu['menus'][$id]->getController()}/{$menu['menus'][$id]->getAction()}'>{$menu['menus'][$id]->getCaption()}</a>";
                $output .= $this->generateMenu($id, $menu);
                $output .= "</li>";
            }
            $output .= "</ul>";
        }

        return $output;
    }

/****************************************************
 * START OF ALL MAIN/SHARED FUNCTIONS
 ****************************************************/

    /**
     * Get Language id or name. Defaults to language - id
     * If a different offset is passed (not-existing-offset) and it doesn't,
     * it will ty to check for a language offset.
     * If language offset is also not found 1 s being returned as the default language id where 1 == en
     *
     * @return  mixed
     */
    protected function language($offset = "language")
    {
        if ($this->translation->offSetExists($offset)) {
            return $this->translation->offSetGet($offset);
        } elseif ($this->translation->offSetExists("language")) {
            return $this->translation->offSetExists("language");
        }
        return 1;
    }

    /**
     * @param array $breadcrumbs
     * @return  ViewModel
     */
    protected function addBreadcrumb(array $breadcrumb = [])
    {
        $this->breadcrumbs[] = $breadcrumb;
    }

    /**
     * Is the user admin? Lets check that.
     * 1. Run this function and see if we are logged in as admin.
     *    If all went fine show the admin area.
     * 2. Else go to Login Controller and attempt to login as [u]real[/u] admin. Just in case log every access to login controller
     * 3. On success run this function. If all went fine, access admin else clear identity and create log
     *
     * @throws AuthorizationException If wrong credentials or not in administrator table
     * @todo create a bruteforce protection for failed login attempts.
     * @todo create a join query for admin column check via the user table.
     * @return mixed
     */
    private function isAdmin()
    {
        $auth = $this->UserData();
        if ($auth->checkIdentity(false, $this->translate("ERROR_AUTHORIZATION"))) {
            if (
                isset($auth->getIdentity()->role)         &&
                ((int) $auth->getIdentity()->role === 10) &&
                isset($auth->getIdentity()->logged)       &&
                $auth->getIdentity()->logged === true
            ) {
                $checkAdminExistence = $this->getTable("administrator")->fetchList(false, [], ["user" => $auth->getIdentity()->id])->current();
                if (count($checkAdminExistence) === 1) {
                    unset($checkAdminExistence);
                    return true;
                }
                return $auth->clearUserData($this->translate("ERROR_AUTHORIZATION"));
            }
            return $auth->clearUserData($this->translate("ERROR_AUTHORIZATION"));
        }
        return $auth->clearUserData($this->translate("ERROR_AUTHORIZATION"));
    }

    /**
     * @return ViewModel
     */
    public function getView()
    {
        return $this->view;
    }

/****************************************************
 * START OF ALL ACTION METHODS
 ****************************************************/

    /**
     * @return  ViewModel
     */
    public function indexAction()
    {
        $this->getView()->setTemplate("admin/index/index");
        return $this->getView();
    }

    /**
     * Select new language
     *
     * This will reload the translations every time the method is being called
     */
    protected function languageAction()
    {
        $language = $this->getTable("language")->getLanguage((int) $this->getParam("id", 1));

        $this->translation->language = $language->getId();
        $this->translation->languageName = $language->getName();

        return $this->redirect()->toUrl("/admin");
    }
}
