<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\City;
use App\Models\Region;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() === null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $providerRegistration = in_array($this->input('account_type'), ['practitioner', 'institution'], true);

        return [
            'account_type' => ['required', Rule::in(['patient', 'practitioner', 'institution'])],
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'preferred_locale' => ['nullable', 'string', 'max:16', Rule::exists('languages', 'code')],
            'category_id' => [Rule::requiredIf($providerRegistration), 'nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'city_id' => [Rule::requiredIf($providerRegistration), 'nullable', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'region_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('is_active', true)],
            'phone' => [Rule::requiredIf($providerRegistration), 'nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'visibility' => ['nullable', 'array:name,contact_email,phone,address,description'],
            'visibility.name' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.contact_email' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.phone' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.address' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'visibility.description' => ['nullable', Rule::in(['public', 'registered', 'private'])],
            'documents' => [Rule::requiredIf($providerRegistration), 'array', 'min:1', 'max:5'],
            'documents.*' => [File::types(['pdf', 'jpg', 'jpeg', 'png'])->max('5mb')],
        ];
    }

    public function after(): array
    {
        if (! in_array($this->input('account_type'), ['practitioner', 'institution'], true)) {
            return [];
        }

        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['category_id', 'city_id', 'region_id'])) {
                return;
            }

            $city = City::query()->find($this->integer('city_id'));
            $region = $this->integer('region_id') > 0
                ? Region::query()->find($this->integer('region_id'))
                : null;
            $category = Category::query()->with('parent')->find($this->integer('category_id'));

            if ($region !== null && $region->city_id !== $city?->id) {
                $validator->errors()->add('region_id', 'Select a region that belongs to the chosen city.');
            }

            $allowedKinds = $this->input('account_type') === 'institution'
                ? ['hospital']
                : ['doctor', 'specialist'];

            if (
                $category === null
                || ! $category->is_active
                || $category->parent === null
                || $category->parent->parent_id !== null
                || ! in_array($category->kind, $allowedKinds, true)
                || $category->parent->kind !== $category->kind
            ) {
                $validator->errors()->add('category_id', 'Select an active second-level category for this account type.');
            }
        }];
    }
}
