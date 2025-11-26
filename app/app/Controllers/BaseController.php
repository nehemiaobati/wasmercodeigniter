<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * The request object.
     * @var IncomingRequest|CLIRequest|RequestInterface
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically.
     * @var array
     */
    protected $helpers = [];

    /**
     * The CodeIgniter session service.
     * @var \CodeIgniter\Session\Session
     */
    protected \CodeIgniter\Session\Session $session;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->session = service('session');
    }

    public function acceptCookie()
    {
        $cookie = [
            'name'   => 'user_cookie_consent',
            'value'  => 'accepted',
            'expire' => 31536000, // 1 year
        ];

        return redirect()->back()->setCookie($cookie);
    }
}
