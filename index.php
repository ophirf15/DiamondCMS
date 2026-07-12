<?php

declare(strict_types=1);

/**
 * Shared-hosting front controller.
 *
 * Use when the domain document root cannot point at /public
 * (typical Bluehost public_html layout with the full app extracted here).
 *
 * Prefer pointing the domain document root at /public when the host allows it.
 */
require __DIR__.'/public/index.php';
