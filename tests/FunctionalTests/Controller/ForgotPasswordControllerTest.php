<?php

namespace App\Tests\FunctionalTests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ForgotPasswordControllerTest extends WebTestCase
{
    protected function makeRequest(string $uri, string $method = 'GET'): KernelBrowser
    {
        $client = static::createClient();
        $client->request($method, $uri);
        return $client;
    }

    /**
     * @test
     */
    public function showsResetRequestForm(): void
    {
        $this->makeRequest('/forgot-password/request');

        self::assertResponseIsSuccessful();
    }

    /**
     * @test
     */
    public function onSubmitRedirectToEmailNotification(): void
    {
        $client = $this->makeRequest('/forgot-password/request');
        $client->submitForm('Send e-mail', [
            'password_request_form[email]' => 'jr@rushlow.dev'
        ]);

        self::assertResponseRedirects('/forgot-password/check-email');
    }

    /**
     * @test
     */
    public function errorDisplayedWhenThrottleLimitReached(): void
    {
        $client = $this->makeRequest('/forgot-password/request');
        $client->submitForm('Send e-mail', [
            'password_request_form[email]' => 'jr@rushlow.dev'
        ]);

        $client->followRedirects();
        $client->request('GET', '/forgot-password/request');

        $crawler = $client->submitForm('Send e-mail', [
            'password_request_form[email]' => 'jr@rushlow.dev'
        ]);

        self::assertCount(1, $crawler->filter('.flash-error'));
    }

    /**
     * @test
     */
    public function successfulRequestSendsEmail(): void
    {
        $client = $this->makeRequest('/forgot-password/request');
        $client->submitForm('Send e-mail', [
            'password_request_form[email]' => 'jr@rushlow.dev'
        ]);

        self::assertEmailCount(1);
    }

    /**
     * @test
     */
    public function emailContainsValidResetToken(): void
    {
        $client = $this->makeRequest('/forgot-password/request');
        $client->submitForm('Send e-mail', [
            'password_request_form[email]' => 'jr@rushlow.dev'
        ]);

        $email = self::getMailerMessage();
        $context = $email->getContext();
        $token = $context['resetToken']->getToken();

        $client->followRedirects();
        $client->request('GET', '/forgot-password/reset/'. $token);

        self::assertPageTitleContains('Reset your password');
    }
}
