<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

class AuthController extends BaseController
{
    public function __construct(
        Twig $view,
        private AuthService $authService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($view);
    }

    public function showRegister(Request $request, Response $response): Response
    {
        // TODO: you also have a logger service that you can inject and use anywhere; file is var/app.log
        $this->logger->info('Register page requested');

        return $this->render($response, 'auth/register.twig');
    }

    public function register(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user registration
        $credentials = $request->getParsedBody();
        $errors = [];

        if(strlen($credentials['username']) < 4) {
            $this->logger->warning('Username must be at least 4 characthers long!');
            $errors['username'] = 'Username must be at least 4 characters long!';
        }

        if(strlen($credentials['password']) < 8) {
            $this->logger->warning('Password must have at least 8 characters!');
            $errors['password'] = 'Password must have at least 8 characters!';
        }

        if(!preg_match('/[0-9]/', $credentials['password'])) {
            $this->logger->warning('Password must contain at least one number');
            $errors['password'] = 'Password must contain at least one number';
        }

        if(!empty($errors)) {
            $response = $response->withStatus(400);
            return $this->render($response,'auth/register.twig', ['errors'=> $errors, 'username' => $credentials['username'], 'password'=> $credentials['password']]);
        }

        $this->authService->register($credentials['username'], $credentials['password']);
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    public function showLogin(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login.twig');
    }

    public function login(Request $request, Response $response): Response
    {
        // TODO: call corresponding service to perform user login, handle login failures
        $credentials = $request->getParsedBody();
        $loggedIn = $this->authService->attempt($credentials['username'], $credentials['password']);

        if (! $loggedIn) {
            return $this->render($response,'auth/login.twig', ['error' => 'Wrong username or password']);
        }

        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response): Response
    {
        // TODO: handle logout by clearing session data and destroying session
        session_unset();
        session_destroy();

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
