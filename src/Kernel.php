<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Toutes les dates de l'application (créneaux, inscriptions, mails) sont en heure de Paris,
        // quel que soit le fuseau configuré sur le serveur
        date_default_timezone_set('Europe/Paris');

        parent::boot();
    }
}
