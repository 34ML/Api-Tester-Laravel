<?php

use _34ml\ApiTester\http\Helpers\JsonSchemaValidator;

/**
 * Example API Test
 * 
 * This file demonstrates how to use the Laravel API Tester package
 * to validate API responses against JSON schemas.
 */

it('validates API response structure', function () {
    // Make a request to your API endpoint
    $response = $this->getJson('/api/v1/example');

    // Assert the response is successful
    $response->assertOk();

    // Extract the data from the response
    // The response might be wrapped in a 'data' key or be direct
    $data = $response->json('data') ?? $response->json();
    
    // Basic validation
    expect($data)->toBeArray();
    expect($data)->not->toBeEmpty();
    
    // If it's an array of items, validate the first item
    if (is_array($data) && count($data) > 0) {
        $first_item = $data[0];
        expect($first_item)->toBeArray();
        
        // Validate required fields exist
        expect($first_item)->toHaveKeys(['id']);
    }
});

it('validates against JSON schema', function () {
    $response = $this->getJson('/api/v1/example');
    $response->assertOk();

    $data = $response->json('data') ?? $response->json();
    
    // Validate against the generated JSON schema
    // Make sure the schema file exists at this path
    $schema_path = base_path('tests/Schemas/GET_ExampleTest.json');
    
    if (file_exists($schema_path)) {
        JsonSchemaValidator::assert($data, $schema_path);
    } else {
        $this->markTestSkipped('Schema file not found. Run php artisan api:generate-tests first.');
    }
});

it('handles different response structures', function () {
    $response = $this->getJson('/api/v1/example');
    $response->assertOk();

    $response_data = $response->json();
    
    // Handle different response structures
    if (isset($response_data['data'])) {
        // Response wrapped in 'data' key
        $data = $response_data['data'];
        $this->assertIsArray($data);
    } elseif (isset($response_data['items'])) {
        // Response wrapped in 'items' key
        $data = $response_data['items'];
        $this->assertIsArray($data);
    } else {
        // Direct response
        $data = $response_data;
        $this->assertIsArray($data);
    }
    
    // Validate the data structure
    $this->assertNotEmpty($data);
    
    if (count($data) > 0) {
        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('id', $data[0]);
    }
}); 