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

        // Create users table
        $client->createTable([
            'TableName' => 'users',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'id',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'id',
                    'KeyType' => 'HASH'
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => 'users'
        ]);

        // Create password_reset_tokens table
        $client->createTable([
            'TableName' => 'password_reset_tokens',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'email',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'email',
                    'KeyType' => 'HASH'
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => 'password_reset_tokens'
        ]);

        // Create sessions table
        $client->createTable([
            'TableName' => 'sessions',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'user_id',
                    'AttributeType' => 'S'
                ]
            ],
            'KeySchema' => [
                [
                    'AttributeName' => 'id',
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
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST'
        ]);

        // Wait until the table is created
        $client->waitUntil('TableExists', [
            'TableName' => 'sessions'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();

        // Delete users table
        $client->deleteTable([
            'TableName' => 'users'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'users'
        ]);

        // Delete password_reset_tokens table
        $client->deleteTable([
            'TableName' => 'password_reset_tokens'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'password_reset_tokens'
        ]);

        // Delete sessions table
        $client->deleteTable([
            'TableName' => 'sessions'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'sessions'
        ]);
    }
};
