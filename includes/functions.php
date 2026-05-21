
<?php
// ============================================================
// FindIt — functions.php
// Reusable helper functions
// ============================================================

function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}