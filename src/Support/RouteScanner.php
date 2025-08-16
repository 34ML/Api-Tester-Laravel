<?php

namespace _34ml\ApiTester\Support;

use Illuminate\Support\Facades\Route;

class RouteScanner
{
    public static function getApiRoutes(): array
    {
        $routes = [];
        $supported_methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            $methods = $route->methods();

            // Only process API routes
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            // Skip routes that don't have any supported HTTP methods
            $route_methods = array_intersect($methods, $supported_methods);
            if (empty($route_methods)) {
                continue;
            }

            // Create a route entry for each supported method
            foreach ($route_methods as $method) {
                // Skip HEAD and OPTIONS methods
                if (in_array($method, ['HEAD', 'OPTIONS'])) {
                    continue;
                }

                $routes[] = [
                    'uri' => $uri,
                    'method' => $method,
                    'name' => $route->getName(),
                    'middleware' => $route->middleware(),
                ];
            }
        }

        return $routes;
    }

    public static function getApiRoutesByMethod(string $method): array
    {
        $routes = self::getApiRoutes();
        return array_filter($routes, fn($route) => $route['method'] === strtoupper($method));
    }

    public static function getApiRoutesByUri(string $uri): array
    {
        $routes = self::getApiRoutes();
        return array_filter($routes, fn($route) => $route['uri'] === $uri);
    }
}