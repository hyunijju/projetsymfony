<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Commande;
use App\Entity\Vehicule;
use App\Form\CommandeType;
use App\Form\InscriptionType;
use App\Security\AppHomeAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;


class HomeController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'home_index')]
    public function index (Request $request , ManagerRegistry $doctrine):Response{
       
        $vehicules = $doctrine->getRepository(Vehicule::class)->findAll();

        return $this->render("front/index.html.twig", [ "vehicules" => $vehicules ]);
    }
    #[Route('/login', name: 'app_register')]
    public function login (Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, AppHomeAuthenticator $authenticator){
        $user = new User();
        $form = $this->createForm(InscriptionType::class , $user);
        $user->setRoles(['ROLE_USER']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
            $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $this->em->persist($user);
            $this->em->flush();

            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );

            }
            return $this->render("front/registration.html.twig" , [
                "registrationForm" => $form->createView() ,
                "id" => $user->getId()
            ]);
            return $this->redirectToRoute("home_rent");
    }

    #[Route('/louer', name: 'home_rent')]
    public function rent (){
        
        $vehicules = $this->em->getRepository(Vehicule::class)->findAll();
        return $this->render('front/resultats.html.twig' , ["vehicules" => $vehicules]);
    }

    #[Route('/commande', name: 'home_commande')]
    public function commande(Request $request , EntityManagerInterface $em , Commande $commande = null):Response{
        
         // si /admin/commande/new => $commande = null
        // si /admin/commande/update/{id} => $commande = $em->getRepository(Commande::class)->find($id); donc $commande = { }
        if($commande === null){

            $now = new \DateTime();
            // $now->add(new \DateInterval("PT1H"));
            // $now->format("Y-m-d H:i");

            $tomorrow = new \DateTime();
            // $tomorrow->add(new \DateInterval("PT1H"));;
            // $tomorrow->format("Y-m-d H:i");
            $commande = new Commande();
            /* $commande->setDateHeureDepart( new DateTime())
                     ->setDateHeureFin(new DateTime()); */
        }

        $form = $this->createForm(CommandeType::class, $commande);
        

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){

            // nbjour = récupérer dt_ debut // date de fin 
            // prix journalier qui vient de le véhicule choisi 

            // multiplication = nbjour * prix journalier 
            $dt_debut = $form->get("date_heure_depart")->getData();
            $dt_fin = $form->get("date_heure_fin")->getData();
            $interval = $dt_debut->diff($dt_fin);
            $interval->format("%d");
            $nbJours = $interval->days ; 

            if($nbJours < 1){
                $this->addFlash("message" , "Une réservation doit durer au minimum 24 heures");
                //return $this->redirectToRoute("commande_new" , $request->query->all());
            }


            $listevehiculesLoues = $em->getRepository(Commande::class)->listeVehiculesLoues($dt_debut ,$dt_fin );
            $vehicule = $form->get("vehicule")->getData();
            if(in_array( $vehicule->getId() , $listevehiculesLoues)){

                $listevehiculesDisponibles = $em->getRepository(Vehicule::class)->findByVehiculesDisponibles($listevehiculesLoues );
                // $listevehiculeDisponible
                $this->addFlash("message" , "Le véhicule demandé est déjà réservé.");
                $this->addFlash("vehicules" , ["disponibles" => $listevehiculesDisponibles] );
                //return $this->redirectToRoute("commande_new" , $request->query->all());
            }

            // dd($listevehiculeLoue , $listevehiculeDisponible); 

            if(!in_array( $vehicule->getId() , $listevehiculesLoues) && $nbJours >= 1){
                $prix_journalier = $vehicule->getPrixJournalier();

                $commande->setPrixTotal($nbJours * $prix_journalier);
                $em->persist($commande);
                $em->flush();
                return $this->redirectToRoute("home_index");
                // regarder dans la base de données 
            }
           
        }

        return $this->render("front/commande.html.twig" , [
            "form" => $form->createView(),
            "id"   => $commande->getId()
        ]);
    }

}
