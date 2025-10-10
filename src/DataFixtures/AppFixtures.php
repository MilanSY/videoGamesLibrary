<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Editor;
use App\Entity\VideoGame;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création des éditeurs
        $sandfall = new Editor();
        $sandfall->setName('Sandfall Interactive');
        $sandfall->setCountry('France');
        $manager->persist($sandfall);

        $nintendo = new Editor();
        $nintendo->setName('Nintendo');
        $nintendo->setCountry('Japon');
        $manager->persist($nintendo);

        $ubisoft = new Editor();
        $ubisoft->setName('Ubisoft');
        $ubisoft->setCountry('France');
        $manager->persist($ubisoft);

        $cdProjekt = new Editor();
        $cdProjekt->setName('CD Projekt RED');
        $cdProjekt->setCountry('Pologne');
        $manager->persist($cdProjekt);

        // Création des catégories
        $rpg = new Category();
        $rpg->setName('RPG');
        $manager->persist($rpg);

        $action = new Category();
        $action->setName('Action');
        $manager->persist($action);

        $aventure = new Category();
        $aventure->setName('Aventure');
        $manager->persist($aventure);

        $plateforme = new Category();
        $plateforme->setName('Plateforme');
        $manager->persist($plateforme);

        $openWorld = new Category();
        $openWorld->setName('Monde Ouvert');
        $manager->persist($openWorld);

        $indie = new Category();
        $indie->setName('Indépendant');
        $manager->persist($indie);

        // Création des jeux vidéo
        $claireObscure = new VideoGame();
        $claireObscure->setTitle('Claire Obscure: Expedition 33');
        $claireObscure->setReleaseDate(new \DateTime('2025-04-24'));
        $claireObscure->setDescription('Un RPG au tour par tour inspiré de la Belle Époque française, où les joueurs explorent un monde mystérieux menacé par une entité appelée le Peintre.');
        $claireObscure->setEditor($sandfall);
        $claireObscure->addCategory($rpg);
        $claireObscure->addCategory($aventure);
        $claireObscure->addCategory($indie);
        $manager->persist($claireObscure);

        $zeldaBotW = new VideoGame();
        $zeldaBotW->setTitle('The Legend of Zelda: Breath of the Wild');
        $zeldaBotW->setReleaseDate(new \DateTime('2017-03-03'));
        $zeldaBotW->setDescription('Un jeu d\'action-aventure en monde ouvert où Link doit sauver Hyrule de Calamity Ganon.');
        $zeldaBotW->setEditor($nintendo);
        $zeldaBotW->addCategory($action);
        $zeldaBotW->addCategory($aventure);
        $zeldaBotW->addCategory($openWorld);
        $manager->persist($zeldaBotW);

        $assassinsCreed = new VideoGame();
        $assassinsCreed->setTitle('Assassin\'s Creed Valhalla');
        $assassinsCreed->setReleaseDate(new \DateTime('2020-11-10'));
        $assassinsCreed->setDescription('Incarnez Eivor, un guerrier viking, et menez votre clan des terres désolées de Norvège vers un nouveau foyer en Angleterre.');
        $assassinsCreed->setEditor($ubisoft);
        $assassinsCreed->addCategory($action);
        $assassinsCreed->addCategory($rpg);
        $assassinsCreed->addCategory($openWorld);
        $manager->persist($assassinsCreed);

        $witcher3 = new VideoGame();
        $witcher3->setTitle('The Witcher 3: Wild Hunt');
        $witcher3->setReleaseDate(new \DateTime('2015-05-19'));
        $witcher3->setDescription('Geralt de Riv part à la recherche de sa fille adoptive dans un monde ouvert fantasy riche et immersif.');
        $witcher3->setEditor($cdProjekt);
        $witcher3->addCategory($rpg);
        $witcher3->addCategory($action);
        $witcher3->addCategory($openWorld);
        $manager->persist($witcher3);

        $marioOdyssey = new VideoGame();
        $marioOdyssey->setTitle('Super Mario Odyssey');
        $marioOdyssey->setReleaseDate(new \DateTime('2017-10-27'));
        $marioOdyssey->setDescription('Mario explore de nouveaux royaumes avec son compagnon Cappy pour sauver la Princesse Peach de Bowser.');
        $marioOdyssey->setEditor($nintendo);
        $marioOdyssey->addCategory($plateforme);
        $marioOdyssey->addCategory($aventure);
        $manager->persist($marioOdyssey);

        // Création des utilisateurs
        $userNormal = new User();
        $userNormal->setEmail('user@example.com');
        $userNormal->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($userNormal, 'milanscroll');
        $userNormal->setPassword($hashedPassword);
        $manager->persist($userNormal);

        $userAdmin = new User();
        $userAdmin->setEmail('admin@example.com');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $hashedPasswordAdmin = $this->passwordHasher->hashPassword($userAdmin, 'milanscroll');
        $userAdmin->setPassword($hashedPasswordAdmin);
        $manager->persist($userAdmin);

        $manager->flush();
    }
}
