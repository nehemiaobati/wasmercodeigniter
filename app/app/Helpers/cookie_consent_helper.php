<?php declare(strict_types=1);

/**
 * Checks if the user has provided cookie consent.
 *
 * @return bool True if the consent cookie is set and has the value 'accepted', false otherwise.
 */
function hasCookieConsent(): bool
{
    // Use the filter_input function for secure access to cookie data.
    return filter_input(INPUT_COOKIE, 'user_cookie_consent', FILTER_SANITIZE_STRING) === 'accepted';
}
