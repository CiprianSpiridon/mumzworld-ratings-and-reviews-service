<?php

namespace App\Console\Commands;

use App\Models\RatingAndReview;
use App\Services\TranslationService;
use Illuminate\Console\Command;

class TranslateReviewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:translate {--limit=100 : Number of reviews to process} {--status=published : Filter by publication status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translate reviews to all supported languages';

    /**
     * The translation service.
     *
     * @var \App\Services\TranslationService
     */
    protected $translationService;

    /**
     * Create a new command instance.
     *
     * @param \App\Services\TranslationService $translationService
     * @return void
     */
    public function __construct(TranslationService $translationService)
    {
        parent::__construct();
        $this->translationService = $translationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $status = $this->option('status');

        $this->info("Starting to translate reviews (limit: {$limit}, status: {$status})...");

        // Build the query
        $query = RatingAndReview::query();

        // Filter by publication status if not 'all'
        if ($status !== 'all') {
            $query->where('publication_status', $status);
        }

        // Find reviews that need translation (where one of the language fields is empty)
        $query->where(function ($q) {
            $q->whereNull('review_en')
                ->orWhereNull('review_ar')
                ->orWhere('review_en', '')
                ->orWhere('review_ar', '');
        });

        // Get the total count for progress bar
        $total = $query->count();

        if ($total === 0) {
            $this->info('No reviews found that need translation.');
            return 0;
        }

        // Limit the number of reviews to process
        $reviews = $query->limit($limit)->get();
        $count = $reviews->count();

        $this->info("Found {$count} reviews that need translation.");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $translated = 0;
        $errors = 0;

        foreach ($reviews as $review) {
            try {
                $this->translationService->translateReview($review);
                $translated++;
            } catch (\Exception $e) {
                $this->error("Error translating review {$review->review_id}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Translation completed: {$translated} reviews translated, {$errors} errors.");

        return 0;
    }
}
