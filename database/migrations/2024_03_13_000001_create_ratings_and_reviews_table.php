<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use BaoPham\DynamoDb\DynamoDbClientService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();
        $tableName = env('DYNAMODB_TABLE', 'ratings_and_reviews');

        $client->createTable([
            'TableName' => $tableName,
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'review_id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'user_id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'product_id',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'review_id',
                    'KeyType' => 'HASH'
                ]
            ],
            'GlobalSecondaryIndexes' => [
                [
                    'IndexName' => 'user_id-index',
                    'KeySchema' => [
                        [
                            'AttributeName' => 'user_id',
                            'KeyType' => 'HASH'
                        ]
                    ],
                    'Projection' => [
                        'ProjectionType' => 'ALL'
                    ]
                ],
                [
                    'IndexName' => 'product_id-index',
                    'KeySchema' => [
                        [
                            'AttributeName' => 'product_id',
                            'KeyType' => 'HASH'
                        ]
                    ],
                    'Projection' => [
                        'ProjectionType' => 'ALL'
                    ]
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => $tableName
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();
        $tableName = env('DYNAMODB_TABLE', 'ratings_and_reviews');

        $client->deleteTable([
            'TableName' => $tableName
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => $tableName
        ]);
    }
};
