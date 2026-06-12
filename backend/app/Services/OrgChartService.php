<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OrgChartNode;
use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lógica del organigrama por centro de trabajo.
 */
final class OrgChartService
{
    /**
     * Árbol del centro: nodos raíz con sus descendientes anidados.
     *
     * @return Collection<int, OrgChartNode>
     */
    public function tree(WorkCenter $workCenter): Collection
    {
        return OrgChartNode::where('work_center_id', $workCenter->id)
            ->whereNull('parent_id')
            ->with(['employee', 'children.employee', 'children.children.employee'])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OrgChartNode
    {
        return OrgChartNode::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OrgChartNode $node, array $data): OrgChartNode
    {
        $node->update($data);

        return $node;
    }

    public function delete(OrgChartNode $node): void
    {
        $node->delete();
    }

    public function setNotifications(OrgChartNode $node, bool $value): OrgChartNode
    {
        $node->update(['receives_notifications' => $value]);

        return $node;
    }
}
