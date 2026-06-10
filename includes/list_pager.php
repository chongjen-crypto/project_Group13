<?php
/**
 * Gmail-style table pagination helpers (10 rows per page by default).
 */
declare(strict_types=1);

/**
 * @return array{page: int, per_page: int, total: int, total_pages: int, offset: int, from: int, to: int}
 */
function list_pager_meta(int $total, int $page, int $perPage = 10): array
{
    $perPage = max(1, min(50, $perPage));
    $page = max(1, $page);
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;
    $from = $total === 0 ? 0 : $offset + 1;
    $to = min($offset + $perPage, $total);

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'from' => $from,
        'to' => $to,
    ];
}

/**
 * Compact page numbers (e.g. 1 … 4 5 6 … 12).
 *
 * @return list<int|string>
 */
function list_pager_page_items(int $current, int $total): array
{
    if ($total <= 7) {
        return range(1, max(1, $total));
    }
    $items = [1];
    $start = max(2, $current - 1);
    $end = min($total - 1, $current + 1);
    if ($start > 2) {
        $items[] = '…';
    }
    for ($i = $start; $i <= $end; $i++) {
        $items[] = $i;
    }
    if ($end < $total - 1) {
        $items[] = '…';
    }
    $items[] = $total;

    return $items;
}

/**
 * Build page URL preserving extra query parameters.
 */
function list_pager_url(string $baseUrl, int $page, array $extraParams = []): string
{
    $params = array_merge($extraParams, ['page' => $page]);
    $parts = [];
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null) {
            continue;
        }
        $parts[] = urlencode((string) $key) . '=' . urlencode((string) $value);
    }

    return $baseUrl . ($parts !== [] ? '?' . implode('&', $parts) : '');
}

/**
 * @param array{page: int, per_page: int, total: int, total_pages: int, from: int, to: int} $pagination
 * @param list<int|string> $pageItems
 */
function list_pager_render(
    array $pagination,
    array $pageItems,
    string $baseUrl,
    string $itemLabelSingular = 'item',
    string $itemLabelPlural = '',
    array $extraParams = [],
    string $navLabel = 'Table pages'
): void {
    if ($pagination['total'] <= 0) {
        return;
    }

    if ($itemLabelPlural === '') {
        $itemLabelPlural = $itemLabelSingular . 's';
    }

    $label = $pagination['total'] === 1 ? $itemLabelSingular : $itemLabelPlural;
    ?>
    <div class="table-pager">
        <div class="table-pager-info">
            <?php if ($pagination['total'] <= $pagination['per_page']): ?>
                <?php echo (int) $pagination['total']; ?> <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
            <?php else: ?>
                <?php echo (int) $pagination['from']; ?>–<?php echo (int) $pagination['to']; ?>
                of <?php echo (int) $pagination['total']; ?>
            <?php endif; ?>
        </div>
        <?php if ($pagination['total_pages'] > 1): ?>
        <nav class="table-pager-nav" aria-label="<?php echo htmlspecialchars($navLabel, ENT_QUOTES, 'UTF-8'); ?>">
            <?php
            $prevPage = max(1, $pagination['page'] - 1);
            $nextPage = min($pagination['total_pages'], $pagination['page'] + 1);
            $prevDisabled = $pagination['page'] <= 1;
            $nextDisabled = $pagination['page'] >= $pagination['total_pages'];
            ?>
            <a href="<?php echo htmlspecialchars(list_pager_url($baseUrl, $prevPage, $extraParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="table-pager-btn <?php echo $prevDisabled ? 'disabled' : ''; ?>"
               aria-label="Previous page"
               <?php echo $prevDisabled ? 'tabindex="-1"' : ''; ?>>
                <i class="bi bi-chevron-left"></i>
            </a>
            <?php foreach ($pageItems as $item): ?>
                <?php if ($item === '…'): ?>
                    <span class="table-pager-ellipsis">…</span>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars(list_pager_url($baseUrl, (int) $item, $extraParams), ENT_QUOTES, 'UTF-8'); ?>"
                       class="table-pager-btn <?php echo (int) $item === $pagination['page'] ? 'active' : ''; ?>"
                       <?php echo (int) $item === $pagination['page'] ? 'aria-current="page"' : ''; ?>>
                        <?php echo (int) $item; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <a href="<?php echo htmlspecialchars(list_pager_url($baseUrl, $nextPage, $extraParams), ENT_QUOTES, 'UTF-8'); ?>"
               class="table-pager-btn <?php echo $nextDisabled ? 'disabled' : ''; ?>"
               aria-label="Next page"
               <?php echo $nextDisabled ? 'tabindex="-1"' : ''; ?>>
                <i class="bi bi-chevron-right"></i>
            </a>
        </nav>
        <?php endif; ?>
    </div>
    <?php
}
