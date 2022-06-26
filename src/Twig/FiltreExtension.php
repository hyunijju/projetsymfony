<?php 

namespace App\Twig ;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FiltreExtension extends AbstractExtension{

    public function getFilters(){

        // <td class="text-end align-middle">{{vehicule.prixJournalier | deviseFr}}</td>
        return [
            new TwigFilter("deviseFr" , [$this , "deviseFr"])

        ];
    }

    public function deviseFr(float $prix) :string {

        return number_format($prix , 0 , "," , " ") . " €";

    }


}