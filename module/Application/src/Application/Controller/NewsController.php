<?php
namespace Application\Controller;

use Application\Controller\IndexController;
use Custom\Plugins\Functions;

class NewsController extends IndexController
{
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    {
        parent::onDispatch($e);
    }

    public function newsAction()
    {
        $post = (string) $this->getParam("post", null);
        if(!empty($post))
        {
            try
            {
                $new = $this->getTable("Content")->fetchList(false, "type='1' AND menu='0' AND title='{$post}' AND language='".$this->langTranslation."'", "date DESC");
                if (!$new->current())
                {
                    $post = str_replace(array("-","_"),array(" ","/"), $post);
                    $new = $this->getTable("Content")->fetchList(false, "title LIKE '%{$post}%' AND menu='0' AND language='".$this->langTranslation."'", "date DESC");
                }

                if (count($new) === 0)
                {
                    throw new \Exception($this->translation->NEWS_NOT_FOUND);
                }

                $this->view->new = $new->current();
                $this->setMetaTags($new, "news");
            }
            catch(\Exception $e)
            {
                throw new \Exception($this->translation->NEWS_NOT_FOUND);
            }
        }
        else
        {
            $news = $this->getTable("content")->fetchList(true, "type='1' AND menu='0' AND language='".$this->langTranslation."'", "date DESC");
            $news->setCurrentPageNumber((int)$this->params('page', 1));
            $news->setItemCountPerPage(10);
            $this->view->news = $news;
        }
        return $this->view;
    }
}
?>