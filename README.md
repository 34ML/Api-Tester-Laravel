# Laravel API Tester

A comprehensive Laravel package to auto-generate Pest-based tests for API endpoints using JSON schema assertions. This package makes it easy to test and validate your API responses automatically.

## Features

- 🚀 **Auto-generate tests** for all API endpoints
- 🔍 **JSON schema validation** for response structure
- 📊 **Multiple HTTP methods** support (GET, POST, PUT, PATCH, DELETE)
- 🧪 **Pest-based testing** with comprehensive assertions
- ⚙️ **Configurable** settings for different environments
- 🔐 **Authentication support** for protected endpoints
- 📝 **Smart route detection** and filtering

## Installation

```bash
composer require 34ml/laravel-api-tester
```

## Quick Start

### 1. Generate Tests for All API Endpoints

```bash
php artisan api:generate-tests
```

This will:
- Scan all your API routes
- Generate JSON schemas from actual responses
- Create Pest test files for each endpoint
- Store schemas in `tests/Schemas/`
- Store tests in `tests/Feature/ApiTester/`

### 2. Test a Specific Endpoint

```bash
php artisan api:test api/v1/branches
```

### 3. List Available API Routes

```bash
php artisan api:list
```

## Commands

### `api:generate-tests`

Generates tests for all API endpoints.

```bash
# Generate tests for all endpoints
php artisan api:generate-tests

# Generate tests for specific HTTP methods
php artisan api:generate-tests --method=GET --method=POST

# Generate tests for specific URIs
php artisan api:generate-tests --uri=api/v1/branches

# Force overwrite existing test files
php artisan api:generate-tests --force
```
php artisan api:generate-tests --force --uri=api/v1/cities --method=GET

### `api:test`

Test a specific API endpoint and optionally validate against a schema.

```bash
# Test GET endpoint
php artisan api:test api/v1/branches

# Test with different HTTP method
php artisan api:test api/v1/branches --method=POST

# Test and validate against schema
php artisan api:test api/v1/branches --schema=tests/Schemas/GET_BranchesTest.json
```

### `api:list`

List all available API routes with filtering options.

```bash
# List all routes
php artisan api:list

# Filter by HTTP method
php artisan api:list --method=GET

# Filter by URI pattern
php artisan api:list --uri=branches

# Output in different formats
php artisan api:list --format=json
php artisan api:list --format=csv
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=config
```

### Configuration Options

```php
// config/api-tester.php
return [
    // Schema storage path
    'schema_path' => base_path('tests/Schemas'),
    
    // Test storage path
    'test_path' => base_path('tests/Feature/ApiTester'),
    
    // Default HTTP headers
    'default_headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
    
    // Authentication settings
    'auth' => [
        'enabled' => false,
        'type' => 'bearer',
        'token' => env('API_TEST_TOKEN'),
        'username' => env('API_TEST_USERNAME'),
        'password' => env('API_TEST_PASSWORD'),
    ],
    
    // Test generation options
    'test_generation' => [
        'include_structure_tests' => true,
        'include_schema_tests' => true,
        'overwrite_existing' => false,
    ],
];
```

## Generated Test Structure

The package generates comprehensive tests for each API endpoint:

```php
<?php

use _34ml\ApiTester\http\Helpers\JsonSchemaValidator;

it('validates GET /api/v1/branches against JSON schema', function () {
    $response = $this->getJson('/api/v1/branches');

    $response->assertOk();

    $data = $response->json('data') ?? $response->json();
    
    // Ensure we have valid data to validate
    expect($data)->not->toBeEmpty();
    expect($data)->toBeArray();
    
    // Validate against the generated JSON schema
    JsonSchemaValidator::assert($data, base_path('tests/Schemas/GET_BranchesTest.json'));
});

it('validates response structure for GET /api/v1/branches', function () {
    $response = $this->getJson('/api/v1/branches');
    
    $response->assertOk();
    
    $data = $response->json('data') ?? $response->json();
    
    // Basic structure validation
    expect($data)->toBeArray();
    expect($data)->not->toBeEmpty();
    
    if (count($data) > 0) {
        $first_item = $data[0];
        expect($first_item)->toBeArray();
        
        // Validate required fields exist
        expect($first_item)->toHaveKeys(['id']);
    }
});
```

## JSON Schema Validation

The package automatically generates JSON schemas from your API responses and validates them in tests:

```json
{
    "type": "array",
    "items": {
        "type": "object",
        "properties": {
            "id": {"type": "integer"},
            "title": {"type": "string"},
            "location_text": {"type": "string"}
        },
        "required": ["id", "title", "location_text"]
    }
}
```

## Troubleshooting

### Common Issues

#### 1. "Array value found, but an object is required" Error

This usually means your JSON schema doesn't match your API response structure.

**Solution**: Regenerate the schema using the command:
```bash
php artisan api:generate-tests --force
```

#### 2. No Routes Found

**Check**: Ensure your routes are properly defined in `routes/api.php`

**Solution**: Run `php artisan route:list` to verify routes exist

#### 3. Authentication Errors

**Solution**: Configure authentication in your `.env` file:
```env
API_TEST_TOKEN=your_token_here
API_TEST_USERNAME=your_username
API_TEST_PASSWORD=your_password
```

#### 4. Schema Validation Fails

**Debug**: Use the `api:test` command to see the actual response:
```bash
php artisan api:test api/v1/branches
```

**Solution**: Check that your API returns the expected data structure

### Debug Mode

Enable debug mode to see detailed information:

```bash
php artisan api:generate-tests --verbose
```

## Best Practices

### 1. Test Structure

- Always test both schema validation and basic structure
- Use descriptive test names
- Include edge case testing for empty responses

### 2. Schema Management

- Regenerate schemas when API changes
- Version control your schemas
- Use the `--force` flag sparingly

### 3. Environment Setup

- Use separate test databases
- Configure proper APP_URL in your environment
- Set up authentication tokens for protected endpoints

### 4. Continuous Integration

- Run generated tests in your CI pipeline
- Validate schemas against staging/production APIs
- Monitor for API breaking changes

## Advanced Usage

### Custom Test Templates

You can customize the generated test content by modifying the command class or extending it.

### Multiple Environments

Use different configurations for different environments:

```bash
# Development
php artisan api:generate-tests

# Staging
APP_ENV=staging php artisan api:generate-tests

# Production (read-only)
APP_ENV=production php artisan api:list
```

### Integration with Existing Tests

The generated tests can be integrated with your existing test suite:

```bash
# Run only API tests
php artisan test --filter=ApiTester

# Run specific API test
php artisan test tests/Feature/ApiTester/GET_BranchesTest.php
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

If you encounter any issues:

1. Check the troubleshooting section above
2. Review the generated test files for errors
3. Use the debug commands to identify issues
4. Open an issue on GitHub with detailed information

---

**Happy API Testing! 🚀** 