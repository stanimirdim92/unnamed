<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.18
 *
 * @link       TBA
 */

namespace Admin\Controller;

use Admin\Model\Content;
use Zend\View\Model\JsonModel;
use Admin\Form\ContentForm;
use Zend\File\Transfer\Adapter\Http;
use Zend\Validator\File\IsImage;
use Zend\Validator\File\Size;
use Zend\Validator\File\Extension;

final class ContentController extends BaseController
{
    /**
     * @var array
     */
    protected $acceptCriteria = [
        'Zend\View\Model\JsonModel' => ['application/json'],
        'Zend\View\Model\ViewModel' => ['text/html'],
    ];

    /**
     * @var ContentForm $contentForm
     */
    private $contentForm = null;

    /**
     * @param ContentForm $contentForm
     */
    public function __construct(ContentForm $contentForm = null)
    {
        parent::__construct();

        $this->contentForm = $contentForm;
    }

    /**
     * @param MvcEvent $e
     */
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        $this->addBreadcrumb(["reference"=>"/admin/content", "name"=>$this->translate("CONTENTS")]);
        parent::onDispatch($e);
    }

    /**
     * This action shows the list of all contents.
     *
     * @return ViewModel
     */
    public function indexAction()
    {
        $this->getView()->setTemplate("admin/content/index");

        $paginator = $this->getTable("ContentTable");
        if ((int) $this->getParam("id", 0) === 1) {
            $paginator->where(["type" => 1, "content.language" => $this->language()]);
            $paginator->order("content.date DESC");
        } else {
            $paginator->join("menu", "content.menu=menu.id", [], "inner");
            $paginator->where(["type" => 0, "content.language" => $this->language()]);
            $paginator->order("menu.parent ASC, menu.menuOrder ASC, content.date DESC");
        }

        $paginator = $paginator->fetchPagination();
        $paginator->setCurrentPageNumber((int)$this->getParam("page", 1));
        $paginator->setItemCountPerPage($this->systemSettings('posts', 'content'));
        $this->getView()->paginator = $paginator;
        return $this->getView();
    }

    /**
     * This action serves for adding a new object of type Content.
     *
     * @return ViewModel
     */
    protected function addAction()
    {
        $this->getView()->setTemplate("admin/content/add");
        $this->initForm($this->translate("ADD_NEW_CONTENT"), null);
        $this->addBreadcrumb(["reference"=>"/admin/content/add", "name"=>$this->translate("ADD_NEW_CONTENT")]);
        return $this->getView();
    }

    /**
     * This action presents a edit form for Content object with a given id and session language.
     * Upon POST the form is processed and saved.
     *
     * @return ViewModel
     */
    protected function editAction()
    {
        $this->acceptableviewmodelselector($this->acceptCriteria);

        $this->getView()->setTemplate("admin/content/edit");
        $content = $this->getTable("ContentTable")->getContent((int)$this->getParam("id", 0), $this->language());
        $this->getView()->content = $content;
        $this->addBreadcrumb(["reference"=>"/admin/content/edit/{$content->getId()}", "name"=> $this->translate("EDIT_CONTENT")." &laquo;".$content->getTitle()."&raquo;"]);
        $this->initForm($this->translate("EDIT_CONTENT"), $content);
        return $this->getView();
    }

    /**
     * this action deletes a content.
     */
    protected function deleteAction()
    {
        $this->getTable("ContentTable")->deleteContent((int)$this->getParam("id", 0), $this->language());
        $this->setLayoutMessages($this->translate("DELETE_CONTENT_SUCCESS"), "success");
    }

    /**
     * this action shows content details.
     *
     * @return ViewModel
     */
    protected function detailAction()
    {
        $this->getView()->setTemplate("admin/content/detail");
        $content = $this->getTable("ContentTable")->getContent((int)$this->getParam("id", 0), $this->language());
        $this->getView()->content = $content;
        $this->addBreadcrumb(["reference"=>"/admin/content/detail/".$content->getId()."", "name"=>"&laquo;". $content->getTitle()."&raquo; ".$this->translate("DETAILS")]);
        return $this->getView();
    }

    protected function deactivateAction()
    {
        $this->getTable("ContentTable")->toggleActiveContent((int)$this->getParam("id", 0), 0);
        $this->setLayoutMessages($this->translate("CONTENT_DISABLE_SUCCESS"), "success");
    }

    protected function activateAction()
    {
        $this->getTable("ContentTable")->toggleActiveContent((int)$this->getParam("id", 0), 1);
        $this->setLayoutMessages($this->translate("CONTENT_ENABLE_SUCCESS"), "success");
    }

    /**
     * This action will clone the object.
     */
    protected function cloneAction()
    {
        $this->getTable("ContentTable")->duplicate((int)$this->getParam("id", 0), $this->language());
        $this->setLayoutMessages("&laquo;".$content->getTitle()."&raquo; ".$this->translate("CLONE_SUCCESS"), "success");
    }

    /**
     * This is common function used by add and edit actions.
     *
     * @param string $label button title
     * @param Content $content
     */
    private function initForm($label = '', Content $content = null)
    {
        if (!$content instanceof Content) {
            $content = new Content([]);
        }

        /**
         * @var $form ContentForm
         */
        $form = $this->contentForm;
        $form->bind($content);
        $form->get("submit")->setValue($label);
        $this->getView()->form = $form;

        return $this->form($form, $content);
    }

    /**
     * @param ContentForm $form
     * @param Content $content
     */
    private function form(ContentForm $form, Content $content)
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setInputFilter($form->getInputFilter());
            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            $form->setData($data);
            if ($form->isValid()) {
                $formData = $form->getData();
                $userData = $this->UserData();

                if ($userData->checkIdentity(false, $this->translate("ERROR_AUTHORIZATION"))) {
                    $name = $userData->getIdentity()->name." ".$userData->getIdentity()->surname;
                } else {
                    $name = "Admin";
                }

                /*
                 * We only need the name. All images ar stored in the same folder, based on the month and year
                 */
                $formData->setPreview($formData->getPreview()["name"]);
                $formData->setAuthor($name);
                $this->getTable("ContentTable")->saveContent($content);
                $this->setLayoutMessages("&laquo;".$content->getTitle()."&raquo; ".$this->translate("SAVE_SUCCESS"), "success");
            } else {
                $this->setLayoutMessages($form->getMessages(), "error");
            }
        }
    }

    /**
     * @return JsonModel
     */
    protected function uploadAction()
    {
        $request = $this->getRequest();
        $data = [];

        if ($request->isXmlHttpRequest()) {
            $data = $this->prepareImages();
        }

        return new JsonModel($data);
    }

    /**
     * Deleted image with from a given src.
     *
     * @method deleteimageAction
     *
     * @return bool
     */
    protected function deleteimageAction()
    {
        $request = $this->getRequest();
        $status = false;

        if ($request->isPost()) {
            $data = $request->getPost()->toArray();

            if ($request->isXmlHttpRequest()) {
                if (is_file("public".$data["img"])) {
                    unlink("public".$data["img"]);
                    $status = true;
                }
            }
        }
        return $status;
    }

    /**
     * Get all files from all folders and list them in the gallery
     * getcwd() is there to make the work with images path easier.
     *
     * @return JsonModel
     */
    protected function filesAction()
    {
        chdir(getcwd()."/public/");
        if (!is_dir('userfiles/'.date("Y_M").'/images/')) {
            mkdir('userfiles/'.date("Y_M").'/images/', 0750, true);
        }
        $this->getView()->setTerminal(true);
        $dir = new \RecursiveDirectoryIterator('userfiles/', \FilesystemIterator::SKIP_DOTS);
        $it  = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
        $it->setMaxDepth(50);
        $files = [];
        $i = 0;
        foreach ($it as $file) {
            if ($file->isFile()) {
                $files[$i]["filelink"] = DIRECTORY_SEPARATOR.$file->getPath().DIRECTORY_SEPARATOR.$file->getFilename();
                $files[$i]["filename"] = $file->getFilename();
                $i++;
            }
        }
        chdir(dirname(getcwd()));
        $model = new JsonModel();
        $model->setVariables(["files" => $files]);
        return $model;
    }

    /**
     * Upload all images async.
     *
     * @return array
     */
    private function prepareImages()
    {
        $adapter = new Http();
        $size = new Size(['min'=>'10kB', 'max'=>'5MB', 'useByteString' => true]);
        $extension = new Extension(['jpg', 'gif', 'png', 'jpeg', 'bmp', 'webp', 'svg'], true);

        $adapter->setValidators([$size, new IsImage(), $extension]);

        if (!is_dir('public/userfiles/'.date("Y_M").'/images/')) {
            mkdir('public/userfiles/'.date("Y_M").'/images/', 0750, true);
        }

        $adapter->setDestination('public/userfiles/'.date("Y_M").'/images/');
        return $this->uploadFiles($adapter);
    }

    /**
     * @param  Http $adapter
     *
     * @return array
     */
    private function uploadFiles(Http $adapter)
    {
        $uploadStatus = [];

        foreach ($adapter->getFileInfo() as $key => $file) {
            if ($key != "preview") {
                if (!$adapter->isValid($file["name"])) {
                    foreach ($adapter->getMessages() as $key => $msg) {
                        $uploadStatus["errorFiles"][] = $file["name"]." ".strtolower($msg);
                    }
                }

                // @codeCoverageIgnoreStart
                $adapter->receive($file["name"]);
                if (!$adapter->isReceived($file["name"]) && $adapter->isUploaded($file["name"])) {
                    $uploadStatus["errorFiles"][] = $file["name"]." was not uploaded";
                } else {
                    $uploadStatus["successFiles"][] = $file["name"]." was successfully uploaded";
                }
                // @codeCoverageIgnoreEnd
            }
        }
        return $uploadStatus;
    }
}
