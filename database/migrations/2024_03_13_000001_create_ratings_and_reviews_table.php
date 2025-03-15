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

        $client->createTable([
            'TableName' => 'ratings_and_reviews',
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
            'TableName' => 'ratings_and_reviews'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();

        $client->deleteTable([
            'TableName' => 'ratings_and_reviews'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'ratings_and_reviews'
        ]);
    }
};
