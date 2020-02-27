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
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * @Route("/forgot-password")
 */
class ForgotPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private $resetPasswordHelper;

    public function __construct(ResetPasswordHelperInterface $resetPasswordHelper)
    {
        $this->resetPasswordHelper = $resetPasswordHelper;
    }

    /**
     * @Route("/request", name="app_forgot_password_request")
     */
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(PasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processRequestForm($form, $request, $mailer);
        }

        return $this->render('forgot_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/check-email", name="app_check_email")
     */
    public function checkEmail(SessionInterface $session): Response
    {
        // We prevent users from directly accessing this page
        if (!$this->canCheckEmail()) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('forgot_password/check_email.html.twig', [
            'tokenLifetime' => $this->resetPasswordHelper->getTokenLifetime(),
        ]);
    }

    /**
     * @Route("/reset/{token}", name="app_reset_password")
     */
    public function reset(Request $request, UserPasswordEncoderInterface $passwordEncoder, string $token = null): Response
    {
        //Put token in session and redirect to self
        if ($token) {
            // We store token in session and remove it from the URL,
            // to avoid any leak if someone get to know the URL (AJAX requests, Analytics...).
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        //Get token out of session storage
        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException();
        }

        //Validate token using password helper
        $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);

        //Reset password after token verified
        $form = $this->createForm(PasswordResettingFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A ResetPasswordToken should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode the plain password, and set it.
            //@TODO Encode password expects a UserInterface, we are only guaranteeing an object
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

    private function processRequestForm(FormInterface $form, Request $request, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'email' => $form->get('email')->getData(),
        ]);

        // Needed to be able to access next page, app_check_email
        $this->setCanCheckEmailInSession();

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', \sprintf(
                'There was a problem handling your password reset request - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('noreply@mydomain.com', 'Noreply'))
            ->to($user->getEmail())
            ->subject('Your password reset request')
            ->htmlTemplate('forgot_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ])
        ;

        $mailer->send($email);

        return $this->redirectToRoute('app_check_email');
    }
}
