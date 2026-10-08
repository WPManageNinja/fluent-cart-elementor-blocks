<?php
/**
 * Seed approved product reviews so the review widgets have something to render.
 *
 * smoke/render-widgets asserts that the review widgets print their markup, which
 * they cannot do on a store with no reviews — the test fails for want of data
 * rather than for a defect. Run this once against a scratch site.
 *
 * Idempotent: rows are keyed by a marker in reviewer_email, so re-running
 * tops up rather than duplicating.
 *
 * Usage:
 *   wp --require=tests/bin/lib/load-plugins.php eval-file tests/bin/seed-reviews.php
 */

use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductReview;

if (!class_exists(ProductReview::class)) {
    echo "FluentCart reviews are unavailable in this build.\n";
    return;
}

const SEED_MARKER = '+seed@fluentcart.test';

$productId = (int) Product::query()
    ->where('post_status', 'publish')
    ->orderBy('ID', 'DESC')
    ->value('ID');

if (!$productId) {
    echo "No published product to attach reviews to.\n";
    return;
}

$existing = ProductReview::query()
    ->where('post_id', $productId)
    ->where('reviewer_email', 'LIKE', '%' . SEED_MARKER)
    ->count();

if ($existing >= 3) {
    echo "Already seeded: {$existing} reviews on product {$productId}.\n";
    return;
}

$seeds = [
    ['Asha Rahman', 5, 'Exactly as described', 'Fits true to size and arrived quickly. The finish is better than the photos suggest.'],
    ['Daniel Whitfield', 4, 'Comfortable, runs slightly narrow', 'Good cushioning for long days. I would size up half if you have wide feet.'],
    ['Mira Okonkwo', 5, 'Would buy again', 'Second pair I have ordered. Holds up well and still looks new after a month.'],
];

$created = 0;

foreach ($seeds as $index => $seed) {
    list($name, $rating, $title, $body) = $seed;

    $review = new ProductReview();
    $review->fill([
        'post_id'        => $productId,
        'reviewer_name'  => $name,
        'reviewer_email' => 'reviewer' . ($index + 1) . SEED_MARKER,
        'title'          => $title,
        'review'         => $body,
        'rating'         => $rating,
    ]);

    // status and is_verified are guarded — the real write path sets them
    // server-side after deriving them, so a seeder must do the same.
    $review->status = 'approved';
    $review->is_verified = 1;
    $review->save();

    $created++;
}

echo "Seeded {$created} approved reviews on product {$productId}.\n";

$total = ProductReview::query()->where('status', 'approved')->whereNull('parent_id')->count();
echo "Approved top-level reviews on the site: {$total}\n";
