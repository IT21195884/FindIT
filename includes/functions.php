
<!-- cleaning and securing user input -->

<!-- Debugged using GenAI  -->

<?php
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}