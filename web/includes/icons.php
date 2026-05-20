<?php
/**
 * Doorman inline SVG icon set — ported from design_handoff icons.jsx
 * Lucide-flavored, minimal, 24×24 viewBox, 1.6 stroke
 *
 * Usage:
 *   <?= ico('home') ?>
 *   <?= ico('camera', 18) ?>
 *   <?= ico('lock', 14, 'text-danger') ?>
 */

function ico(string $name, int $size = 16, string $class = 'ico', float $stroke = 1.6): string {
    static $paths = null;
    if ($paths === null) {
        $paths = [
            'home'      => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
            'camera'    => '<path d="M3 7h4l2-2h6l2 2h4v12H3z"/><circle cx="12" cy="13" r="3.5"/>',
            'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6"/><circle cx="17" cy="9" r="2.5"/><path d="M22 19c0-2.5-2-4.5-5-4.5"/>',
            'list'      => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>',
            'bell'      => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10 21a2 2 0 0 0 4 0"/>',
            'settings'  => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3h.1a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8v.1a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
            'network'   => '<rect x="9" y="2" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="16" y="16" width="6" height="6" rx="1"/><path d="M12 8v4M5 16v-2h14v2"/>',
            'lock'      => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
            'unlock'    => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7-2.7"/>',
            'door'      => '<path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17"/><path d="M3 21h18"/><circle cx="15" cy="13" r="0.6" fill="currentColor"/>',
            'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'arrow-in'  => '<path d="M3 12h13"/><path d="m12 7 5 5-5 5"/><path d="M20 4v16"/>',
            'arrow-out' => '<path d="M21 12H8"/><path d="m12 7-5 5 5 5"/><path d="M4 4v16"/>',
            'plus'      => '<path d="M12 5v14M5 12h14"/>',
            'minus'     => '<path d="M5 12h14"/>',
            'filter'    => '<path d="M3 5h18l-7 9v6l-4-2v-4z"/>',
            'download'  => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
            'upload'    => '<path d="M12 21V9"/><path d="m7 14 5-5 5 5"/><path d="M5 5h14"/>',
            'cpu'       => '<rect x="5" y="5" width="14" height="14" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
            'thermo'    => '<path d="M14 14V5a2 2 0 1 0-4 0v9a4 4 0 1 0 4 0z"/>',
            'memory'    => '<rect x="3" y="6" width="18" height="12" rx="1"/><path d="M7 10v4M11 10v4M15 10v4M19 10v4"/>',
            'disk'      => '<line x1="22" y1="12" x2="2" y2="12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
            'wifi'      => '<path d="M5 12a10 10 0 0 1 14 0"/><path d="M8.5 15.5a5 5 0 0 1 7 0"/><circle cx="12" cy="19" r="1" fill="currentColor"/>',
            'chip'      => '<rect x="5" y="5" width="14" height="14" rx="2"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
            'server'    => '<rect x="3" y="4" width="18" height="7" rx="1"/><rect x="3" y="13" width="18" height="7" rx="1"/><circle cx="7" cy="7.5" r=".7" fill="currentColor"/><circle cx="7" cy="16.5" r=".7" fill="currentColor"/>',
            'database'  => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v6c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 11v6c0 1.66 4.03 3 9 3s9-1.34 9-3v-6"/>',
            'eye'       => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
            'eye-off'   => '<path d="M2 12s3.5-7 10-7c2.4 0 4.5.95 6.2 2.2"/><path d="M22 12s-3.5 7-10 7c-2.4 0-4.5-.95-6.2-2.2"/><path d="M3 3l18 18"/>',
            'alert-tri' => '<path d="M10.3 3.86a2 2 0 0 1 3.4 0l8 13.86A2 2 0 0 1 20 21H4a2 2 0 0 1-1.7-3.28z"/><path d="M12 9v5"/><circle cx="12" cy="17.5" r=".8" fill="currentColor" stroke="none"/>',
            'check'     => '<path d="m5 12 5 5 9-11"/>',
            'check-circle' => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
            'x'         => '<path d="M6 6l12 12M18 6 6 18"/>',
            'x-circle'  => '<circle cx="12" cy="12" r="9"/><path d="M9 9l6 6M15 9l-6 6"/>',
            'chev-r'    => '<path d="m9 6 6 6-6 6"/>',
            'chev-l'    => '<path d="m15 6-6 6 6 6"/>',
            'chev-down' => '<path d="m6 9 6 6 6-6"/>',
            'chev-up'   => '<path d="m6 15 6-6 6 6"/>',
            'refresh'   => '<path d="M3 12a9 9 0 0 1 15.5-6.4L21 8"/><path d="M21 4v4h-4"/><path d="M21 12a9 9 0 0 1-15.5 6.4L3 16"/><path d="M3 20v-4h4"/>',
            'more'      => '<circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1" fill="currentColor" stroke="none"/>',
            'maximize'  => '<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>',
            'minimize'  => '<path d="M9 4v5H4M15 4v5h5M9 20v-5H4M15 20v-5h5"/>',
            'power'     => '<path d="M12 3v9"/><path d="M18.4 6.6a9 9 0 1 1-12.8 0"/>',
            'id'        => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11.5" r="2"/><path d="M6 16c.5-1.5 2-2.5 3-2.5s2.5 1 3 2.5M14 9h5M14 13h4M14 16h3"/>',
            'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
            'shield'    => '<path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>',
            'activity'  => '<path d="M3 12h4l3-8 4 16 3-8h4"/>',
            'calendar'  => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',
            'mic'       => '<rect x="9" y="3" width="6" height="12" rx="3"/><path d="M5 12a7 7 0 0 0 14 0M12 19v3"/>',
            'pulse'     => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
            'sun'       => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>',
            'moon'      => '<path d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79z"/>',
            'log-out'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
            'log-in'    => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/>',
            'edit'      => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
            'trash'     => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
            'save'      => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/>',
            'copy'      => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
            'play'      => '<path d="m6 3 14 9-14 9V3z"/>',
            'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
            'arrow-left'  => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
            'arrow-up'    => '<path d="M12 19V5"/><path d="m5 12 7-7 7 7"/>',
            'arrow-down'  => '<path d="M12 5v14"/><path d="m19 12-7 7-7-7"/>',
            'globe'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
            'terminal'  => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="m7 9 3 3-3 3M13 15h4"/>',
            'code'      => '<path d="m8 18-6-6 6-6M16 6l6 6-6 6"/>',
            'info'      => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
            'help'      => '<circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01"/>',
            'book'      => '<path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v18H6.5A2.5 2.5 0 0 1 4 17.5v-13z"/><path d="M4 17.5V20h16"/>',
            'pin'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
            'face-smile'  => '<circle cx="12" cy="12" r="9"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01M15 9h.01"/>',
            'face-frown'  => '<circle cx="12" cy="12" r="9"/><path d="M8 16s1.5-2 4-2 4 2 4 2"/><path d="M9 9h.01M15 9h.01"/>',
            'user-x'    => '<path d="M11.5 14H7a4 4 0 0 0-4 4v2"/><circle cx="11" cy="7" r="4"/><path d="m17 16 5 5M22 16l-5 5"/>',
            'user-check'=> '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/>',
            'image'     => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/>',
            'video'     => '<rect x="2" y="6" width="15" height="12" rx="2"/><path d="m22 8-5 4 5 4V8z"/>',
            'signal'    => '<path d="M2 20h2M6 20h2v-4M10 20h2v-8M14 20h2v-12M18 20h2v-16"/>',
            'fire'      => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 17h2.5a2.5 2.5 0 0 0 0-5h-1a2.5 2.5 0 0 1 0-5h.5"/><path d="M12 22c-3.5 0-7-2.5-7-7 0-3.5 3-5 3-7 0-2 2-4 4-4 0 2 2 3 2 6 0 2 3 1 3 4 0 5-3 8-5 8z"/>',
            'arrows-lr' => '<path d="M8 3 4 7l4 4"/><path d="M4 7h16"/><path d="m16 21 4-4-4-4"/><path d="M20 17H4"/>',
            'sliders'   => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
            'project'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h18"/>',
            'sitemap'   => '<rect x="9" y="2" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="16" y="16" width="6" height="6" rx="1"/><path d="M12 8v4M5 16v-2h14v2"/>',
            'box'       => '<path d="m21 16-9 5-9-5V8l9-5 9 5v8z"/><path d="m3.3 7 8.7 5 8.7-5M12 12v9"/>',
            'shopping'  => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
            'plug'      => '<path d="M9 2v6M15 2v6"/><path d="M5 8h14v4a4 4 0 0 1-4 4h-6a4 4 0 0 1-4-4V8z"/><path d="M12 16v6"/>',
            'puzzle'    => '<path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.48-.872-.95a.745.745 0 0 0-1.482.121 2.5 2.5 0 1 0 4.965-.495l-1.498-1.498c-.47-.47-.706-1.087-.706-1.704s.235-1.233.706-1.704l1.568-1.568c.323-.323.46-.79.345-1.221A2.502 2.502 0 0 0 19.439 7.85zM12 2v20M2 12h20"/>',
            'lightbulb' => '<path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.7c.7.7 1 1.5 1 2.3v1h6v-1c0-.8.3-1.6 1-2.3A7 7 0 0 0 12 2z"/>',
            'magnify'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
            'send'      => '<path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/>',
            'flask'     => '<path d="M9 2v6L4 19a2 2 0 0 0 2 3h12a2 2 0 0 0 2-3L15 8V2"/><path d="M9 2h6"/>',
            'github'    => '<path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/>',
            'raspberry' => '<circle cx="12" cy="12" r="9"/><path d="M8 9a4 4 0 0 1 4 4 4 4 0 0 1 4-4"/><path d="M12 17v-4"/>',
            'satellite' => '<path d="M4 10a3 3 0 0 1 3-3 3 3 0 0 0 3-3"/><path d="m14.5 14.5 4-4"/><path d="M20 4 4 20"/><path d="M10 10a6 6 0 0 1 6-6 4 4 0 0 1 4 4M10 14a6 6 0 0 0 6 6 4 4 0 0 0 4-4"/>',
            'circle-dot' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3" fill="currentColor"/>',
            'minus-circle' => '<circle cx="12" cy="12" r="9"/><path d="M8 12h8"/>',
            'plug-zap'  => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
        ];
    }

    $key = strtolower($name);
    $svgInner = $paths[$key] ?? $paths['help'];
    $cls = htmlspecialchars($class, ENT_QUOTES);
    return '<svg class="' . $cls . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" '
        . 'fill="none" stroke="currentColor" stroke-width="' . $stroke . '" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $svgInner . '</svg>';
}
