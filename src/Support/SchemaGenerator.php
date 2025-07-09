<?php
namespace _34ml\ApiTester\Support;

use SwaggyMacro\ArrayToJsonSchema\ArrayToJsonSchema;

class SchemaGenerator
{
    public static function generateSchema(array $responseData): array
    {
        $schema = new ArrayToJsonSchema();
        return $schema->toSchema($responseData);
    }
}
