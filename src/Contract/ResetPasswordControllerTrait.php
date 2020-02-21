<?php

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

trait ResetPasswordControllerTrait
{
    private function setCanCheckEmailInSession(Request $request, ResetPasswordHelperInterface $helper, bool $value = true): void
    {
        $request->getSession()->set($helper->getSessionEmailKey(), $value);
    }

    private function isAbleToCheckEmail(SessionInterface $session, ResetPasswordHelperInterface $helper): bool
    {
        $sessionKey = $helper->getSessionEmailKey();

        if ($session->get($sessionKey)) {
            $session->remove($sessionKey);

            return true;
        }

        return false;
    }

    private function storeTokenInSession(Request $request, ResetPasswordHelperInterface $helper, string $token): void
    {
        $request->getSession()->set($helper->getSessionTokenKey(), $token);
    }

    private function getTokenFromSession(Request $request, ResetPasswordHelperInterface $helper): string
    {
        return $request->getSession()->get($helper->getSessionTokenKey());
    }
}