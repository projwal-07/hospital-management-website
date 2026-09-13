<?php
declare(strict_types=1);

/**
 * Presentation-only inline SVG icon set for the dynamic (application) pages.
 *
 * There is no data, logic or external asset here. Every icon is a 24x24
 * stroke glyph that inherits its colour from `currentColor`; size and colour
 * are set in CSS. Icons are decorative (aria-hidden) - the meaning is always
 * carried by the visible text label next to them.
 *
 * Usage:  <?= svg_icon('calendar-plus') ?>
 *         <?= svg_icon('clock', ['class' => 'dash-stat-glyph']) ?>
 */

if (!function_exists('svg_icon')) {

    function svg_icon(string $name, array $attr = []): string
    {
        static $paths = [
            'calendar'       => '<rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M8 3v3M16 3v3M3 9.5h18"/>',
            'calendar-plus'  => '<rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M8 3v3M16 3v3M3 9.5h18M12 13v5M9.5 15.5h5"/>',
            'calendar-check' => '<rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M8 3v3M16 3v3M3 9.5h18M8.5 15l2.4 2.4 4.6-5.1"/>',
            'calendar-clock' => '<path d="M21 11.4V6.5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6.1"/><path d="M8 3v3M16 3v3M3 9.5h18"/><circle cx="17.5" cy="17.5" r="3.6"/><path d="M17.5 15.9v1.7l1.1 1"/>',
            'list'           => '<path d="M8 6h12.5M8 12h12.5M8 18h12.5M3.5 6h.01M3.5 12h.01M3.5 18h.01"/>',
            'clock'          => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5.2l3.4 2"/>',
            'check-circle'   => '<circle cx="12" cy="12" r="8.5"/><path d="M8.5 12.2l2.4 2.4 4.6-5.2"/>',
            'check'          => '<path d="M4.5 12.5l4.5 4.5 10.5-11"/>',
            'x-circle'       => '<circle cx="12" cy="12" r="8.5"/><path d="M9 9l6 6M15 9l-6 6"/>',
            'history'        => '<path d="M3.6 12a8.4 8.4 0 1 0 2.5-6"/><path d="M3.5 4.5V9H8"/><path d="M12 7.5v5l3.4 2"/>',
            'users'          => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5c.7-3.4 3-5.6 5.5-5.6s4.8 2.2 5.5 5.6"/><path d="M16 5.2a3.2 3.2 0 0 1 0 6.2M17.8 13.6c2 .8 3.4 2.7 3.7 5"/>',
            'user-plus'      => '<circle cx="9" cy="8" r="3.4"/><path d="M3.5 19.5c.8-3.5 3-5.8 5.5-5.8s4.7 2.3 5.5 5.8"/><path d="M18 8v6M15 11h6"/>',
            'shield'         => '<path d="M12 3l7.5 3v5.4c0 4.9-3.2 8.3-7.5 10.1-4.3-1.8-7.5-5.2-7.5-10.1V6z"/><path d="M9 12l2 2 4-4.5"/>',
            'stethoscope'    => '<path d="M6 3v5a5 5 0 0 0 10 0V3"/><path d="M4.5 3H7M13 3h2.5"/><path d="M11 17.5a5.5 5.5 0 0 0 5.5-5.5V12"/><circle cx="18" cy="10.5" r="2.4"/>',
            'building'       => '<rect x="4.5" y="3.5" width="15" height="17" rx="1.5"/><path d="M9 8h1.5M13.5 8H15M9 12h1.5M13.5 12H15M10.5 20.5v-4h3v4"/>',
            'phone'          => '<path d="M21 16.5v2.6a2 2 0 0 1-2.2 2 19.6 19.6 0 0 1-8.5-3 19.3 19.3 0 0 1-6-6 19.6 19.6 0 0 1-3-8.6A2 2 0 0 1 3.3 3.3H6a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L7 11.1a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
            'mail'           => '<rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="M3 6l9 6.5L21 6"/>',
            'map-pin'        => '<path d="M12 21.5s-6.5-5.6-6.5-11A6.5 6.5 0 0 1 18.5 10.5c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10.5" r="2.3"/>',
            'lock'           => '<rect x="4.5" y="10" width="15" height="10.5" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
            'activity'       => '<path d="M3 12h4l2.5-6 4 15 3-9H21"/>',
            'edit'           => '<path d="M4 20h4L18.5 9.5l-4-4L4 16z"/><path d="M13.5 6l4 4"/>',
            'chevron-right'  => '<path d="M9 5l7 7-7 7"/>',
            'arrow-left'     => '<path d="M20 12H4M10 6l-6 6 6 6"/>',
        ];

        $inner = $paths[$name] ?? $paths['activity'];

        $class = 'ico ico-' . preg_replace('/[^a-z0-9-]/', '', $name);
        if (isset($attr['class']) && $attr['class'] !== '') {
            $class .= ' ' . $attr['class'];
        }

        return '<svg class="' . e($class) . '" width="24" height="24" viewBox="0 0 24 24" '
             . 'fill="none" stroke="currentColor" stroke-width="1.8" '
             . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
             . $inner . '</svg>';
    }
}

if (!function_exists('user_initials')) {

    /**
     * Presentation helper: 1-2 letter initials from a person's display
     * name, for the compact navbar avatar. No data access - the caller
     * passes the already-authenticated full name.
     *
     *   "Prajwal Adhikari" -> "PA"   "Alan Reyes" -> "AR"
     *   "Madonna"          -> "M"    ""            -> "?"
     */
    function user_initials(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($parts === []) {
            return '?';
        }

        $first = function_exists('mb_substr')
            ? mb_strtoupper(mb_substr($parts[0], 0, 1))
            : strtoupper(substr($parts[0], 0, 1));

        if (count($parts) === 1) {
            return $first;
        }

        $last = end($parts);
        $lastInitial = function_exists('mb_substr')
            ? mb_strtoupper(mb_substr($last, 0, 1))
            : strtoupper(substr($last, 0, 1));

        return $first . $lastInitial;
    }
}
