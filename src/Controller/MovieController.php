<?php

namespace App\Controller;

use App\Service\MovieApiService;
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
    private MovieApiService $movieApiService;

    public function __construct(MovieApiService $movieApiService)
    {
        $this->movieApiService = $movieApiService;
    }

    #[Route('/', name: 'movie_index')]
    public function index(MovieRepository $movieRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $sortBy = $request->query->get('sort', 'createdAt');
        $query = $movieRepository->findAllSorted($sortBy);
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
    
        return $this->render('movie/index.html.twig', [
            'movies' => $pagination,
            'user' => $this->getUser(),
            'sort' => $sortBy,
        ]);
    }

    #[Route('/movies/search', name: 'movie_search', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function search(Request $request): Response
    {
        $query = $request->query->get('query', '');
        $page = $request->query->getInt('page', 1);

        if ($query) {
            try {
                $movies = $this->movieApiService->searchMovies($query, $page);
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred: ' . $e->getMessage());
                $movies = [];
            }

            return $this->render('movie/search.html.twig', [
                'movies' => $movies['results'] ?? [],
                'query' => $query,
                'page' => $page,
                'total_pages' => $movies['total_pages'] ?? 1,
            ]);
        }

        return $this->render('movie/search.html.twig', [
            'movies' => [],
            'query' => $query,
        ]);
    }

    #[Route('/movies/create', name: 'movie_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request, MovieRepository $movieRepository, EntityManagerInterface $entityManager): Response
    {
        $title = $request->request->get('title');
        $year = $request->request->get('year');
        $description = $request->request->get('description');

        if ($title && $description) {
            $fullTitle = $title . ' (' . $year . ')';
            $existingMovie = $movieRepository->findOneBy(['title' => $fullTitle, 'user' => $this->getUser()]);

            if ($existingMovie) {
                $this->addFlash('error', 'Movie Already exists.');
                return $this->redirectToRoute('movie_search');
            }

            $movie = new Movie();
            $movie->setTitle($fullTitle);
            $movie->setDescription($description);
            $movie->setCreatedAt(new \DateTime());
            $movie->setUser($this->getUser());

            $entityManager->persist($movie);
            $entityManager->flush();

            $this->addFlash('success', 'Movie added successfully.');

            return $this->redirectToRoute('movie_index');
        }

        $this->addFlash('error', 'Invalid movie data.');

        return $this->redirectToRoute('movie_search');
    }

    #[Route('/react', name: 'movie_react', methods: ['POST'])]
    public function react(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $movieId = $request->request->get('movie_id');
        $reactionType = $request->request->get('reaction');



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

        if ($movie->getUser()->getId() === $user->getId()) {
            $this->addFlash('error', 'You cannot react to your own movie.');
            return $this->redirectToRoute('movie_index');
        }

        $reactionRepository = $entityManager->getRepository(MovieReaction::class);
        $existingReaction = $reactionRepository->findOneBy([
            'movie' => $movie,
            'user' => $user
        ]);

        if ($existingReaction) {
            $entityManager->remove($existingReaction);
        }

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
