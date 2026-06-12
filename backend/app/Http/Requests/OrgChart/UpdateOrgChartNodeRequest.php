<?php

declare(strict_types=1);

namespace App\Http\Requests\OrgChart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrgChartNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'uuid', 'exists:org_chart_nodes,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'receives_notifications' => ['boolean'],
        ];
    }
}
