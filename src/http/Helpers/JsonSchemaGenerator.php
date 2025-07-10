<?php
namespace _34ml\ApiTester\http\Helpers;


use Swaggest\JsonSchema\Schema;

class JsonSchemaGenerator
{
    public static function fromResponse(array $responseData): string
    {
        $schema = Schema::import($responseData);
        //It analyzes the JSON array and converts it into a schema file.
        return json_encode($schema->jsonSerialize(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}