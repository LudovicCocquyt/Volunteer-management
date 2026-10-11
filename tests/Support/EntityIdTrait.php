<?php

namespace App\Tests\Support;

/**
 * Les entités n'ont pas de setId() : en test unitaire (sans base), on fixe l'id par réflexion.
 */
trait EntityIdTrait
{
    private static function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}
