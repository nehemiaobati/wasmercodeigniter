<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Throttle Filter
 *
 * Implements rate limiting for routes to prevent brute-force attacks
 * and resource abuse. Uses cache to track request counts per IP.
 *
 * Usage: 'filter' => 'throttle:5,60' (5 requests per 60 seconds)
 */
class ThrottleFilter implements FilterInterface
{
    /**
     * Execute the filter before the controller.
     *
     * @param RequestInterface $request The incoming request
     * @param array|null       $arguments Arguments passed to filter (limit, seconds)
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = \Config\Services::throttler();

        // Default: 60 requests per minute
        $limit   = isset($arguments[0]) ? (int) $arguments[0] : 60;
        $seconds = isset($arguments[1]) ? (int) $arguments[1] : 60;

        $ip = $request->getIPAddress();
        // Create a unique key for the IP + Route
        $key = 'throttle_' . md5($ip . $request->getUri()->getPath());

        // Use the native Throttler service (Token Bucket algorithm)
        // check() returns false if the limit is reached
        if ($throttler->check($key, $limit, $seconds) === false) {
            // Check if it's an AJAX request via headers
            $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
            $isJson = str_contains($request->getHeaderLine('Accept'), 'application/json');

            if ($isAjax || $isJson) {
                return service('response')
                    ->setStatusCode(429)
                    ->setJSON([
                        'status' => 'error',
                        'message' => 'Too many requests. Please try again later.',
                        'token' => csrf_hash()
                    ]);
            }

            // Otherwise, redirect back with a flash error message
            return redirect()->back()->with('error', 'Too many requests. Please wait a moment before trying again.');
        }
    }

    /**
     * Execute the filter after the controller.
     *
     * @param RequestInterface  $request  The incoming request
     * @param ResponseInterface $response The outgoing response
     * @param array|null        $arguments Arguments passed to filter
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
    }
}
