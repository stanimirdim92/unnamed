<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.16
 *
 * @link       TBA
 */

namespace Admin\Controller;

use Admin\Model\Menu;
use Admin\Form\MenuForm;

final class MenuController extends IndexController
{
    /**
     * @var MenuForm $menuForm
     */
    private $menuForm = null;

    /**
     * @param MenuForm $menuForm
     */
    public function __construct(MenuForm $menuForm = null)
    {
        parent::__construct();
        $this->menuForm = $menuForm;
    }

    /**
     * @param MvcEvent $e
     */
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        $this->addBreadcrumb(["reference"=>"/admin/menu", "name"=>$this->translate("MENUS")]);
        parent::onDispatch($e);
    }

    /**
     * Initialize menus and their submenus. 1 query to rule them all!
     *
     * @return string|null
     */
    private function showMenus()
    {
        $menu = $this->getTable("Menu")->fetchList(false, ['id', 'class', 'menulink', 'caption', 'language', 'active', 'parent'], ["language" => $this->language()], "AND", null, "id, menuOrder")->getDataSource();

        if (count($menu) > 0) {
            $menus = ['menus' => [], 'submenus' => []];

            foreach ($menu as $submenus) {
                $menus['menus'][$submenus['id']] = $submenus;
                $menus['submenus'][$submenus['parent']][] = $submenus['id'];
            }

            return $this->generateMenu(0, $menus);
        }
        return;
    }

    /**
     * Builds menu HTML.
     *
     * @method generateMenu
     *
     * @param int $parent
     * @param array $menu
     *
     * @return string generated html code
     */
    private function generateMenu($parent = 0, array $menu = [])
    {
        $output = "";
        $escaper = new \Zend\Escaper\Escaper('utf-8');
        if (isset($menu["submenus"][$parent])) {
            foreach ($menu['submenus'][$parent] as $id) {
                $output .= "<ul class='table-row'>";
                $output .= "<li class='table-cell flex-2'>{$menu['menus'][$id]['caption']}</li>";
                $output .= "<li class='table-cell flex-b'><a title='{$this->translate('DETAILS')}' hreflang='{$this->language("languageName")}' itemprop='url' href='/admin/menu/detail/{$escaper->escapeUrl($menu['menus'][$id]['id'])}' class='btn btn-sm blue'><i class='fa fa-info'></i></a></li>";
                $output .= "<li class='table-cell flex-b'><a title='{$this->translate('EDIT')}' hreflang='{$this->language("languageName")}' itemprop='url' href='/admin/menu/edit/{$escaper->escapeUrl($menu['menus'][$id]['id'])}' class='btn btn-sm orange'><i class='fa fa-pencil'></i></a></li>";
                if ($menu['menus'][$id]['active'] == 0) {
                    $output .= "<li class='table-cell flex-b'><a title='{$this->translate('DEACTIVATED')}' hreflang='{$this->language("languageName")}' itemprop='url' href='/admin/menu/activate/{$escaper->escapeUrl($menu['menus'][$id]['id'])}' class='btn btn-sm deactivated'><i class='fa fa-minus-square-o'></i></a></li>";
                } else {
                    $output .= "<li class='table-cell flex-b'><a title='{$this->translate('ACTIVE')}' hreflang='{$this->language("languageName")}' itemprop='url' href='/admin/menu/deactivate/{$escaper->escapeUrl($menu['menus'][$id]['id'])}' class='btn btn-sm active'><i class='fa fa fa-check-square-o'></i></a></li>";
                }
                $output .= "
                <li class='table-cell flex-b'>
                    <button role='button' aria-pressed='false' aria-label='{$this->translate("DELETE")}' id='{$menu['menus'][$id]['id']}' type='button' class='btn btn-sm delete dialog_delete' title='{$this->translate("DELETE")}'><i class='fa fa-trash-o'></i></button>
                        <div role='alertdialog' aria-labelledby='dialog{$menu['menus'][$id]['id']}Title' class='delete_{$menu['menus'][$id]['id']} dialog_hide'>
                           <p id='dialog{$menu['menus'][$id]['id']}Title'>{$this->translate("DELETE_CONFIRM_TEXT")} &laquo;{$menu['menus'][$id]['caption']}&raquo;</p>
                            <ul>
                                <li>
                                    <a class='btn delete' href='/admin/menu/delete/{$escaper->escapeUrl($menu['menus'][$id]['id'])}'><i class='fa fa-trash-o'></i> {$this->translate("DELETE")}</a>
                                </li>
                                <li>
                                    <button role='button' aria-pressed='false' aria-label='{$this->translate("CANCEL")}' class='btn btn-default cancel'><i class='fa fa-times'></i> {$this->translate("CANCEL")}</button>
                                </li>
                            </ul>
                        </div>
                </li>";

                $output .= "</ul>";
                $output .= $this->generateMenu($id, $menu);
            }
        }

        return $output;
    }

    /**
     * This action shows the list with all menus.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $this->getView()->setTemplate("admin/menu/index");

        $menus = $this->showMenus();
        $this->getView()->menus = $menus;

        return $this->getView();
    }

    /**
     * This action serves for adding a new menu
     *
     * @return ViewModel
     */
    protected function addAction()
    {
        $this->getView()->setTemplate("admin/menu/add");
        $this->initForm($this->translate("ADD_NEW_MENU"), null);
        $this->addBreadcrumb(["reference"=>"/admin/menu/add", "name"=>$this->translate("ADD_NEW_MENU")]);
        return $this->getView();
    }

    /**
     * This action presents a edit form for Menu object with a given id.
     * Upon POST the form is processed and saved.
     *
     * @return ViewModel
     */
    protected function editAction()
    {
        $this->getView()->setTemplate("admin/menu/edit");
        $menu = $this->getTable("menu")->getMenu((int)$this->getParam("id", 0), $this->language())->current();
        $this->addBreadcrumb(["reference"=>"/admin/menu/edit/{$menu->getId()}", "name"=> $this->translate("EDIT_MENU")." &laquo;".$menu->getCaption()."&raquo;"]);
        $this->initForm($this->translate("EDIT_MENU"), $menu);
        return $this->getView();
    }

    protected function deactivateAction()
    {
        $this->getTable("menu")->toggleActiveMenu((int)$this->getParam("id", 0), 0);
        $this->setLayoutMessages($this->translate("MENU_DISABLE_SUCCESS"), "success");
    }

    protected function activateAction()
    {
        $this->getTable("menu")->toggleActiveMenu((int)$this->getParam("id", 0), 1);
        $this->setLayoutMessages($this->translate("MENU_ENABLE_SUCCESS"), "success");
    }

    /**
     * this action deletes a menu object with a provided id.
     */
    protected function deleteAction()
    {
        $this->getTable("menu")->deleteMenu((int)$this->getParam("id", 0), $this->language());
        $this->setLayoutMessages($this->translate("DELETE_MENU_SUCCESS"), "success");
    }

    /**
     * this action shows menu details from the provided id and session language.
     *
     * @return ViewModel
     */
    protected function detailAction()
    {
        $this->getView()->setTemplate("admin/menu/detail");
        $menu = $this->getTable("menu")->getMenu((int)$this->getParam("id", 0), $this->language())->current();
        $this->getView()->menuDetail = $menu;
        $this->addBreadcrumb(["reference"=>"/admin/menu/detail/".$menu->getId()."", "name"=>"&laquo;". $menu->getCaption()."&raquo; ".$this->translate("DETAILS")]);
        return $this->getView();
    }

    /**
     * This action will clone the object with the provided id and return to the index view.
     */
    protected function cloneAction()
    {
        $menu = $this->getTable("menu")->duplicate((int)$this->getParam("id", 0), $this->language());
        $this->setLayoutMessages("&laquo;".$menu->getCaption()."&raquo; ".$this->translate("CLONE_SUCCESS"), "success");
    }

    /**
     * This is common function used by add and edit actions (to avoid code duplication).
     *
     * @param string $label button title
     * @param Menu $menu menu object
     */
    private function initForm($label = '', Menu $menu = null)
    {
        if (!$menu instanceof Menu) {
            $menu = new Menu([]);
        }

        /**
         * @var MenuForm $form
         */
        $form = $this->menuForm;
        $form->bind($menu);
        $form->get("submit")->setValue($label);
        $this->getView()->form = $form;
        $this->getView()->editMenu = $menu;

        if ($this->getRequest()->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $this->getTable("menu")->saveMenu($menu);
                $this->setLayoutMessages("&laquo;".$menu->getCaption()."&raquo; ".$this->translate("SAVE_SUCCESS"), 'success');
                return $this->redirect()->toRoute('admin/default', ['controller' => 'menu']);
            } else {
                $this->setLayoutMessages($form->getMessages(), 'error');
            }
        }
    }
}
