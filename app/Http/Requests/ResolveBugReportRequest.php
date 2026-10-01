<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Http\FormRequest;

class ResolveBugReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user
            && $user->role === User::ROLE_OWNER
            && app(AuthorizationService::class)->can(
                $user,
                config('authorization.capabilities.bug_reports_resolve')
            );
    }

    public function rules(): array
    {
        return [
            'resolved_note' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
