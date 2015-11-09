<?php

use Aura\Router\RouterFactory;

class Ethna_AuraRouter_Subscriber_ResolveActionNameSubscriber
    extends Ethna_EventSubscriber
{
    protected static $router;

    public static function getSubscribedEvents()
    {
        return array(
            Ethna_Events::CONTROLLER_RESOLVE_ACTION => array(
                array('resolveAction', Ethna_Events::NORMAL_PRIOLITY)
            ));
    }

    public static function clear()
    {
        self::$router = null;
    }

    /**
     * Aura\Routerを使ったActionのResolver
     *
     * URLHandlerが難しい、という人向け
     *
     * @param Ethna_Event_Forward $event
     */
    public static function resolveAction(Ethna_Event_ResolveActionName $event)
    {
        $router = self::$router;
        if (!$router) {
            $router_factory = new RouterFactory;
            self::$router = $router = $router_factory->newInstance();
            $base_dir = $event->getController()->getDirectory("app");

            // MEMO(chobie): scopeを限定させてるだけ
            $path = $base_dir . DIRECTORY_SEPARATOR . "routes.php";
            if (is_file($path)) {
                call_user_func(function(Aura\Router\Router $router, $path) {
                    require $path;
                }, $router, $path);
            }

            $path = $base_dir . DIRECTORY_SEPARATOR . "routes_overrides.php";
            if (is_file($path)) {
                call_user_func(function(Aura\Router\Router $router, $path) {
                    require $path;
                }, $router, $path);
            }
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $route = $router->match($path, $_SERVER);

        if ($route) {
            $ignore = array("controller", "action");
            foreach ($route->params as $key => $value) {
                if (in_array($key, $ignore)) {
                    continue;
                }
                if ($_SERVER['REQUEST_METHOD'] == "POST") {
                    $_POST[$key] = $value;
                } else if ($_SERVER['REQUEST_METHOD'] == "GET") {
                    $_GET[$key] = $value;
                }
            }

            $event->setActionName($route->params['action']);
        } else {
            $event->setActionName($event->getFallbackActionName());
        }
        $event->stopPropagation();
    }
}

