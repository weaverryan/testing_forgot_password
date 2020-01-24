<?php

namespace App\Controller;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Form\PasswordRequestFormType;
use App\Form\PasswordResettingFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/forgot-password")
 */
class ForgotPasswordController extends AbstractController
{
    private const SESSION_TOKEN_KEY = 'forgot_password_token';
    private const SESSION_CAN_CHECK_EMAIL = 'forgot_password_check_email';

    /**
     * @Route("/request", name="app_forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(PasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'email' => $form->get('email')->getData(),
            ]);

            // Needed to be able to access next page, app_check_email
            $request->getSession()->set(self::SESSION_CAN_CHECK_EMAIL, true);

            // Do not reveal whether a user account was found or not.
            if (!$user) {
                return $this->redirectToRoute('app_check_email');
            }

            // If User already has a valid Token, we don't want to generate a new one.
            // We fail silently.
            $oldTokens = $this->getDoctrine()->getRepository(PasswordResetToken::class)->findNonExpiredForUser($user);
            if (count($oldTokens) > 0) {
                return $this->redirectToRoute('app_check_email');
            }

            // Generate a reset password token, that the user could use to change their password.
            $resetPasswordToken = new PasswordResetToken($user);
            $this->getDoctrine()->getManager()->persist($resetPasswordToken);
            $this->getDoctrine()->getManager()->flush();

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@mydomain.com', 'Noreply'))
                ->to($user->getEmail())
                ->subject('Your password reset request')
                ->htmlTemplate('forgot_password/email.html.twig')
                ->context([
                    'token' => $resetPasswordToken,
                ])
            ;
            $mailer->send($email);

            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('forgot_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/check-email", name="app_check_email")
     */
    public function checkEmail(SessionInterface $session)
    {
        // We prevent users from directly accessing this page
        if (!$session->get(self::SESSION_CAN_CHECK_EMAIL)) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $session->remove(self::SESSION_CAN_CHECK_EMAIL);

        return $this->render('forgot_password/check_email.html.twig', [
            'tokenLifetime' => PasswordResetToken::LIFETIME_HOURS,
        ]);
    }

    /**
     * @Route("/reset/{tokenAndSelector}", name="app_reset_password")
     */
    public function reset(Request $request, UserPasswordEncoderInterface $passwordEncoder, $tokenAndSelector = null): Response
    {
        if ($tokenAndSelector) {
            // We store token in session and remove it from the URL,
            // to avoid any leak if someone get to know the URL (AJAX requests, Analytics...).
            $request->getSession()->set(self::SESSION_TOKEN_KEY, $tokenAndSelector);

            return $this->redirectToRoute('app_reset_password');
        }

        $tokenAndSelector = $request->getSession()->get(self::SESSION_TOKEN_KEY);
        if (!$tokenAndSelector) {
            throw $this->createNotFoundException();
        }

        $passwordResetToken = $this->getDoctrine()->getRepository(PasswordResetToken::class)->findOneBy([
            'selector' => substr($tokenAndSelector, 0, PasswordResetToken::SELECTOR_LENGTH),
        ]);

        if (!$passwordResetToken) {
            throw $this->createNotFoundException();
        }

        if ($passwordResetToken->isExpired() || !$passwordResetToken->isTokenEquals(substr($tokenAndSelector, PasswordResetToken::SELECTOR_LENGTH))) {
            $this->getDoctrine()->getManager()->remove($passwordResetToken);
            $this->getDoctrine()->getManager()->flush();

            throw $this->createNotFoundException();
        }

        $form = $this->createForm(PasswordResettingFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A PasswordResetToken should be used only once, remove it.
            $this->getDoctrine()->getManager()->remove($passwordResetToken);

            // Encode the plain password, and set it.
            $passwordResetToken->getUser()->setPassword(
                $passwordEncoder->encodePassword(
                    $passwordResetToken->getUser(),
                    $form->get('plainPassword')->getData()
                )
            );

            $this->getDoctrine()->getManager()->flush();

            // TODO: please check the login route
            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
