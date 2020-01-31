<?php

namespace App\Repository;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Persistence\PasswordResetRequestRepositoryInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\PasswordResetRequestRepositoryTrait;

/**
 * @method PasswordResetRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasswordResetRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasswordResetRequest[]    findAll()
 * @method PasswordResetRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasswordResetRequestRepository extends ServiceEntityRepository implements PasswordResetRequestRepositoryInterface
{
    use PasswordResetRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetRequest::class);
    }
}
