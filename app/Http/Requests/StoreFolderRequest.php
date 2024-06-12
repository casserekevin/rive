<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\File;
use Illuminate\Support\Facades\Auth;

class StoreFolderRequest extends ParentIdBaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = array_merge(parent::rules(),
        [
            'name' => [
                'required',
                'unique:files',
                Rule::unique(File::class, 'name')
                ->where('created_by', Auth::id())
                ->where('parent_id', $this->input('parent_id'))
                ->whereNull('deleted_at')
            ]
        ]);
        return $rules;
    }

    public function messages()
    {
        return [
            'name.unique' => 'Folder ":input" already exists'
        ];
    }
}
