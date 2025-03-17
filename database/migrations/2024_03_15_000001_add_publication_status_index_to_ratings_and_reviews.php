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

        // First, update the table to add the new attribute definition
        $client->updateTable([
            'TableName' => 'ratings_and_reviews',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'publication_status',
                    'AttributeType' => 'S'
                ]
            ],
            'GlobalSecondaryIndexUpdates' => [
                [
                    'Create' => [
                        'IndexName' => 'publication_status-index',
                        'KeySchema' => [
                            [
                                'AttributeName' => 'publication_status',
                                'KeyType' => 'HASH'
                            ]
                        ],
                        'Projection' => [
                            'ProjectionType' => 'ALL'
                        ]
                    ]
                ]
            ]
        ]);

        // Wait until the table update is complete
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

        // Remove the GSI
        $client->updateTable([
            'TableName' => 'ratings_and_reviews',
            'GlobalSecondaryIndexUpdates' => [
                [
                    'Delete' => [
                        'IndexName' => 'publication_status-index'
                    ]
                ]
            ]
        ]);

        // Wait until the table update is complete
        $client->waitUntil('TableExists', [
            'TableName' => 'ratings_and_reviews'
        ]);
    }
};
