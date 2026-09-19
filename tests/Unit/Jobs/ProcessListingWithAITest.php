<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessListingWithAI;
use App\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessListingWithAITest extends TestCase
{
    use RefreshDatabase;

    public function test_job_dispatches_successfully(): void
    {
        Queue::fake();

        $listing = Listing::factory()->create();

        ProcessListingWithAI::dispatch($listing, 'http://example.com/image.jpg');

        Queue::assertPushed(ProcessListingWithAI::class);
    }
}
