<?php

declare(strict_types=1);

namespace App\Services\Socios;

use App\Models\Entity;
use App\Models\Member;

/**
 * Lógica de negocio de socios: alta con número de socio autonumerado por entidad.
 */
final class MemberService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Entity $entity, array $data): Member
    {
        $data['member_number'] ??= $this->nextMemberNumber($entity);

        return $entity->members()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Member $member, array $data): Member
    {
        $member->update($data);

        return $member;
    }

    public function delete(Member $member): void
    {
        // Los pagos se borran en cascada (FK).
        $member->delete();
    }

    /**
     * Siguiente número de socio para la entidad (máximo numérico existente + 1).
     *
     * Se calcula en PHP para ser portable entre PostgreSQL (producción) y SQLite (tests).
     */
    private function nextMemberNumber(Entity $entity): string
    {
        $max = $entity->members()
            ->pluck('member_number')
            ->filter(fn ($n) => is_numeric($n))
            ->map(fn ($n) => (int) $n)
            ->max();

        return (string) (((int) $max) + 1);
    }
}
