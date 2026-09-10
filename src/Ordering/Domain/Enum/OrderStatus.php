<?php

namespace App\Ordering\Domain\Enum;

/**
 * The full lifecycle is defined now to avoid a later schema change, but only
 * DRAFT is used until EPIC 5 wires the actual state machine (transitions,
 * guards) via the Symfony Workflow component.
 */
enum OrderStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case VALIDATED = 'validated';
    case PREPARING = 'preparing';
    case SHIPPED = 'shipped';
    case INVOICED = 'invoiced';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumise',
            self::VALIDATED => 'Validée',
            self::PREPARING => 'En préparation',
            self::SHIPPED => 'Expédiée',
            self::INVOICED => 'Facturée',
            self::CANCELLED => 'Annulée',
        };
    }
}
