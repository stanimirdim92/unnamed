<?php

/**
 * @copyright  2015 (c) Stanimir Dimitrov.
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * @version    0.0.17
 *
 * @link       TBA
 */

namespace Application\Controller\Plugin\Factory;

use Application\Controller\Plugin\Functions;
use Zend\Mvc\Controller\PluginManager;

class FunctionsFactory
{
    /**
     * @{inheritDoc}
     */
    public function __invoke(PluginManager $pluginManager)
    {
        $serviceLocator = $pluginManager->getServiceLocator();

        $adapter = $serviceLocator->get('SD\Adapter');

        $plugin = new Functions($adapter);

        return $plugin;
    }
}
