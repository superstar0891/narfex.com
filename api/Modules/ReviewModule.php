<?php

namespace Modules;

use Db\Pagination\Paginator;
use Db\Where;
use Models\ReviewModel;

class ReviewModule {
    public static function bitcoinovnetReview($page = null, $count = null): Paginator {
        return ReviewModel::queryBuilder()
            ->where(
                Where::and()
                    ->set(Where::equal('platform', PLATFORM_BITCOINOVNET))
                    ->set(Where::or()
                        ->set(Where::equal('status', ReviewModel::STATUS_PUBLIC))
                        ->set(Where::equal('ip', ipAddress()))
                    )
            )
            ->orderBy(['id' => 'DESC'])
            ->paginate($page, $count);
    }

    public static function newBitcoinovnetReview(string $name, string $content) {
        $review = new ReviewModel();
        $review->name = $name;
        $review->content = $content;
        $review->ip = ipAddress();
        $review->platform = PLATFORM_BITCOINOVNET;
        $review->status = ReviewModel::STATUS_MODERATION;
        $review->save();
        return $review;
    }
}