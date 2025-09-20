<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SimpleLoginController
{
    #[Route(path: '/simple-login', name: 'app_simple_login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        $error = '';
        
        if ($request->isMethod('POST')) {
            $email = $request->request->get('_username');
            $password = $request->request->get('_password');
            
            if ($email === 'admin@chatapp.amirabedini.net' && $password === 'admin123') {
                return new Response('<h1>SUCCESS!</h1><p>Login worked! Email: ' . htmlspecialchars($email) . '</p>');
            } else {
                $error = 'Invalid credentials. Got: ' . htmlspecialchars($email);
            }
        }
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Login - Chat App</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Login to Chat App</h3></div>
                    <div class="card-body">
                        <p>Method: ' . $request->getMethod() . '</p>
                        ' . ($error ? '<div class="alert alert-danger">' . $error . '</div>' : '') . '
                        <form method="post" action="/simple-login">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="_username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <p class="mt-3 text-center">
                            <small>Admin: admin@chatapp.amirabedini.net / admin123</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
        
        return new Response($html);
    }
}
