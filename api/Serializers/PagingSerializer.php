<?php

namespace Serializers;

class PagingSerializer {
    /* @param null|string $next
     * @param array $items
     * @return array
     */
    public static function detail($next, $items) {
        return [
            'next' => $next,
            'items' => $items,
        ];
    }
    /* @param null|string $next
     * @param array $items
     * @param int $total
     * @return array
     */
    public static function classicPaginator($items, $total = 0) {
        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
