<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Round;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @Route("/profil")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_profil")
     */
    public function index(GameRepository $gameRepository): Response
    {
        $games[]=$gameRepository->findBy(['user1' => $this->getUser()->getId(), 'ended' => null], ['created' => 'DESC']);
        $games[]=$gameRepository->findBy(['user2' => $this->getUser()->getId(), 'ended' => null], ['created' => 'DESC']);
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
            'games' => $games
        ]);
    }

    /**
     * @Route("/delete_partie/{game}", name="user_delete_partie")
     */
    public function supprimerPartie(EntityManagerInterface $entityManager, Game $game) :Response
    {
        $entityManager->remove($game);
        $entityManager->flush();

        return $this->redirectToRoute('user_profil');
    }
}