<?php

namespace App\Models;

use BaoPham\DynamoDb\DynamoDbModel;
use Illuminate\Support\Str;

class RatingAndReview extends DynamoDbModel
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ratings_and_reviews';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'review_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
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
        'media',
        'publication_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'media' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * DynamoDB indexes.
     *
     * @var array
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
     * Create a new rating and review instance.
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set default values
        $this->attributes['review_id'] = $this->attributes['review_id'] ?? (string) Str::uuid();
        $this->attributes['created_at'] = $this->attributes['created_at'] ?? now()->toIso8601String();
        $this->attributes['publication_status'] = $this->attributes['publication_status'] ?? 'pending';
        $this->attributes['media'] = $this->attributes['media'] ?? json_encode([]);
    }

    /**
     * Get the review's media attachments.
     *
     * @return array
     */
    public function getMediaAttribute($value)
    {
        return json_decode($value, true) ?: [];
    }

    /**
     * Set the review's media attachments.
     *
     * @param array $value
     * @return void
     */
    public function setMediaAttribute($value)
    {
        $this->attributes['media'] = json_encode($value);
    }

    /**
     * Scope a query to only include published ratings and reviews.
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('publication_status', 'published');
    }

    /**
     * Scope a query to only include ratings and reviews for a specific product.
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $productId
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope a query to only include ratings and reviews from a specific country.
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $country
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeFromCountry($query, $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope a query to only include ratings and reviews in a specific language.
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $language
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeInLanguage($query, $language)
    {
        return $query->where('original_language', $language);
    }

    /**
     * Scope a query to only include ratings and reviews with a specific rating.
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param int $rating
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeWithRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to only include ratings and reviews from a specific user.
     *
     * @param \BaoPham\DynamoDb\Query\Builder $query
     * @param string $userId
     * @return \BaoPham\DynamoDb\Query\Builder
     */
    public function scopeFromUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
