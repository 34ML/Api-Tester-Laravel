<?php

namespace _34ml\ApiTester\Support;

use Swaggest\JsonSchema\Schema;

class SchemaValidator
{
    public static function validate(array $data, array $schemaArray): bool
    {
        try {
            $schema = Schema::import($schemaArray);
            $schema->in(json_decode(json_encode($data)));
            return true;
        } catch (\Exception $e) {
            dump("❌ Schema validation failed: " . $e->getMessage());
            return false;
        }
    }
}
