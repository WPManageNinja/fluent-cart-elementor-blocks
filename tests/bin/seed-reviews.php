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
    // Still sync: the rows can predate this script learning to do it, and a
    // re-run is the obvious way to repair a summary stuck at zero.
    syncRatingAggregate($productId);
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

syncRatingAggregate($productId);

$total = ProductReview::query()->where('status', 'approved')->whereNull('parent_id')->count();
echo "Approved top-level reviews on the site: {$total}\n";

/**
 * Recompute the product's cached rating totals from its review rows.
 *
 * The review widgets do not count rows: the summary, the star rating and the
 * "N Reviews" heading all read `fct_product_details.other_info`, which the real
 * write path updates when a review is approved. A seeder that only inserts rows
 * leaves the list showing three reviews above a summary reading "Based on 0
 * reviews" — the data is there, every widget that aggregates it says zero.
 */
function syncRatingAggregate($productId)
{
    global $wpdb;

    $table = $wpdb->prefix . 'fct_product_details';
    $row   = $wpdb->get_row($wpdb->prepare("SELECT id, other_info FROM {$table} WHERE post_id = %d", $productId));

    if (!$row) {
        echo "No product_details row for {$productId}; rating totals not synced.\n";
        return;
    }

    $reviews = ProductReview::query()
        ->where('post_id', $productId)
        ->where('status', 'approved')
        ->whereNull('parent_id')
        ->get();

    $breakdown = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
    $sum       = 0;

    foreach ($reviews as $review) {
        $star = (string) max(1, min(5, (int) $review->rating));
        $breakdown[$star]++;
        $sum += (int) $review->rating;
    }

    $count = count($reviews);
    $info  = json_decode((string) $row->other_info, true);
    $info  = is_array($info) ? $info : [];

    $info['review_count']     = $count;
    $info['average_rating']   = $count ? round($sum / $count, 6) : 0;
    $info['rating_breakdown'] = $breakdown;

    $wpdb->update($table, ['other_info' => wp_json_encode($info)], ['id' => $row->id]);

    echo "Synced rating totals: {$count} review(s), average {$info['average_rating']}.\n";
}
