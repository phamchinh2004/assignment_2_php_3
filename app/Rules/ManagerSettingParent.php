<?php

namespace App\Rules;

use App\Models\Manager_setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ManagerSettingParent implements ValidationRule
{
    public function __construct(private ?Manager_setting $setting = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = Manager_setting::find($value);
        if (! $parent || $parent->parent_manager_setting_id !== null) {
            $fail('Chức năng cha phải tồn tại và là chức năng cấp gốc.');
        } elseif ($this->setting && (int) $value === (int) $this->setting->id) {
            $fail('Không thể chọn chính chức năng này làm cha.');
        } elseif ($this->setting && $this->setting->children()->exists()) {
            $fail('Chức năng đang có chức năng con không thể chuyển thành chức năng con.');
        }
    }
}
