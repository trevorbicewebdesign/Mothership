<?php

/**
 * @package     Mothership.Plugin
 * @subpackage  Webservices.mothership
 *
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace TrevorBice\Plugin\WebServices\Mothership\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

\defined('_JEXEC') or die;

/**
 * Web Services adapter for com_mothership.
 *
 * Read-only on purpose. Only GET routes are registered, so the write methods
 * ApiController inherits are unreachable. Adding writes is a matter of adding
 * routes here, not of changing the controllers.
 */
final class Mothership extends CMSPlugin implements SubscriberInterface
{
    /**
     * The resources served under v1/mothership/. Each one is a controller in
     * api/src/Controller and a view in api/src/View of the same name.
     *
     * @var string[]
     */
    private const RESOURCES = ['clients', 'accounts', 'projects', 'domains', 'proposals', 'invoices', 'payments'];

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeApiRoute' => 'onBeforeApiRoute',
        ];
    }

    /**
     * Register com_mothership's API routes.
     *
     * @param   BeforeApiRouteEvent  $event  The event object
     *
     * @return  void
     */
    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        // 'public' => false requires an authenticated user (Bearer token).
        $defaults = [
            'component' => 'com_mothership',
            'public'    => false,
        ];

        $routes = [];

        foreach (self::RESOURCES as $resource) {
            $routes[] = new Route(['GET'], 'v1/mothership/' . $resource, $resource . '.displayList', [], $defaults);
            $routes[] = new Route(['GET'], 'v1/mothership/' . $resource . '/:id', $resource . '.displayItem', ['id' => '(\d+)'], $defaults);
        }

        $event->getRouter()->addRoutes($routes);
    }
}
