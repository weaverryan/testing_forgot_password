<?php

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

trait ResetPasswordControllerTrait
{
    private function setCanCheckEmailInSession(Request $request, bool $value = true): void
    {
        $request->getSession()->set(self::SESSION_CAN_CHECK_EMAIL, $value);
    }

    private function canCheckEmailFromSession(SessionInterface $session): bool
    {
        if ($session->get(self::SESSION_CAN_CHECK_EMAIL)) {
            $session->remove(self::SESSION_CAN_CHECK_EMAIL);

            return true;
        }

        return false;
    }

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