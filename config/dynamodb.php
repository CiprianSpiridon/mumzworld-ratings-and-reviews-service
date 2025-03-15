<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default DynamoDb Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the DynamoDb connections below you wish
    | to use as your default connection for all DynamoDb work.
    */

    'default' => env('DYNAMODB_CONNECTION', 'aws'),

    /*
    |--------------------------------------------------------------------------
    | DynamoDb Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the DynamoDb connections setup for your application.
    |
    | Most of the connection's config will be fed directly to AwsClient
    | constructor http://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.AwsClient.html#___construct
    */

    'connections' => [
        'aws' => [
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'version' => 'latest',
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],
        'aws_iam_role' => [
            'region' => env('DYNAMODB_REGION'),
            'debug' => env('DYNAMODB_DEBUG'),
        ],
        'local' => [
            'credentials' => [
                'key' => 'dynamodblocal',
                'secret' => 'secret',
            ],
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            // see http://docs.aws.amazon.com/amazondynamodb/latest/developerguide/Tools.DynamoDBLocal.html
            'endpoint' => env('DYNAMODB_LOCAL_ENDPOINT'),
            'debug' => true,
        ],
        'test' => [
            'credentials' => [
                'key' => 'dynamodblocal',
                'secret' => 'secret',
            ],
            'region' => 'test',
            'endpoint' => env('DYNAMODB_LOCAL_ENDPOINT'),
            'debug' => true,
        ],
    ],
];
