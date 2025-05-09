<?php

namespace App\Models;

use BaoPham\DynamoDb\DynamoDbModel;

/**
 * Class RatingsAndReviewStatistics
 * 
 * Represents pre-calculated statistics for product ratings and reviews.
 * Data is stored in a DynamoDB table.
 */
class RatingsAndReviewStatistics extends DynamoDbModel
{
    /**
     * The table associated with the model in DynamoDB.
     *
     * @var string
     */
    protected $table = 'ratings_and_review_statistics';

    /**
     * The primary key for the model (Hash Key in DynamoDB).
     *
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     * DynamoDB does not use auto-incrementing keys.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'average_rating',
        'rating_count',
        'rating_distribution',
        'percentage_distribution',
        'last_calculated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'average_rating' => 'float',
        'rating_count' => 'integer',
        'rating_distribution' => 'array', // Stored as a Map in DynamoDB
        'percentage_distribution' => 'array', // Stored as a Map in DynamoDB
        'last_calculated_at' => 'datetime', // Stored as ISO8601 string, cast to Carbon instance
    ];
} 