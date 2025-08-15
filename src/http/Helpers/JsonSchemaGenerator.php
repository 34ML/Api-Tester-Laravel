<?php
namespace _34ml\ApiTester\http\Helpers;

class JsonSchemaGenerator
{
    public static function fromResponse(array|object $data): string
    {
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        $schema = self::generateSchema($data);

        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        //JSON_PRETTY_PRINT → multiline readable format.
//JSON_UNESCAPED_SLASHES → avoids escaping slashes (e.g. no \/).


    }

    protected static function generateSchema($data): array
    {
        if (is_array($data)) {
            if (array_is_list($data)) {
                $items = count($data) > 0 ? self::generateSchema($data[0]) : ['type' => 'object'];

                return [
                    'type' => 'array',
                    'items' => $items,
                ];
            }

            $properties = [];
$required = [];
            foreach ($data as $key => $value) {
                $properties[$key] = self::generateSchema($value);
                $required[] = $key;
            }

            return [
                'type' => 'object',
                'properties' => $properties,
                'required' => $required,
            ];
        }

        return ['type' => self::mapType($data)];
    }

    protected static function mapType($value): string
    {
        //match is a PHP 8+ alternative to switch, more concise.
        //gettype() returns the PHP internal type of a value.

        return match (gettype($value)) {
            'string'  => 'string',
            'integer' => 'integer',
            'double'  => 'number',
            'boolean' => 'boolean',
            'array'   => 'array',
            'object'  => 'object',
            default   => 'string',
        };
    }
}
