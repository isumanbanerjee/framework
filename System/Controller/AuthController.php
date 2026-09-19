<?php

declare(strict_types=1);

namespace System\Controller;

use Core\Model\Auth;
use Core\Model\Database\Database;
use Core\Model\Logger;
use Core\Model\Request;
use Core\Model\Response;
use Core\Model\Session;
use Core\Model\Template;

/**
 * Handles the login/register form views and their submit handlers, wired
 * against the Auth/Session services and the resources/views/auth scaffolding.
 */
class AuthController
{
    private function template(): Template
    {
        return new Template(dirname(__DIR__, 2) . '/resources/views');
    }

    private function auth(Session $session): Auth
    {
        return new Auth(new Database(new Logger('logs/app.log')), $session);
    }

    public function showLogin(Request $request, Response $response): void
    {
        $session = new Session();

        $response->html($this->template()->render('auth.login', [
            'error' => $session->getFlash('error'),
            'csrfToken' => $session->generateCsrfToken(),
        ]));
    }

    public function login(Request $request, Response $response): void
    {
        $session = new Session();

        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '' || !$this->auth($session)->login($email, $password)) {
            $session->setFlash('error', 'Invalid email or password.');
            $response->redirect('/login');
            return;
        }

        $response->redirect('/');
    }

    public function showRegister(Request $request, Response $response): void
    {
        $session = new Session();

        $response->html($this->template()->render('auth.register', [
            'error' => $session->getFlash('error'),
            'csrfToken' => $session->generateCsrfToken(),
        ]));
    }

    public function register(Request $request, Response $response): void
    {
        $session = new Session();

        $name = (string) $request->input('name', '');
        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');
        $passwordConfirmation = (string) $request->input('password_confirmation', '');

        if ($name === '' || $email === '' || $password === '') {
            $session->setFlash('error', 'Name, email, and password are required.');
            $response->redirect('/register');
            return;
        }

        if ($password !== $passwordConfirmation) {
            $session->setFlash('error', 'Password confirmation does not match.');
            $response->redirect('/register');
            return;
        }

        $auth = $this->auth($session);

        if ($auth->identityExists($email)) {
            $session->setFlash('error', 'An account with that email already exists.');
            $response->redirect('/register');
            return;
        }

        $auth->register([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $auth->login($email, $password);

        $response->redirect('/');
    }

    public function logout(Request $request, Response $response): void
    {
        $session = new Session();
        $this->auth($session)->logout();

        $response->redirect('/login');
    }
}
