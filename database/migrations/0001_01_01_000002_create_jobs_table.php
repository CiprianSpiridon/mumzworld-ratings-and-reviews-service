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

        // Create jobs table
        $client->createTable([
            'TableName' => 'jobs',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'queue',
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
                    'IndexName' => 'queue-index',
                    'KeySchema' => [
                        [
                            'AttributeName' => 'queue',
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
            'TableName' => 'jobs'
        ]);

        // Create job_batches table
        $client->createTable([
            'TableName' => 'job_batches',
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
            'TableName' => 'job_batches'
        ]);

        // Create failed_jobs table
        $client->createTable([
            'TableName' => 'failed_jobs',
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'uuid',
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
                    'IndexName' => 'uuid-index',
                    'KeySchema' => [
                        [
                            'AttributeName' => 'uuid',
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
            'TableName' => 'failed_jobs'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $client = app(DynamoDbClientService::class)->getClient();

        // Delete jobs table
        $client->deleteTable([
            'TableName' => 'jobs'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'jobs'
        ]);

        // Delete job_batches table
        $client->deleteTable([
            'TableName' => 'job_batches'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'job_batches'
        ]);

        // Delete failed_jobs table
        $client->deleteTable([
            'TableName' => 'failed_jobs'
        ]);

        // Wait until the table is deleted
        $client->waitUntil('TableNotExists', [
            'TableName' => 'failed_jobs'
        ]);
    }
};
