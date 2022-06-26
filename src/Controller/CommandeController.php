<?php

namespace App\Controller ;

use App\Entity\Commande;
use App\Entity\Vehicule;
use App\Form\CommandeType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController{

    #[Route("/admin/commande/list" , name:"commande_list")]
    public function list(EntityManagerInterface $em):Response{
        $commandes = $em->getRepository(Commande::class)->findAll();

        return $this->render("commande/list.html.twig" , compact("commandes"));
        // ["commandes" => $commandes  ]
    }

   /*  #[Route("/admin/commande/update/{id}" , name:"commande_update")]
    public function update(){
        // ..
    } */

    /* #[Route("/admin/commande/suppr/{id}" , name:"commande_suppr")]
    public function delete($id , EntityManagerInterface $em){
        $commandeAsupprimer = $em->getRepository(Commande::class)->find($id);
        if($commandeAsupprimer !== null){
            $em->remove($commandeAsupprimer);
            $em->flush();
        }
        return $this->redirectToRoute("commande_list");
    } */

    // ParamConverter

    #[Route("/admin/commande/suppr/{id}" , name:"commande_suppr")]
    public function delete(Commande $commandeASupprimer , EntityManagerInterface $em){
        
        if($commandeASupprimer !== null){
            $em->remove($commandeASupprimer);
            $em->flush();
        }
        return $this->redirectToRoute("commande_list");
    }

    /**
     * associer à une méthode plusieurs routes en même temps sur une méthode + ParamConverter 
     */

    #[Route("/admin/commande/new" , name:"commande_new")]
    #[Route("/admin/commande/update/{id}" , name:"commande_update")]
    public function new(Request $request , EntityManagerInterface $em , Commande $commande = null):Response{

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
                return $this->redirectToRoute("commande_list");
                // regarder dans la base de données 
            }
           
        }

        return $this->render("commande/new.html.twig" , [
            "form" => $form->createView(),
            "id"   => $commande->getId()
        ]);
    }

}