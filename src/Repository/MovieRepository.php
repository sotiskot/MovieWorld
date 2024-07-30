<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function findAllSorted($sortBy, $userId)
    {
        $qb = $this->createQueryBuilder("m")
            ->leftJoin("m.reactions", "r")
            ->addSelect("COUNT(r.id) AS HIDDEN num_reactions");

        if ($userId) {
            $qb->where('m.user = :user')
                ->setParameter('user', $userId);
        }

        switch ($sortBy) {
            case "likesDesc":
                $qb->addSelect("SUM(CASE WHEN r.type = 'like' THEN 1 ELSE 0 END) AS HIDDEN num_likes")
                    ->groupBy("m.id")
                    ->orderBy("num_likes", "DESC");
                break;
            case "likesAsc":
                $qb->addSelect("SUM(CASE WHEN r.type = 'like' THEN 1 ELSE 0 END) AS HIDDEN num_likes")
                    ->groupBy("m.id")
                    ->orderBy("num_likes", "ASC");
                break;
            case "dislikesDesc":
                $qb->addSelect("SUM(CASE WHEN r.type = 'dislike' THEN 1 ELSE 0 END) AS HIDDEN num_dislikes")
                    ->groupBy("m.id")
                    ->orderBy("num_dislikes", "DESC");
                break;
            case "dislikesAsc":
                $qb->addSelect("SUM(CASE WHEN r.type = 'dislike' THEN 1 ELSE 0 END) AS HIDDEN num_dislikes")
                    ->groupBy("m.id")
                    ->orderBy("num_dislikes", "ASC");
                break;
            case "createdAtAsc":
                $qb->groupBy("m.id")
                    ->orderBy("m.createdAt", "ASC");
                break;
            case "createdAt":
            default:
                $qb->groupBy("m.id")
                    ->orderBy("m.createdAt", "DESC");
                break;
        }
    
        return $qb->getQuery()->getResult();
    }
}
