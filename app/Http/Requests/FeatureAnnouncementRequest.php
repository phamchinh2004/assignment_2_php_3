<?php

namespace App\Http\Requests;

use App\Models\FeatureAnnouncement;
use App\Services\AuthorizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeatureAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        $capabilities = config('authorization.capabilities');
        $permission = $this->route('feature_announcement')
            ? $capabilities['feature_announcements_update']
            : $capabilities['feature_announcements_create'];

        return app(AuthorizationService::class)->can($user, $permission);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'content' => ['required', 'string'],
            'priority' => ['required', Rule::in(FeatureAnnouncement::PRIORITIES)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'target_type' => ['required', Rule::in(FeatureAnnouncement::TARGET_TYPES)],
            'target_roles' => ['required_if:target_type,roles', 'nullable', 'array', 'min:1'],
            'target_roles.*' => ['required', 'string', Rule::in(FeatureAnnouncement::TARGET_ROLES)],
            'target_user_ids' => ['required_if:target_type,users', 'nullable', 'array', 'min:1'],
            'target_user_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->whereIn('role', [
                        \App\Models\User::ROLE_ADMIN,
                        \App\Models\User::ROLE_STAFF,
                    ])
                ),
            ],
            'action_text' => ['nullable', 'string', 'max:120', 'required_with:action_url'],
            'action_url' => [
                'nullable',
                'string',
                'max:2048',
                'required_with:action_text',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!is_string($value) || $value === '') {
                        return;
                    }

                    if (str_starts_with($value, '/') && !str_starts_with($value, '//')) {
                        return;
                    }

                    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
                    if (!filter_var($value, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
                        $fail('Liên kết phải là đường dẫn nội bộ bắt đầu bằng / hoặc URL http/https hợp lệ.');
                    }
                },
            ],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'require_reacknowledgement' => ['nullable', 'boolean'],
        ];
    }
}
