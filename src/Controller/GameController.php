<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Round;
use App\Repository\CardRepository;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/jeu")
 */
class GameController extends AbstractController
{
    /**
     * @Route("/new-game", name="new_game")
     */
    public function newGame(
        UserRepository $userRepository,
        GameRepository $gameRepository
    ): Response {
        $users = $userRepository->findAll();
        $games = $gameRepository->findBy(['public' => 1, 'ended' => null], ['created' => 'DESC']);
        return $this->render('game/index.html.twig', [
            'users' => $users,
            'games' => $games
        ]);
    }

    /**
     * @Route("/create-game", name="create_game")
     */
    public function createGame(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CardRepository $cardRepository
    ): Response {
        $user1 = $this->getUser();
        $user2 = $userRepository->find($request->request->get('user2'));

        if ($user1 !== $user2) {
            $game = new Game();
            $game->setUser1($user1);
            $game->setUser2($user2);
            $game->setCreated(new \DateTime('now'));
            $game->setPlayer(1);
            $game->setPublic(0);

            $entityManager->persist($game);

            $set = new Round();
            $set->setGame($game);
            $set->setCreated(new \DateTime('now'));
            $set->setSetNumber(1);

            $cards = $cardRepository->findAll();
            $tCards = [];
            foreach ($cards as $card) {
                $tCards[$card->getId()] = $card;
            }
            shuffle($tCards);
            $carte = array_pop($tCards);
            $set->setRemovedCard($carte->getId());

            $tMainJ1 = [];
            $tMainJ2 = [];
            for ($i = 0; $i < 6; $i++) {
                //on distribue 6 cartes aux deux joueurs
                $carte = array_pop($tCards);
                $tMainJ1[] = $carte->getId();
                $carte = array_pop($tCards);
                $tMainJ2[] = $carte->getId();
            }
            $set->setUser1HandCards($tMainJ1);
            $set->setUser2HandCards($tMainJ2);

            $tPioche = [];

            foreach ($tCards as $card) {
                $carte = array_pop($tCards);
                $tPioche[] = $carte->getId();
            }
            $set->setPioche($tPioche);
            $set->setUser1Action([
                'SECRET' => false,
                'DEPOT' => false,
                'OFFRE' => false,
                'ECHANGE' => false
            ]);

            $set->setUser2Action([
                'SECRET' => false,
                'DEPOT' => false,
                'OFFRE' => false,
                'ECHANGE' => false
            ]);

            $set->setBoard([
                'EMPL1' => ['N'],
                'EMPL2' => ['N'],
                'EMPL3' => ['N'],
                'EMPL4' => ['N'],
                'EMPL5' => ['N'],
                'EMPL6' => ['N'],
                'EMPL7' => ['N']
            ]);
            $entityManager->persist($set);
            $entityManager->flush();

            return $this->redirectToRoute('show_game', [
                'game' => $game->getId()
            ]);
        } else {
            return $this->redirectToRoute('new_game');
        }
    }

    /**
     * @Route("/show-game/{game}", name="show_game")
     */
    public function showGame(
        Game $game
    ): Response {

        return $this->render('game/show_game.html.twig', [
            'game' => $game
        ]);
    }

    /**
     * @Route("/get-tour-game/{game}", name="get_tour")
     */
    public function getTour(
        Game $game
    ): Response {
        if ($this->getUser()->getId() === $game->getUser1()->getId() && $game->getPlayer() === 1) {
            return $this->json(true);
        }

        if ($this->getUser()->getId() === $game->getUser2()->getId() && $game->getPlayer() === 2) {
            return $this->json(true);
        }

        return $this->json( false);
    }

    /**
     * @param Game $game
     * @route("/refresh/{game}", name="refresh_plateau_game")
     */
    public function refreshPlateauGame(CardRepository $cardRepository, Game $game)
    {
        $cards = $cardRepository->findAll();
        $tCards = [];
        foreach ($cards as $card) {
            $tCards[$card->getId()] = $card;
        }

        if ($this->getUser()->getId() === $game->getUser1()->getId()) {
            $moi['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser1Action();
            $moi['board'] = $game->getRounds()[0]->getUser1BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser2Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser2BoardCards();
        } elseif ($this->getUser()->getId() === $game->getUser2()->getId()) {
            $moi['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser2Action();
            $moi['board'] = $game->getRounds()[0]->getUser2BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser1Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser1BoardCards();
        } else {
            return $this->redirectToRoute('user_profil');
        }

        return $this->render('game/plateau_game.html.twig', [
            'game' => $game,
            'set' => $game->getRounds()[0],
            'cards' => $tCards,
            'moi' => $moi,
            'adversaire' => $adversaire
        ]);
    }

    /**
     * @Route("/action-game/{game}", name="action_game")
     */
    public function actionGame(EntityManagerInterface $entityManager, Request $request, Game $game)
    {
        $action = $request->request->get('action');
        $user = $this->getUser();
        $round = $game->getRounds()[0]; //a gérer selon le round en cours

        if ($game->getUser1()->getId() === $user->getId()) {
            $joueur = 1;
        } elseif ($game->getUser2()->getId() === $user->getId()) {
            $joueur = 2;
        } else {
            /// On a un problème... On pourrait rediriger vers une page d'erreur.
        }

        switch ($action) {
            case 'secret':
                $carte = $request->request->get('carte');
                if ($joueur === 1) {
                    $actions = $round->getUser1Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser1HandCards($main);
                } elseif ($joueur === 2) {
                    $actions = $round->getUser2Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser2HandCards($main);
                }
                break;
            case 'depot':
                $carte1 = $request->request->get('carte1');
                $carte2 = $request->request->get('carte2');
                if ($joueur === 1){
                    $actions = $round->getUser1Action();
                    $actions['DEPOT'] = [$carte1, $carte2];
                    $round->setUser1Action($actions);
                    $main = $round->getUser1HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    unset($main[$indexCarte1], $main[$indexCarte2]);
                    $round->setUser1HandCards($main);
                } elseif ($joueur === 2){
                    $actions = $round->getUser2Action();
                    $actions['DEPOT'] = [$carte1, $carte2];
                    $round->setUser2Action($actions);
                    $main = $round->getUser2HandCards();
                    $indexCarte1 = array_search($carte1, $main);
                    $indexCarte2 = array_search($carte2, $main);
                    unset($main[$indexCarte1], $main[$indexCarte2]);
                    $round->setUser2HandCards($main);
                }
                break;
            case 'offre':
                if ($joueur === 1){

                } elseif ($joueur === 2){

                }
                break;
            case 'echange':
                if ($joueur === 1){

                } elseif ($joueur === 2){

                }
                break;
        }

        $entityManager->flush();

        return $this->json(true);
    }

    /**
     * @Route("/change-tour-game/{game}", name="change_tour")
     */
    public function changeTour(
        EntityManagerInterface $entityManager,
        Request $request,
        Game $game
    ) :Response{
        $event = $request->request->get('event');
        $joueur1 = 1;
        $joueur2 = 2;
        if ($event == 'clicked'){
            if ($game->getPlayer() == $joueur1){
                $game->setPlayer($joueur2);
            } elseif ($game->getPlayer() == $joueur2){
                $game->setPlayer($joueur1);
            }
        }
        $entityManager->flush();
        return $this->json(true);
    }
}