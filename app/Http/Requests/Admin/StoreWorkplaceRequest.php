<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Domain\OpsFortress\Workplaces\Models\Workplace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for creating a new Workplace via the admin UI.
 *
 * The `business_id` rule uses Rule::exists scoped by the user's tenant_id —
 * this prevents an admin from one tenant from creating a workplace under
 * another tenant's business by tampering with the form. The BelongsToTenant
 * trait would catch this on save (auto-stamp would set tenant_id to admin's
 * tenant, but the business would belong elsewhere — orphan reference). The
 * explicit rule gives a clean validation error instead of a 500.
 */
final class StoreWorkplaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Workplace::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_id' => [
                'required',
                'integer',
                Rule::exists('businesses', 'id')
                    ->where('tenant_id', $this->user()->tenant_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'classification' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'suburb' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:64'],
            'postcode' => ['nullable', 'string', 'max:16'],
            'country' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /**
     * Default country to AU if not provided (per existing Workplace migration default).
     *
     * @return array<string, mixed>
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('country') === null) {
            $this->merge(['country' => 'AU']);
        }
    }
}
