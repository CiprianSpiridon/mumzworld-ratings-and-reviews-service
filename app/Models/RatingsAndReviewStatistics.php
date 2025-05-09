<?php

namespace App\Models;

use BaoPham\DynamoDb\DynamoDbModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class RatingsAndReviewStatistics
 * 
 * Pre-calculated statistics for product ratings and reviews
 */
class RatingsAndReviewStatistics extends DynamoDbModel
{
    use HasFactory;

    /**
     * DynamoDB table name
     *
     * @var string
     */
    protected $table = 'ratings_and_review_statistics';

    /**
     * Primary key (DynamoDB Hash Key)
     *
     * @var string
     */
    protected $primaryKey = 'product_id';

    /**
     * Disable auto-incrementing primary key
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Mass assignable attributes
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'rating_count',
        'average_rating',
        'rating_distribution',
        'percentage_distribution',
        'last_calculated_at',
    ];

    /**
     * Attribute type casting
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating_count' => 'integer',
        'average_rating' => 'float',
        'rating_distribution' => 'array',    // Map in DynamoDB
        'percentage_distribution' => 'array', // Map in DynamoDB
        'last_calculated_at' => 'datetime',  // ISO8601 string
    ];
} 