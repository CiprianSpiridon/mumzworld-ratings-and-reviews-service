<?php

namespace App\Models;

use BaoPham\DynamoDb\DynamoDbModel;
use Illuminate\Support\Str;

/**
 * Class RatingAndReview
 * 
 * Represents a single product rating and review.
 * This model interacts with the 'ratings_and_reviews' DynamoDB table.
 */
class RatingAndReview extends DynamoDbModel
{

    /**
     * The DynamoDB table associated with the model.
     *
     * @var string
     */
    protected $table = 'ratings_and_reviews';

    /**
     * The primary key for the model (DynamoDB Hash Key).
     *
     * @var string
     */
    protected $primaryKey = 'review_id';

    /**
     * The "type" of the primary key ID.
     * For DynamoDB, this is typically 'string' (S), 'number' (N), or 'binary' (B).
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     * DynamoDB does not support auto-incrementing keys in the same way SQL databases do.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped using Laravel's default created_at/updated_at.
     * This model handles 'created_at' manually in the constructor and does not use 'updated_at'.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     * These can be set using array syntax, e.g., new RatingAndReview($attributes).
     *
     * @var array<int, string> 
     */
    protected $fillable = [
        'review_id',
        'user_id',
        'product_id',
        'rating',
        'original_language',
        'review_en',
        'review_ar',
        'country',
        'created_at',
        'media', // Stored as a JSON string, cast to array via accessor/mutator and $casts
        'publication_status',
    ];

    /**
     * The attributes that should be cast to native types or custom classes.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'media' => 'array', // Automatically decodes/encodes from/to JSON string for DynamoDB
        'created_at' => 'datetime', // Casts to Carbon instance from ISO8601 string
    ];

    /**
     * Defines the Global Secondary Indexes (GSIs) for the DynamoDB table.
     * Used by the baopham/dynamodb package for query building.
     *
     * @var array<string, array<string, string>>
     */
    protected $dynamoDbIndexKeys = [
        'user_id-index' => [
            'hash' => 'user_id',
        ],
        'product_id-index' => [
            'hash' => 'product_id',
        ],
        'publication_status-index' => [
            'hash' => 'publication_status',
        ],
    ];

    /**
     * Create a new rating and review model instance
     *
     * Sets defaults for review_id, created_at, publication_status, and media
     *
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set default values if not already provided in $attributes
        $this->attributes['review_id'] = $this->attributes['review_id'] ?? (string) Str::uuid();
        $this->attributes['created_at'] = $this->attributes['created_at'] ?? now()->toIso8601String();
        $this->attributes['publication_status'] = $this->attributes['publication_status'] ?? 'pending';
        
        // Ensure media is initialized as an empty JSON array string if not set or null
        if (!isset($this->attributes['media']) || is_null($this->attributes['media'])) {
            $this->attributes['media'] = json_encode([]);
        } elseif (is_array($this->attributes['media'])) {
            // If it was passed as an array during construction (e.g. from factory), encode it.
            // The setMediaAttribute mutator handles this for subsequent assignments.
            $this->attributes['media'] = json_encode($this->attributes['media']);
        }
    }

    /**
     * Accessor for the 'media' attribute
     *
     * @param string|null $value JSON string from DynamoDB
     * @return array Decoded media items or empty array
     */
    public function getMediaAttribute(?string $value): array
    {
        return $value ? (json_decode($value, true) ?: []) : [];
    }

    /**
     * Mutator for the 'media' attribute
     *
     * @param array|string|null $value Media items or JSON string
     * @return void
     */
    public function setMediaAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['media'] = json_encode($value);
        } elseif (is_string($value)) {
            // Assume it might already be a JSON string, ensure it's stored as such
            // Or handle potential error if it's not valid JSON, though $casts='array' helps.
            $this->attributes['media'] = $value; 
        } else {
            $this->attributes['media'] = json_encode([]);
        }
    }

    /**
     * Scope: published reviews only
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('publication_status', 'published');
    }

    /**
     * Scope: reviews for a specific product
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $productId
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope: reviews from a specific country
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $country 2-letter country code
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeFromCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope: reviews in a specific language
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $language Language code (e.g., 'en', 'ar')
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeInLanguage($query, string $language)
    {
        return $query->where('original_language', $language);
    }

    /**
     * Scope: reviews with a specific rating
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param int $rating Rating value (1-5)
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope: reviews from a specific user
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $userId
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeFromUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }
}
