<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\MovieReaction;
use App\Form\MovieType;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Knp\Component\Pager\PaginatorInterface;

class MovieController extends AbstractController
{
    #[Route('/', name: 'movie_index')]
    public function index(MovieRepository $movieRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $movieRepository->createQueryBuilder('m')
            ->leftJoin('m.reactions', 'r')
            ->addSelect('r');

        $query = $queryBuilder->getQuery();
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), 
            10 
        );

        return $this->render('movie/index.html.twig', [
            'movies' => $pagination,
            'user' => $this->getUser()
        ]);
    }

    #[Route('/movies/create', name: 'movie_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $movie = new Movie();
        $form = $this->createForm(MovieType::class, $movie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $movie->setCreatedAt(new \DateTime());
            $movie->setUser($this->getUser());

            $em->persist($movie);
            $em->flush();

            return $this->redirectToRoute('movie_index');
        }

        return $this->render('movie/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/react', name: 'movie_react', methods: ['POST'])]
    public function react(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $movieId = $request->request->get('movie_id');
        $reactionType = $request->request->get('reaction');
        
        // Ensure the reaction type is valid
        if (!in_array($reactionType, ['like', 'dislike'])) {
            $this->addFlash('error', 'Invalid reaction type.');
            return $this->redirectToRoute('movie_index');
        }

        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'You need to be logged in to react.');
            return $this->redirectToRoute('app_login');
        }

        $movieRepository = $entityManager->getRepository(Movie::class);
        $movie = $movieRepository->find($movieId);

        if (!$movie) {
            $this->addFlash('error', 'Movie not found.');
            return $this->redirectToRoute('movie_index');
        }

        // Check if the user has already reacted to this movie
        $reactionRepository = $entityManager->getRepository(MovieReaction::class);
        $existingReaction = $reactionRepository->findOneBy([
            'movie' => $movie,
            'user' => $user
        ]);

        if ($existingReaction) {
            $entityManager->remove($existingReaction);
        }

        // Add the new reaction
        $reaction = new MovieReaction();
        $reaction->setMovie($movie);
        $reaction->setUser($user);
        $reaction->setType($reactionType);

        $entityManager->persist($reaction);
        $entityManager->flush();

        $this->addFlash('success', 'Your reaction has been recorded.');

        return $this->redirectToRoute('movie_index');
    }
}
