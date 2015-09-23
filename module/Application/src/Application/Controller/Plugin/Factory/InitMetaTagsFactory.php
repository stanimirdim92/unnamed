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

namespace Application\Controller\Plugin\Factory;

use Application\Controller\Plugin\InitMetaTags;
use Zend\Mvc\Controller\PluginManager;

class InitMetaTagsFactory
{
    /**
     * @{inheritDoc}
     */
    public function __invoke(PluginManager $pluginManager)
    {
        /**
         * @var Zend\View\HelperPluginManager
         */
        $viewHelper = $pluginManager->getController()->getServiceLocator()->get('ViewHelperManager');

        /**
         * @var Zend\View\Helper\Placeholder\Container $placeholderContainer
         */
        $placeholderContainer = $viewHelper->get("placeholder")->getContainer("customHead");

        /**
         * @var Zend\View\Helper\HeadMeta $headMeta
         */
        $headMeta = $viewHelper->get("HeadMeta");

        /**
         * @var Zend\Http\PhpEnvironment\Request $request
         */
        $request = $pluginManager->getController()->getRequest();

        /**
         * @var InitMetaTags $plugin
         */
        $plugin = new InitMetaTags($placeholderContainer, $headMeta, $request);

        return $plugin;
    }
}
