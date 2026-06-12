<?php

declare(strict_types=1);

namespace App\Http\Requests\OrgChart;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrgChartNodeRequest extends FormRequest
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
            'work_center_id' => ['required', 'uuid', 'exists:work_centers,id'],
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'parent_id' => ['nullable', 'uuid', 'exists:org_chart_nodes,id'],
            'receives_notifications' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
