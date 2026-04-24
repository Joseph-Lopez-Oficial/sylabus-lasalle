<?php

namespace App\Http\Requests\Admin;

use App\Models\ProblematicNucleus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $nucleusId = $this->input('problematic_nucleus_id');
        $programId = ProblematicNucleus::find($nucleusId)?->program_id;

        $uniqueCode = Rule::unique('competencies', 'code')->where(function ($query) use ($programId) {
            $query->whereIn('problematic_nucleus_id', function ($sub) use ($programId) {
                $sub->select('id')->from('problematic_nuclei')->where('program_id', $programId);
            });
        });

        return [
            'code' => ['required', 'string', 'max:10', 'regex:/^C\d+$/', $uniqueCode],
            'problematic_nucleus_id' => ['required', 'integer', 'exists:problematic_nuclei,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código de la competencia es obligatorio.',
            'code.regex' => 'El código debe tener el formato C seguido de números (ej. C1, C15).',
            'code.unique' => 'Ya existe una competencia con ese código en el mismo programa.',
            'problematic_nucleus_id.required' => 'Debe seleccionar un núcleo problémico.',
            'problematic_nucleus_id.exists' => 'El núcleo problémico seleccionado no existe.',
            'name.required' => 'El nombre de la competencia es obligatorio.',
        ];
    }
}
