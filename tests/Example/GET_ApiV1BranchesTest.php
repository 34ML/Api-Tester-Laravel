<?php

use _34ml\ApiTester\http\Helpers\JsonSchemaValidator;

it('validates GET /api/v1/branches against JSON schema', function () {
    $response = $this->getJson('/api/v1/branches');

    $response->assertOk();

    // Extract the actual data array from the response
    // The response might be wrapped in a 'data' key or similar structure
    $data = $response->json('data');
    
    // If the response structure is different, adjust this accordingly
    // For example, if the response is directly an array:
    // $data = $response->json();
    
    // Ensure we have an array of objects (branches)
    expect($data)->toBeArray();
    expect($data)->not->toBeEmpty();
    expect($data[0])->toBeArray();
    
    // Validate against the JSON schema
    JsonSchemaValidator::assert($data, base_path('tests/JsonSchemas/GET_ApiV1BranchesTest.json'));
});

it('validates individual branch structure', function () {
    $response = $this->getJson('/api/v1/branches');

    $response->assertOk();

    $data = $response->json('data');
    
    // Validate the first branch has all required fields
    $first_branch = $data[0];
    
    expect($first_branch)->toHaveKeys([
        'id',
        'title', 
        'opening_and_closing_days_and_hours',
        'location_text',
        'lng',
        'lat',
        'mobile_number'
    ]);
    
    // Validate data types
    expect($first_branch['id'])->toBeInt();
    expect($first_branch['title'])->toBeString();
    expect($first_branch['opening_and_closing_days_and_hours'])->toBeString();
    expect($first_branch['location_text'])->toBeString();
    expect($first_branch['lng'])->toBeString();
    expect($first_branch['lat'])->toBeString();
    expect($first_branch['mobile_number'])->toBeString();
}); 