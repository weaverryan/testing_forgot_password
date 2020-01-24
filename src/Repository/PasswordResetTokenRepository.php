<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method PasswordResetToken|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasswordResetToken|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasswordResetToken[]    findAll()
 * @method PasswordResetToken[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    public function findNonExpiredForUser(User $user): array
    {
        // We calculate the oldest datetime a valid token could have generated at
        $tokenLifetime = new \DateInterval(sprintf('PT%sH', PasswordResetToken::LIFETIME_HOURS));
        $minDateTime = (new \DateTimeImmutable('now'))->sub($tokenLifetime);

        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.requestedAt >= :minDateTime')
            ->setParameters([
                'minDateTime' => $minDateTime,
                'user' => $user,
            ])
            ->getQuery()
            ->getResult()
        ;
    }
}
