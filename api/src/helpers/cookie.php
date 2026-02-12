<?php

function setTokenInCookie()
{
    if (!isset($_COOKIE['token']) || $_COOKIE['token'] === '') {
        $cookieId = bin2hex(random_bytes(16));
        setcookie('token', $cookieId, 
        [
            'expires'  => time() + 31536000,
            'path'     => '/',
            'domain'   => 'project-beer.local',
            'secure'   => false,
            'httponly' => true
        ]);
    }
}