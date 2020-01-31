<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use SymfonyCasts\Bundle\ResetPassword\Model\PasswordResetRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\PasswordResetRequestTrait;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PasswordResetRequestRepository")
 */
class PasswordResetRequest implements PasswordResetRequestInterface
{
    use PasswordResetRequestTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     */
    private $user;

    public function getUser(): User
    {
        return $this->user;
    }
}
