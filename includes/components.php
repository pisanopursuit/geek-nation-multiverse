<?php
declare(strict_types=1);

/**
 * Geek Nation Multiverse shared UI components.
 *
 * All component classes use the gnm- prefix so the design system remains
 * isolated from legacy module CSS while pages are migrated incrementally.
 */

function gnm_attrs(array $attributes): string {
    $parts = [];
    foreach ($attributes as $name => $value) {
        if ($value === null || $value === false) continue;
        if ($value === true) {
            $parts[] = e((string)$name);
            continue;
        }
        $parts[] = e((string)$name) . '="' . e((string)$value) . '"';
    }
    return $parts ? ' ' . implode(' ', $parts) : '';
}

function gnm_icon(string $name): string {
    $icons = [
        'arrow' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 10h11M11 5l5 5-5 5"/></svg>',
        'spark' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 2l1.7 5.1L17 9l-5.3 1.9L10 16l-1.7-5.1L3 9l5.3-1.9L10 2Z"/></svg>',
        'search' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="8.5" cy="8.5" r="5.5"/><path d="m13 13 4 4"/></svg>',
        'calendar' => '<svg viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="5" width="14" height="12" rx="2"/><path d="M6 3v4M14 3v4M3 9h14"/></svg>',
        'users' => '<svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="7" cy="7" r="3"/><circle cx="14" cy="8" r="2.5"/><path d="M2 17c.5-3 2.2-5 5-5s4.5 2 5 5M12 13c2.8 0 4.5 1.5 5 4"/></svg>',
        'shop' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 8h14l-1-4H4L3 8Z"/><path d="M4 8v9h12V8M8 17v-5h4v5"/></svg>',
        'book' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M3 4.5c2.8-.8 5-.3 7 1.5v11c-2-1.8-4.2-2.3-7-1.5v-11ZM17 4.5c-2.8-.8-5-.3-7 1.5v11c2-1.8 4.2-2.3 7-1.5v-11Z"/></svg>',
        'star' => '<svg viewBox="0 0 20 20" aria-hidden="true"><path d="m10 2.5 2.2 4.6 5 .7-3.6 3.5.9 5-4.5-2.4-4.5 2.4.9-5-3.6-3.5 5-.7L10 2.5Z"/></svg>',
    ];
    return $icons[$name] ?? $icons['spark'];
}

function gnm_button(string $label, string $href, string $variant = 'primary', array $attributes = []): string {
    $allowed = ['primary', 'secondary', 'ghost', 'text', 'danger'];
    if (!in_array($variant, $allowed, true)) $variant = 'primary';
    $attributes['class'] = trim('gnm-button gnm-button--' . $variant . ' ' . ($attributes['class'] ?? ''));
    return '<a href="' . e($href) . '"' . gnm_attrs($attributes) . '><span>' . e($label) . '</span>' . gnm_icon('arrow') . '</a>';
}

function gnm_badge(string $label, string $variant = 'neutral'): string {
    $allowed = ['neutral', 'accent', 'success', 'warning', 'danger', 'info'];
    if (!in_array($variant, $allowed, true)) $variant = 'neutral';
    return '<span class="gnm-badge gnm-badge--' . e($variant) . '">' . e($label) . '</span>';
}

function gnm_page_hero(array $options): void {
    $eyebrow = trim((string)($options['eyebrow'] ?? ''));
    $title = trim((string)($options['title'] ?? ''));
    $description = trim((string)($options['description'] ?? ''));
    $actions = $options['actions'] ?? [];
    $class = trim('gnm-page-hero ' . (string)($options['class'] ?? ''));

    echo '<section class="' . e($class) . '"><div class="gnm-page-hero__content">';
    if ($eyebrow !== '') echo '<p class="gnm-eyebrow">' . e($eyebrow) . '</p>';
    if ($title !== '') echo '<h1>' . e($title) . '</h1>';
    if ($description !== '') echo '<p class="gnm-page-hero__description">' . e($description) . '</p>';
    if ($actions) {
        echo '<div class="gnm-actions">';
        foreach ($actions as $action) {
            echo gnm_button((string)$action['label'], (string)$action['href'], (string)($action['variant'] ?? 'primary'));
        }
        echo '</div>';
    }
    echo '</div></section>';
}

function gnm_section_header(string $title, ?string $exploreLabel = null, ?string $exploreHref = null, ?string $description = null): void {
    echo '<header class="gnm-section-header"><div class="gnm-section-header__copy"><h2>' . e($title) . '</h2>';
    if ($description) echo '<p>' . e($description) . '</p>';
    echo '</div>';
    if ($exploreLabel && $exploreHref) {
        echo '<a class="gnm-explore-link" href="' . e($exploreHref) . '"><span>' . e($exploreLabel) . '</span>' . gnm_icon('arrow') . '</a>';
    }
    echo '</header>';
}

function gnm_card(array $options): void {
    $title = trim((string)($options['title'] ?? ''));
    $description = trim((string)($options['description'] ?? ''));
    $href = trim((string)($options['href'] ?? ''));
    $eyebrow = trim((string)($options['eyebrow'] ?? ''));
    $image = trim((string)($options['image'] ?? ''));
    $icon = trim((string)($options['icon'] ?? ''));
    $meta = $options['meta'] ?? [];
    $badges = $options['badges'] ?? [];
    $variant = preg_replace('/[^a-z0-9_-]/i', '', (string)($options['variant'] ?? 'default')) ?: 'default';
    $class = trim('gnm-card gnm-card--' . $variant . ' ' . (string)($options['class'] ?? ''));
    $tag = $href !== '' ? 'a' : 'article';
    $hrefAttribute = $href !== '' ? ' href="' . e($href) . '"' : '';

    echo '<' . $tag . ' class="' . e($class) . '"' . $hrefAttribute . '>';
    if ($image !== '') {
        echo '<div class="gnm-card__media"><img src="' . e($image) . '" alt="" loading="lazy"></div>';
    } elseif ($icon !== '') {
        echo '<div class="gnm-card__icon">' . gnm_icon($icon) . '</div>';
    }
    echo '<div class="gnm-card__body">';
    if ($eyebrow !== '') echo '<p class="gnm-card__eyebrow">' . e($eyebrow) . '</p>';
    if ($title !== '') echo '<h3>' . e($title) . '</h3>';
    if ($description !== '') echo '<p class="gnm-card__description">' . e($description) . '</p>';
    if ($badges) {
        echo '<div class="gnm-card__badges">';
        foreach ($badges as $badge) echo gnm_badge((string)$badge['label'], (string)($badge['variant'] ?? 'neutral'));
        echo '</div>';
    }
    if ($meta) {
        echo '<ul class="gnm-card__meta">';
        foreach ($meta as $item) echo '<li>' . e((string)$item) . '</li>';
        echo '</ul>';
    }
    if ($href !== '') echo '<span class="gnm-card__arrow">' . gnm_icon('arrow') . '</span>';
    echo '</div></' . $tag . '>';
}

function gnm_stat_card(string $label, string|int $value, ?string $detail = null, string $icon = 'spark'): void {
    echo '<article class="gnm-stat-card"><div class="gnm-stat-card__icon">' . gnm_icon($icon) . '</div><div>';
    echo '<p class="gnm-stat-card__label">' . e($label) . '</p><p class="gnm-stat-card__value">' . e((string)$value) . '</p>';
    if ($detail) echo '<p class="gnm-stat-card__detail">' . e($detail) . '</p>';
    echo '</div></article>';
}

function gnm_action_card(string $title, string $description, string $label, string $href, string $icon = 'spark'): void {
    echo '<article class="gnm-action-card"><div class="gnm-action-card__icon">' . gnm_icon($icon) . '</div><div class="gnm-action-card__copy">';
    echo '<h3>' . e($title) . '</h3><p>' . e($description) . '</p>';
    echo gnm_button($label, $href, 'secondary');
    echo '</div></article>';
}

function gnm_empty_state(string $title, string $description, ?string $actionLabel = null, ?string $actionHref = null, string $icon = 'spark'): void {
    echo '<section class="gnm-empty-state"><div class="gnm-empty-state__icon">' . gnm_icon($icon) . '</div><h2>' . e($title) . '</h2><p>' . e($description) . '</p>';
    if ($actionLabel && $actionHref) echo gnm_button($actionLabel, $actionHref, 'primary');
    echo '</section>';
}

function gnm_filter_bar(array $filters, string $action = ''): void {
    echo '<form class="gnm-filter-bar" method="get" action="' . e($action) . '">';
    foreach ($filters as $filter) {
        $type = (string)($filter['type'] ?? 'text');
        $name = (string)($filter['name'] ?? '');
        $label = (string)($filter['label'] ?? ucfirst($name));
        $value = (string)($filter['value'] ?? '');
        echo '<label><span>' . e($label) . '</span>';
        if ($type === 'select') {
            echo '<select name="' . e($name) . '">';
            foreach (($filter['options'] ?? []) as $optionValue => $optionLabel) {
                $selected = (string)$optionValue === $value ? ' selected' : '';
                echo '<option value="' . e((string)$optionValue) . '"' . $selected . '>' . e((string)$optionLabel) . '</option>';
            }
            echo '</select>';
        } else {
            echo '<input type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '" placeholder="' . e((string)($filter['placeholder'] ?? '')) . '">';
        }
        echo '</label>';
    }
    echo '<button class="gnm-button gnm-button--primary" type="submit"><span>Apply Filters</span>' . gnm_icon('arrow') . '</button></form>';
}


/** Version 10.7: safe cross-module discovery helpers. */
function gnm_safe_rows(string $sql, array $params = []): array {
    try {
        $statement = db()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function gnm_related_section(string $title, string $exploreLabel, string $exploreHref, array $items, string $emptyMessage = ''): void {
    if (!$items && $emptyMessage === '') return;
    echo '<section class="gnm-related-section">';
    gnm_section_header($title, $exploreLabel, $exploreHref);
    if ($items) {
        echo '<div class="gnm-grid gnm-grid--auto">';
        foreach ($items as $item) gnm_card($item);
        echo '</div>';
    } else {
        echo '<p class="gnm-related-empty">' . e($emptyMessage) . '</p>';
    }
    echo '</section>';
}
