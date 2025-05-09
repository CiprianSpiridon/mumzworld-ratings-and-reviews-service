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
        $tableName = 'ratings_and_review_statistics';

        $client->createTable([
            'TableName' => $tableName,
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'product_id',
                    'AttributeType' => 'S'
                ],
                // Add other attribute definitions here if they become part of a GSI key later
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'product_id',
                    'KeyType' => 'HASH'
                ]
            ],
            // Define GlobalSecondaryIndexes here if needed in the future
            // 'GlobalSecondaryIndexes' => [
            //     [
            //         'IndexName' => 'some_attribute-index',
            //         'KeySchema' => [
            //             [
            //                 'AttributeName' => 'some_attribute',
            //                 'KeyType' => 'HASH'
            //             ]
            //         ],
            //         'Projection' => [
            //             'ProjectionType' => 'ALL' // Or KEYS_ONLY, INCLUDE
            //         ]
            //     ]
            // ],
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
        $tableName = 'ratings_and_review_statistics';

        $client->deleteTable([
            'TableName' => $tableName
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => $tableName
        ]);
    }
}; 