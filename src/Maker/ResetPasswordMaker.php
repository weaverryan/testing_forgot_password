<?php

namespace App\Maker;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Security\InteractiveSecurityHelper;
use Symfony\Bundle\MakerBundle\Util\YamlSourceManipulator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle;

class ResetPasswordMaker extends AbstractMaker
{
    private $container;
    /**
     * @var FileManager
     */
    private $fileManager;

    public function __construct(ContainerInterface $container)
    {
//        $this->fileManager = $fileManager;
        //@TODO - dev only
        $this->container = $container;
    }

    public static function getCommandName(): string
    {
        return 'make:reset-password';
    }

    public function configureCommand(
        Command $command,
        InputConfiguration $inputConfig
    ) {
        $command
            ->setDescription('Create controller, entity, and repositories for use with SymfonyCasts Reset Password Bundle.')
            ;
    }

    public function configureDependencies(DependencyBuilder $dependencies)
    {
        $dependencies->addClassDependency(SymfonyCastsResetPasswordBundle::class, 'symfonycasts/reset-password-bundle');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        $requirements[] = '<question>- Reset Password Bundle Requirements -</>'."\n";
        $requirements[] = '1) A user entity has been created.';
        $requirements[] = '2) The user entity contains an email property with getter method.';
        $requirements[] = '3) A user repository exists for the user entity.'."\n";
        $requirements[] = '<fg=yellow>bin/console make:user</> will generate the user entity and it\'s repository...'."\n";
        $io->text($requirements);

        // initialize arguments & commands that are internal (i.e. meant only to be asked)
        $command
            ->addArgument('user-class')
            ->addArgument('email-property-name')
            ->addArgument('email-getter')
            ->addArgument('password-setter')
            ->addArgument('from-email-address')
            ->addArgument('from-email-name')
        ;

        $emailText[] = 'Please answer the following questions that will be used to generate the email templates.';
        $emailText[] = 'If you are unsure of what these answers will be, that\'s ok. You can change these later in the generated templates.';
        $io->text($emailText);

        $input->setArgument(
            'from-email-address',
            $io->ask('What email address will be used to send reset confirmations? I.e. you@your-domain.com')
        );

        $input->setArgument(
            'from-email-name',
            $io->ask('What name will be associated with the from email address? I.e. John Smith or ABC Company, LLC')
        );

        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        $this->fileManager = $this->container->get('maker.file_manager');
        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command needs that file to accurately build the reset password form.');
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $securityData = $manipulator->getData();
        $providersData = $securityData['security']['providers'] ?? [];

        $input->setArgument(
            'user-class',
            $userClass = $interactiveSecurityHelper->guessUserClass(
                $io,
                $providersData,
                'Enter the User class that should be used with the "forgotten password" feature (e.g. <fg=yellow>App\\Entity\\User</>)'
            )
        );

        $io->text(sprintf('Implementing reset password for <info>%s</info>', $userClass));

        $input->setArgument(
            'email-property-name',
            $interactiveSecurityHelper->guessEmailField($io, $userClass)
        );
        $input->setArgument(
            'email-getter',
            $interactiveSecurityHelper->guessEmailGetter($io, $userClass)
        );
        $input->setArgument(
            'password-setter',
            $interactiveSecurityHelper->guessPasswordSetter($io, $userClass)
        );
    }

    public function generate(
        InputInterface $input,
        ConsoleStyle $io,
        Generator $generator
    ) {
        $userClass = $input->getArgument('user-class');
        $userClassNameDetails = $generator->createClassNameDetails(
            '\\'.$userClass,
            'Entity\\'
        );

        $controllerClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordController',
            'Controller\\'
        );

        $requestClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordRequest',
            'Entity\\'
        );

        $repositoryClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordRequestRepository',
            'Repository\\'
        );

        $requestFormTypeClassNameDetails = $generator->createClassNameDetails(
            'ResetPasswordRequestFormType',
            'Form\\'
        );

        $changePasswordFormTypeClassNameDetails = $generator->createClassNameDetails(
            'ChangePasswordFormType',
            'Form\\'
        );

        $templatePath = 'src/Resource/templates/';

        $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            $templatePath.'ResetPasswordController.tpl.php',
            [
                'user_full_class_name' => $userClassNameDetails->getFullName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'request_form_type_full_class_name' => $requestFormTypeClassNameDetails->getFullName(),
                'request_form_type_class_name' => $requestFormTypeClassNameDetails->getShortName(),
                'reset_form_type_full_class_name' => $changePasswordFormTypeClassNameDetails->getFullName(),
                'reset_form_type_class_name' => $changePasswordFormTypeClassNameDetails->getShortName(),
                'password_setter' => $input->getArgument('password-setter'),
                'from_email' => $input->getArgument('from-email-address'),
                'from_email_name' => $input->getArgument('from-email-name'),
                'email_getter' => $input->getArgument('email-getter')
            ]
        );

        $generator->generateClass(
            $requestClassNameDetails->getFullName(),
            $templatePath.'ResetPasswordRequest.tpl.php',
            [
                'repository_class_name' => $repositoryClassNameDetails->getFullName(),
                'user_full_class_name' => $userClassNameDetails->getFullName()
            ]
        );

        $generator->generateClass(
            $repositoryClassNameDetails->getFullName(),
            $templatePath.'ResetPasswordRequestRepository.tpl.php',
            [
                'request_class_full_name' => $requestClassNameDetails->getFullName(),
                'request_class_name' => $requestClassNameDetails->getShortName()
            ]
        );

        $this->fileManager = $this->container->get('maker.file_manager');
        if (!$this->fileManager->fileExists($path = 'config/packages/reset_password.yaml')) {
            throw new RuntimeCommandException(sprintf('The file "%s" does not exist. This command needs that file to accurately build the reset password config.', $path));
        }

        $manipulator = new YamlSourceManipulator($this->fileManager->getFileContents($path));
        $data = $manipulator->getData();

        $data['symfonycasts_reset_password'] = ['request_password_repository' => $repositoryClassNameDetails->getFullName()];

        $manipulator->setData($data);

        $generator->dumpFile($path, $manipulator->getContents());

        $generator->generateClass(
            $requestFormTypeClassNameDetails->getFullName(),
            $templatePath.'ResetPasswordRequestFormType.tpl.php',
            [
                'email_field' => $input->getArgument('email-property-name')
            ]
        );

        $generator->generateClass(
            $changePasswordFormTypeClassNameDetails->getFullName(),
            $templatePath.'ChangePasswordFormType.tpl.php'
        );

        $generator->generateTemplate(
            'reset_password/check_email.html.twig',
            $templatePath.'twig_check_email.tpl.php',
            []
        );

        $generator->generateTemplate(
            'reset_password/email.html.twig',
            $templatePath.'twig_email.tpl.php',
            []
        );

        $generator->generateTemplate(
            'reset_password/request.html.twig',
            $templatePath.'twig_request.tpl.php',
            [
                'email_field' => $input->getArgument('email-property-name')
            ]
        );

        $generator->generateTemplate(
            'reset_password/reset.html.twig',
            $templatePath.'twig_reset.tpl.php',
            []
        );

        $generator->writeChanges();

        $this->successMessage($io);
    }

    private function successMessage(ConsoleStyle $io): void
    {
        $closing[] = 'Check the login redirect route is correct in <fg=yellow>ResetPasswordController::reset()</>';
        $closing[] = 'Set correct "from" email address and name values for the email template in <fg=yellow>ResetPasswordController::processRequestForm()</>';
        $closing[] = 'Ensure <fg=yellow>MAILER_DSN</> has the correct host for sending emails.';
        $io->text($closing);
    }

}
