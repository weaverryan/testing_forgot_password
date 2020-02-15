<?php

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

trait ResetPasswordControllerTrait
{
    private function storeTokenInSession(Request $request, string $token): void
    {
        $request->getSession()->set(self::SESSION_TOKEN_KEY, $token);
    }

    /**
     * @return mixed
     */
    private function getTokenFromSession(Request $request)
    {
        return $request->getSession()->get(self::SESSION_TOKEN_KEY);
    }
}