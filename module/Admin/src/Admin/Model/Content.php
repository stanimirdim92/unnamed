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
 * @version    0.0.4
 * @link       TBA
 */

namespace Admin\Model;

class Content
{
    /**
     * @var Int $id
     */
    private $id = 0;

    /**
     * @var Int $id
     */
    private $menu = 0;

    /**
     * @var string $title
     */
    private $title = null;

    /**
     * @var string $preview
     */
    private $preview = null;

    /**
     * @var string $text
     */
    private $text = null;

    /**
     * @var Int $id
     */
    private $menuOrder = 0;

    /**
     * @var Int $id
     */
    private $type = 0;

    /**
     * @var string $date
     */
    private $date = "0000-00-00 00:00:00";

    /**
     * @var Int $language
     */
    private $language = 1;

    /**
     * @var string $titleLink
     */
    private $titleLink = null;

    /**
     * @param array $data
     * @return void
     */
    public function exchangeArray(array $data = [])
    {
        $this->id = (isset($data['id'])) ? $data['id'] : $this->getId();
        $this->menu = (isset($data['menu'])) ? $data['menu'] : $this->getMenu();
        $this->title = (isset($data['title'])) ? $data['title'] : $this->getTitle();
        $this->preview = (isset($data['preview'])) ? $data['preview'] : $this->getPreview();
        $this->text = (isset($data['text'])) ? $data['text'] : $this->getText();
        $this->menuOrder = (isset($data['menuOrder'])) ? $data['menuOrder'] : $this->getMenuOrder();
        $this->type = (isset($data['type'])) ? $data['type'] : $this->getType();
        $this->date = (isset($data['date'])) ? $data['date'] : $this->getDate();
        $this->language = (isset($data['language'])) ? $data['language'] : $this->getLanguage();
        $this->titleLink = (isset($data['titleLink'])) ? $data['titleLink'] :  $this->getTitleLink();
    }

    /**
     * Used into form binding
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    /**
     * constructor
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->exchangeArray($options);
    }

    /**
     * Get id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     * @param int
     */
    public function setId($id = 0)
    {
        $this->id = $id;
    }

    /**
     * Set Menu
     * @param int $menu
     */
    public function setMenu($menu = 0)
    {
        $this->menu = $menu;
    }

    /**
     * Get menu
     * @return int
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * Set title
     * @param null $title
     */
    public function setTitle($title = null)
    {
        $this->title = $title;
    }

    /**
     * Get title
     * @return String
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set titleLink
     * @param null $titleLink
     */
    public function setTitleLink($titleLink = null)
    {
        $this->titleLink = $titleLink;
    }

    /**
     * Get titleLink
     * @return String
     */
    public function getTitleLink()
    {
        return $this->titleLink;
    }

    /**
     * Set preview
     * @param String $preview
     */
    public function setPreview($preview = null)
    {
        $this->preview = $preview;
    }

    /**
     * Get preview
     * @return String
     */
    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * Set text
     * @param String $text
     */
    public function setText($text = null)
    {
        $this->text = $text;
    }

    /**
     * Get text
     * @return String
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set order
     * @param int $menuOrder
     */
    public function setMenuOrder($menuOrder = 0)
    {
        $this->menuOrder = $menuOrder;
    }

    /**
     * Get menuOrder
     * @return int
     */
    public function getMenuOrder()
    {
        return $this->menuOrder;
    }

    /**
     * Set type
     * @param int $type
     */
    public function setType($type = 0)
    {
        $this->type = $type;
    }

    /**
     * Get type
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set date
     * @param String $date
     */
    public function setDate($date = "0000-00-00 00:00:00")
    {
        $this->date = $date;
    }

    /**
     * Get date
     * @return String
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set Language
     * @param int $language
     */
    public function setLanguage($language = 1)
    {
        $this->language = $language;
    }

    /**
     * Get language
     * @return int
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * magic getter
     */
    public function __get($property)
    {
        return (property_exists($this, $property) ? $this->{$property} : null);
    }

    /**
     * magic setter
     */
    public function __set($property, $value)
    {
        return (property_exists($this, $property) ? $this->{$property} = $value : null);
    }

    /**
     * magic property exists (isset) checker
     */
    public function __isset($property)
    {
        return (property_exists($this, $property) ? isset($this->{$property}) : null);
    }

    /**
     * Serialize object or return it as an array
     *
     * @param  array $skip Remove the unnecessary objects from the array
     * @param  bool $serializable Should the function return a serialized object
     * @return array|string
     */
    public function getProperties(array $skip = [], $serializable = false)
    {
        $returnValue = [];
        $data = get_class_vars(get_class($this));
        foreach ($data as $key => $value) {
            if (!in_array($key, $skip)) {
                $returnValue[$key] = $this->{$key};
            }
        }
        if ((bool) $serializable === true) {
            return serialize($returnValue);
        }
        return $returnValue;
    }

    /**
     * This method is a copy constructor that will return a copy object (except for the id field)
     * Note that this method will not save the object
     */
    public function getCopy()
    {
        $clone = new self();
        $clone->setMenu($this->getMenu());
        $clone->setTitle($this->getTitle());
        $clone->setPreview($this->getPreview());
        $clone->setText($this->getText());
        $clone->setMenuOrder($this->getMenuOrder());
        $clone->setType($this->getType());
        $clone->setDate($this->getDate());
        $clone->setLanguage($this->getLanguage());
        $clone->setTitleLink($this->getTitleLink());
        return $clone;
    }
}
