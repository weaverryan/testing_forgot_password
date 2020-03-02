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
        // initialize arguments & commands that are internal (i.e. meant only to be asked)
        $command
            ->addArgument('user-class')
//            ->addArgument('email-field')
            ->addArgument('email-getter')
            ->addArgument('password-setter')
        ;

        $interactiveSecurityHelper = new InteractiveSecurityHelper();

        $this->fileManager = $this->container->get('maker.file_manager');
        if (!$this->fileManager->fileExists($path = 'config/packages/security.yaml')) {
            throw new RuntimeCommandException('The file "config/packages/security.yaml" does not exist. This command needs that file to accurately build the forgotten password form.');
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

        $io->text(sprintf('Implementing forgotten password for <info>%s</info>', $userClass));

//        $input->setArgument(
//            'email-field',
//            $interactiveSecurityHelper->guessEmailField($io, $userClass)
//        );
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
            'XResetPasswordController',
            'Controller\\'
        );

        $requestClassNameDetails = $generator->createClassNameDetails(
            'XResetPasswordRequest',
            'Entity\\'
        );

        $repositoryClassNameDetails = $generator->createClassNameDetails(
            'XResetPasswordRequestRepository',
            'Repository\\'
        );

        $requestFormTypeClassNameDetails = $generator->createClassNameDetails(
            'XResetPasswordRequestFormType',
            'Form\\'
        );

        $resetFormTypeClassNameDetails = $generator->createClassNameDetails(
            'XResetPasswordResetFormType',
            'Form\\'
        );

        $generator->generateController(
            $controllerClassNameDetails->getFullName(),
            'src/Resource/templates/ResetPasswordController.tpl.php',
            [
                'user_full_class_name' => $userClassNameDetails->getFullName(),
                'user_class_name' => $userClassNameDetails->getShortName(),
                'request_form_type_full_class_name' => $requestFormTypeClassNameDetails->getFullName(),
                'request_form_type_class_name' => $requestFormTypeClassNameDetails->getShortName(),
                'reset_form_type_full_class_name' => $resetFormTypeClassNameDetails->getFullName(),
                'reset_form_type_class_name' => $resetFormTypeClassNameDetails->getShortName(),
                'password_setter' => $input->getArgument('password-setter'),
                'email_getter' => $input->getArgument('email-getter')
            ]
        );

        $generator->generateClass(
            $requestClassNameDetails->getFullName(),
            'src/Resource/templates/ResetPasswordRequest.tpl.php',
            [
                'repository_class_name' => $repositoryClassNameDetails->getFullName(),
                'user_full_class_name' => $userClassNameDetails->getFullName()
            ]
        );

        $generator->generateClass(
            $repositoryClassNameDetails->getFullName(),
            'src/Resource/templates/ResetPasswordRequestRepository.tpl.php',
            [
                'request_class_full_name' => $requestClassNameDetails->getFullName(),
                'request_class_name' => $requestClassNameDetails->getShortName()
            ]
        );

        $generator->generateClass(
            $requestFormTypeClassNameDetails->getFullName(),
            'src/Resource/templates/ResetPasswordRequestFormType.tpl.php'
        );

        $generator->generateClass(
            $resetFormTypeClassNameDetails->getFullName(),
            'src/Resource/templates/ResetPasswordResetFormType.tpl.php'
        );

        $generator->generateTemplate(
            'reset_password/check_email.html.twig',
            'src/Resource/templates/twig_check_email.tpl.php',
            []
        );

        $generator->generateTemplate(
            'reset_password/email.html.twig',
            'src/Resource/templates/twig_email.tpl.php',
            []
        );

        $generator->generateTemplate(
            'reset_password/request.html.twig',
            'src/Resource/templates/twig_request.tpl.php',
            []
        );

        $generator->generateTemplate(
            'reset_password/reset.html.twig',
            'src/Resource/templates/twig_reset.tpl.php',
            []
        );

        $generator->writeChanges();
    }
}
