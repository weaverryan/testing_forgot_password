<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordRequestFormType;
use App\Form\PasswordResettingFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordTimeHelper;

/**
 * @Route("/forgot-password")
 */
class ForgotPasswordController extends AbstractController
{
    private const SESSION_TOKEN_KEY = 'forgot_password_token';
    private const SESSION_CAN_CHECK_EMAIL = 'forgot_password_check_email';

    /**
     * @var ResetPasswordHelperInterface
     */
    private $resetPasswordHelper;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
    }

    /**
     * @Route("/request", name="app_forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer, ResetPasswordHelperInterface $passwordResetHelper): Response
    {
        $form = $this->createForm(PasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processRequestForm($form, $request, $mailer, $passwordResetHelper);
        }

        return $this->render('forgot_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    protected function processRequestForm(FormInterface $form, Request $request, MailerInterface $mailer, ResetPasswordHelperInterface $passwordResetHelper): RedirectResponse
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $form->get('email')->getData(),
        ]);

        // Needed to be able to access next page, app_check_email
        $request->getSession()->set(self::SESSION_CAN_CHECK_EMAIL, true);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $passwordResetHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            // TODO - make this better..
            $this->addFlash('error', sprintf(
                'Ooops something went wrong. %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $email = $this->getEmailTemplate($user->getEmail(), $resetToken);
        $mailer->send($email);

        return $this->redirectToRoute('app_check_email');
    }

    protected function getEmailTemplate(string $emailAddress, ResetPasswordToken $resetToken): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address('noreply@mydomain.com', 'Noreply'))
            ->to($emailAddress)
            ->subject('Your password reset request')
            ->htmlTemplate('forgot_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;
    }

    /**
     * @Route("/check-email", name="app_check_email")
     */
    public function checkEmail(SessionInterface $session): Response
    {
        // We prevent users from directly accessing this page
        if (!$session->get(self::SESSION_CAN_CHECK_EMAIL)) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $session->remove(self::SESSION_CAN_CHECK_EMAIL);

        return $this->render('forgot_password/check_email.html.twig', [
            'tokenLifetime' => ResetPasswordTimeHelper::getFormattedSeconds($this->resetPasswordHelper->getTokenLifetime()),
        ]);
    }

    /**
     * @Route("/reset/{tokenAndSelector}", name="app_reset_password")
     */
    public function reset(Request $request, ResetPasswordHelperInterface $helper, UserPasswordEncoderInterface $passwordEncoder, string $tokenAndSelector = null): Response
    {
        //Put token in session and redirect to self
        if ($tokenAndSelector) {
            // We store token in session and remove it from the URL,
            // to avoid any leak if someone get to know the URL (AJAX requests, Analytics...).
            $request->getSession()->set(self::SESSION_TOKEN_KEY, $tokenAndSelector);

            return $this->redirectToRoute('app_reset_password');
        }

        //Get token out of session storage
        $tokenAndSelector = $request->getSession()->get(self::SESSION_TOKEN_KEY);
        if (!$tokenAndSelector) {
            throw $this->createNotFoundException();
        }

        //Validate token using password helper
        $partialUser = $helper->validateTokenAndFetchUser($tokenAndSelector);

        /** @var UserInterface $user */
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'id' => $partialUser->getId(),
        ]);

        //Reset password after token verified
        //@TODO Move to separate method
        $form = $this->createForm(PasswordResettingFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A ResetPasswordToken should be used only once, remove it.
            $helper->removeResetRequest($tokenAndSelector);

            // Encode the plain password, and set it.
            $encodedPassword = $passwordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            /**
             * We are assuming the user object HAS a setPassword method..
             * @TODO handle this if it doesnt/sets password using different method.
             * @psalm-suppress UndefinedInterfaceMethod
             */
            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();

            // TODO: please check the login route | CHANGE ROUTE, APP_FORGOT_PASSWORD_REQUEST USED IN DEVELOPMENT
            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('forgot_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}
