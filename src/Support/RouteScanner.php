<?php
namespace _34ml\ApiTester\Support;

use Illuminate\Support\Facades\Route;

class RouteScanner
{
    public static function getApiRoutes(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            $methods = $route->methods();

            // Ignore non-GET routes for now
            if (!in_array('GET', $methods)) {
                continue;
            }

            if (str_starts_with($uri, 'api/')) {
                $routes[] = [
                    'uri' => $uri,
                    'method' => 'GET',
                ];
            }
        }

        return $routes;
    }
}