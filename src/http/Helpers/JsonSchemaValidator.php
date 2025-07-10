<?php
namespace _34ml\ApiTester\http\Helpers;

use JsonSchema\Validator;

class JsonSchemaValidator
{
    public static function assert(array $actualResponse, string $schemaPath): void
    {
        $schema = json_decode(file_get_contents($schemaPath));
        $validator = new Validator;
        $validator->validate($actualResponse, $schema);

        if (!$validator->isValid()) {
            $errors = array_map(fn ($e) => "{$e['property']}: {$e['message']}", $validator->getErrors());
            throw new \Exception("Schema validation failed:\n" . implode("\n", $errors));
        }
    }
}
