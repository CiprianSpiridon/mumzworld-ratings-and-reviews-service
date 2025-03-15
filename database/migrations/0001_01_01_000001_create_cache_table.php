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

        // Create cache table
        $client->createTable([
            'TableName' => 'cache',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'key',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'key',
                    'KeyType' => 'HASH'
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => 'cache'
        ]);

        // Create cache_locks table
        $client->createTable([
            'TableName' => 'cache_locks',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'key',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'key',
                    'KeyType' => 'HASH'
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => 'cache_locks'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();

        // Delete cache table
        $client->deleteTable([
            'TableName' => 'cache'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'cache'
        ]);

        // Delete cache_locks table
        $client->deleteTable([
            'TableName' => 'cache_locks'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'cache_locks'
        ]);
    }
};
