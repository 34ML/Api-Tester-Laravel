<?php

namespace _34ml\ApiTester\Commands;

use Illuminate\Console\Command;
use _34ml\ApiTester\Support\RouteScanner;

class ListApiRoutesCommand extends Command
{
    protected $signature = 'api:list {--method= : Filter by HTTP method} {--uri= : Filter by URI pattern} {--format=table : Output format (table, json, csv)}';
    protected $description = 'List all available API routes for testing';

    public function handle()
    {
        $routes = RouteScanner::getApiRoutes();
        
        if (empty($routes)) {
            $this->warn('⚠️ No API routes found. Make sure your routes are properly defined.');
            return 1;
        }

        // Apply filters
        if ($method = $this->option('method')) {
            $routes = RouteScanner::getApiRoutesByMethod(strtoupper($method));
        }

        if ($uri = $this->option('uri')) {
            $routes = array_filter($routes, fn($route) => str_contains($route['uri'], $uri));
        }

        if (empty($routes)) {
            $this->warn('⚠️ No routes match the specified filters.');
            return 1;
        }

        $format = $this->option('format');
        
        match ($format) {
            'json' => $this->outputJson($routes),
            'csv' => $this->outputCsv($routes),
            default => $this->outputTable($routes),
        };

        $this->newLine();
        $this->info("Found " . count($routes) . " API route(s)");
        
        return 0;
    }

    protected function outputTable(array $routes): void
    {
        $headers = ['Method', 'URI', 'Name', 'Middleware'];
        
        $rows = array_map(function ($route) {
            return [
                $route['method'],
                '/' . $route['uri'],
                $route['name'] ?? '-',
                implode(', ', $route['middleware'] ?? []),
            ];
        }, $routes);

        $this->table($headers, $rows);
    }

    protected function outputJson(array $routes): void
    {
        $this->line(json_encode($routes, JSON_PRETTY_PRINT));
    }

    protected function outputCsv(array $routes): void
    {
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Method', 'URI', 'Name', 'Middleware']);
        
        // Data
        foreach ($routes as $route) {
            fputcsv($output, [
                $route['method'],
                '/' . $route['uri'],
                $route['name'] ?? '',
                implode(', ', $route['middleware'] ?? []),
            ]);
        }
        
        fclose($output);
    }
} 