<?php
namespace Admin\Form;
use Zend\Form\Form;
use Zend\Form\Element;
class LanguageSearchForm extends Form
{
    public function __construct($options = null)
    {
        parent::__construct("languagesearch");
        $elements = array();
        $elements[0] = new Element\Text('search');
        $elements[0]->setLabel('Search for language')
                ->setAttribute('size', 40);
        $elements[1] = new Element\Submit('submit');
        $elements[1]->setAttribute('id', 'searchbutton');
        $elements[1]->setLabel('');
        foreach($elements as $e)
        {
          $this->add($e);
        }
    }
}
