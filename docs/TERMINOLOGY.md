# Domain Terminology

This file is the canonical terminology reference for coding agents working in this repository.

## Rules for agents

- Use the canonical terms and identifiers documented here for new code.
- Keep legacy names only where this file explicitly marks them as backward-compatibility identifiers.
- Do not infer a business meaning from a field name alone. Terms under **Needs confirmation** are intentionally unresolved.
- `Order` and `Frozen Order` are different concepts. Do not use them interchangeably.
- Distribution `spin` terminology and Lucky Wheel `spin` terminology are different flows.

## Verified terminology

### High-Value Order (HVO)

- Canonical English: **High-Value Order**.
- Canonical Vietnamese: **Đơn hàng giá trị cao**.
- Abbreviation: **HVO**.
- Meaning: a distributed `Frozen_order` whose effective order value is overridden by a non-null `custom_price`.
- Current discriminator: `frozen_orders.custom_price IS NOT NULL`.
- An active/uncompleted HVO check may also require `is_frozen = true` and `spun = true`; see `User::hasHighValueOrders()`.
- Preferred identifiers for new code: `highValueOrder`, `high_value_order`, `high-value-order`, `HVO`/`hvo`, following the local naming convention.
- Deprecated terminology for this concept: **Đơn đặc biệt**, `specialOrder`, `special_order`, `special-order`, `isSpecial`, and equivalent "special order" naming.
- Backward compatibility only:
  - API field `is_order_special` maps to canonical `is_high_value_order`.
  - Artisan alias `orders:check-special-reminder` maps to canonical `orders:check-hvo-reminder`.
- Evidence: `app/Models/User.php`, `app/Http/Controllers/User/HomeController.php`, `app/Http/Controllers/User/OrderController.php`, `resources/js/user/distribution.js`, `app/Console/Commands/CheckHighValueOrdersReminder.php`.

### Normal Order

- Canonical English: **Normal Order**.
- Vietnamese: **Đơn hàng bình thường**.
- Meaning in the distribution lifecycle: a distributed `Frozen_order` with `custom_price = null`.
- Confirmation uses the user's normal `balance` path; HVO confirmation uses the `frozen_balance` path.
- Evidence: `app/Http/Controllers/User/OrderController.php`, `app/Http/Controllers/User/HomeController.php`, `resources/js/user/order.js`.

### Order

- Code entity: `App\Models\Order`, table `orders`.
- Meaning: the reusable/source order definition used by the distribution flow. It contains order index/code, product data, quantity/price, commission percentage, rank, customer/payment data, partner, and API data.
- Relation: an `Order` can be used to create user-specific `Frozen_order` records.
- Do not treat mutable `Order` data as verified historical data for an already-created frozen order when a snapshot field exists.
- Evidence: `app/Models/Order.php`, `database/migrations/2014_10_12_000001_create_orders_table.php`, `app/Models/Frozen_order.php`.

### Frozen Order

- Code entity: `App\Models\Frozen_order`, table `frozen_orders`.
- Meaning: a user-specific order lifecycle record created/assigned from an `Order` during distribution or admin assignment.
- It stores distribution state (`is_frozen`, `spun`), lifecycle status, commission state, optional HVO `custom_price`, historical snapshot fields, and settlement fields.
- Both normal orders and HVOs use `Frozen_order`; `Frozen Order` is therefore not a synonym for HVO.
- Evidence: `app/Models/Frozen_order.php`, `database/migrations/2014_10_12_000011_create_frozen_orders_table.php`, `app/Http/Controllers/User/HomeController.php`.

### Order Distribution

- Canonical English: **Order Distribution**.
- Common identifiers: `distribution`, `check_frozen_order`, `distribution_today`.
- Meaning: the user flow that advances order progress for the current rank and creates/receives the corresponding `Frozen_order`.
- `User_spin_progress.current_spin` determines the current position; `Rank.spin_count` is the configured number of distribution spins/orders for that rank.
- Do not use this term to mean general shipping/logistics.
- Evidence: `app/Http/Controllers/User/HomeController.php`, `resources/js/user/distribution.js`, `resources/views/user/distribution.blade.php`, `app/Models/User_spin_progress.php`.

### Rank

- Code entity: `App\Models\Rank`, table `ranks`.
- Vietnamese domain meaning: **Cấp độ**.
- Meaning: the level associated with a user and its orders. Rank configuration includes commission percentage, upgrade fee, distribution `spin_count`, rank value, and withdrawal limits.
- Relation: `Order.rank_id` assigns orders to a rank; `User.rank_id` assigns the user's current rank.
- Evidence: `app/Models/Rank.php`, `database/migrations/2013_04_27_053244_create_ranks_table.php`, `database/migrations/2014_10_12_000001_create_orders_table.php`.

### Distribution Spin / User Spin Progress

- Code entity: `App\Models\User_spin_progress`, table `user_spin_progresses`.
- Main field: `current_spin`.
- Meaning: the user's current position in the order-distribution sequence for a rank.
- `Rank.spin_count` is the configured daily number/limit used by this distribution flow.
- This is separate from **Lucky Wheel Spin**.
- Evidence: `app/Models/User_spin_progress.php`, `database/migrations/2014_10_12_000002_create_user_spin_progress_table.php`, `app/Http/Controllers/User/HomeController.php`.

### Lucky Wheel Spin

- Code entity: `App\Models\LuckyWheelSpin`, table `lucky_wheel_spins`.
- Meaning: a Lucky Wheel prize spin. The record stores `user_id`, `prize`, and `spin_date` and enforces one record per user/date.
- This is separate from order-distribution `current_spin` / `spin_count`.
- Evidence: `app/Models/LuckyWheelSpin.php`, `database/migrations/2014_10_12_000010_create_lucky_wheel_spins_table.php`.

### Balance

- Code field: `users.balance`.
- Meaning: the user's normal account balance used by the normal-order confirmation path and as the withdrawable balance in the current withdrawal flow.
- Evidence: `database/migrations/2014_10_12_000000_create_users_table.php`, `app/Http/Controllers/User/OrderController.php`, `app/Http/Controllers/User/HomeController.php`.

### Frozen Balance

- Code field: `users.frozen_balance`.
- Vietnamese: **Số dư đóng băng**.
- Meaning: balance separated for the HVO/frozen-order flow. When a new HVO is received, the current normal balance is moved into `frozen_balance` and normal `balance` becomes zero.
- HVO confirmation checks/deducts from the frozen-balance path; it is not the normal withdrawal source.
- Evidence: `database/migrations/2014_10_12_000000_create_users_table.php`, `app/Http/Controllers/User/HomeController.php`, `app/Http/Controllers/User/OrderController.php`.

### Commission

- Vietnamese: **Hoa hồng**.
- Common identifiers: `commission_percentage`, `snapshot_commission_amount`, `snapshot_commission_value`, `commission_paid`.
- Meaning: the percentage/value credited from an order after the applicable order lifecycle completes.
- `commission_paid` records whether the commission has already been credited for a `Frozen_order`.
- Evidence: `app/Models/Rank.php`, `app/Models/Order.php`, `app/Models/Frozen_order.php`, `app/Jobs/CompleteOrder.php`.

### Order Snapshot

- Common identifiers: `snapshot_*`, `snapshotFromOrder()`, `snapshot_order_value`, `snapshot_commission_value`.
- Meaning: creation-time historical data stored on `Frozen_order` so later display and financial calculations do not depend on mutable `Order` data.
- Snapshot fields include order identity/display data, quantity/unit price/order amount, commission amount, payment data, partner name, and API data.
- `snapshot_state` values:
  - `legacy`: no snapshot identity/financial fields are present.
  - `incomplete`: required snapshot fields are missing.
  - `invalid`: snapshot fields exist but fail internal consistency checks.
  - `complete`: required fields exist and pass current consistency checks.
- Evidence: `app/Models/Frozen_order.php`, `database/migrations/2026_09_20_000001_add_order_snapshot_to_frozen_orders_table.php`, `app/Services/FrozenOrderSnapshotService.php`.

### Snapshot Fallback / Restored Current

- Common identifiers: `uses_snapshot_fallback`, `snapshot_source = restored_current`.
- Meaning: compatibility/audit state for historical frozen orders whose snapshot is incomplete and whose non-financial display fields may be supplied or restored from the current related `Order`.
- `restored_current` is fallback data from the current order and must not be described as verified historical data.
- Existing snapshot values are not overwritten by the restore flow.
- Evidence: `app/Models/Frozen_order.php`, `app/Services/FrozenOrderSnapshotService.php`, `app/Http/Controllers/Admin/OrderDistributionController.php`.

### Settlement / Settlement Snapshot

- Common identifiers: `settled_order_amount`, `settled_commission_amount`, `settled_penalty_amount`, `settled_refund_amount`, `settled_balance_destination`, `settled_at`.
- Meaning: final stored financial outcome for a `Frozen_order` after settlement.
- `FrozenOrderSettlementService` prefers the stored settlement snapshot and can reconstruct an exact settlement from `Transaction_history` only when the transaction evidence is unambiguous.
- Settlement states used by the service: `pending`, `not_required`, `settled`, `needs_review`.
- Evidence: `app/Services/FrozenOrderSettlementService.php`, `database/migrations/2026_09_20_000003_add_settlement_snapshot_to_frozen_orders_table.php`.

### Order Status

- Canonical lifecycle values: `pending`, `confirmed`, `preparing`, `transit`, `shipping`, `delivered`, `completed`, `cancelled`.
- `Frozen_order.status` stores the current lifecycle status.
- `OrderStatusService` changes status/timestamps and records status history.
- Evidence: `database/migrations/2014_10_12_000011_create_frozen_orders_table.php`, `database/migrations/2014_10_12_000013_create_statuses_table.php`, `app/Services/OrderStatusService.php`.

### Status Order / Order Status History

- Code entity: `App\Models\StatusOrder`, table `status_orders`.
- Meaning: a history record for a `Frozen_order` status change, including the status, notes, and optional `changed_by` user.
- Do not confuse this history record with the current `Frozen_order.status` field.
- Evidence: `app/Models/StatusOrder.php`, `database/migrations/2014_10_12_000013_create_statuses_table.php`.

### Order Status Timing

- Code entity: `App\Models\OrderStatusTiming`, table `order_status_timings`.
- Meaning: configurable minimum/maximum transition timing for a `from_status` -> `to_status` pair.
- Supported units in the model: minutes, hours, days.
- Evidence: `app/Models/OrderStatusTiming.php`, `database/migrations/2014_10_12_000014_create_order_status_timings_table.php`.

### Partner

- Code entity: `App\Models\Partner`, table `partners`.
- Vietnamese: **Đối tác**.
- Meaning: an associated sales platform/business partner with name, image, and link. `Order.partner_id` points to this entity; order schema comments use examples such as Shopee, Lazada, and TikTok Shop.
- Legacy compatibility naming: historical `Frozen_order.platform` data maps to canonical snapshot/display partner naming (`snapshot_partner_name`, `display_partner_name`).
- Prefer `partner` naming in new frozen-order code instead of reintroducing `platform` as a stored domain field.
- Evidence: `app/Models/Partner.php`, `database/migrations/2013_04_27_053245_create_partners_table.php`, `database/migrations/2014_10_12_000001_create_orders_table.php`, `database/migrations/2026_09_20_000005_standardize_frozen_orders_platform_field.php`, `app/Models/Frozen_order.php`.

### Wallet Balance History

- Code entity: `App\Models\Wallet_balance_history`, table `wallet_balance_histories`.
- Meaning: operational deposit/withdraw transaction records for a user's wallet balance.
- `type`: `deposit` | `withdraw`.
- `status`: `processing` | `completed` | `cancelled`.
- `transaction_type`: `normal` | `bonus` | `virtual_withdraw`.
- Evidence: `app/Models/Wallet_balance_history.php`, `database/migrations/2014_10_12_000008_create_wallet_balance_histories_table.php`.

### Transaction History

- Code entity: `App\Models\Transaction_history`, table `transaction_histories`.
- Meaning: financial movement history used for account/order financial records and, when unambiguous, settlement reconstruction.
- `type`: `deposit` | `order` | `profit` | `withdraw` | `penalty`.
- Evidence: `app/Models/Transaction_history.php`, `database/migrations/2014_10_12_000007_create_transaction_histories_table.php`, `app/Services/FrozenOrderSettlementService.php`.

### Frozen Order Settings

- Code entity: `App\Models\FrozenOrderSetting`, table `frozen_order_settings`.
- Meaning: timing configuration for frozen/HVO processing and reminder thresholds.
- Current defaults: `processing_time_limit = 24`, `notification_1_remaining_time = 12`, `notification_2_remaining_time = 1`.
- Evidence: `app/Models/FrozenOrderSetting.php`, `database/migrations/2026_09_17_000002_create_frozen_order_settings_table.php`.

### User Role

- Canonical values: `member`, `staff`, `admin`.
- Canonical constants: `User::ROLE_MEMBER`, `User::ROLE_STAFF`, `User::ROLE_ADMIN`.
- Evidence: `app/Models/User.php`, `database/migrations/2014_10_12_000000_create_users_table.php`.

## Canonical / legacy mappings

| Legacy identifier or term | Canonical term / identifier | Rule |
| --- | --- | --- |
| Đơn đặc biệt / Special Order | Đơn hàng giá trị cao / High-Value Order (HVO) | Deprecated; do not use for new code or UI text. |
| `is_order_special` | `is_high_value_order` | Legacy API compatibility only. |
| `orders:check-special-reminder` | `orders:check-hvo-reminder` | Legacy Artisan alias only. |
| `Frozen_order.platform` | partner snapshot/display naming | Legacy storage/compatibility naming; prefer `snapshot_partner_name` / `display_partner_name`. |

## Needs confirmation

These identifiers exist in the repository, but the current code/documentation does not establish one sufficiently precise canonical domain definition. Do not invent one.

### `Order.fake_price`

- Status: **chưa xác định / cần xác nhận**.
- Evidence found: the model exposes the field and the admin order detail renders it as a struck-through price while `price` is the product unit price.
- Missing evidence: no authoritative rule was found that defines when/how it is calculated or its canonical business meaning.
- Sources: `app/Models/Order.php`, `resources/views/admin/order/show.blade.php`.

### `users.todays_discount`

- Status: **chưa xác định / cần xác nhận** as a canonical term.
- Evidence found: the schema comment calls it today's discount, while current order completion/distribution code increments/calculates it from commission/profit and the UI presents related values as estimated commission.
- Do not rename or document it as strictly "discount" or strictly "commission" without a confirmed business decision.
- Sources: `database/migrations/2014_10_12_000000_create_users_table.php`, `app/Jobs/CompleteOrder.php`, `app/Http/Controllers/User/HomeController.php`, `resources/views/user/distribution.blade.php`.

### `temporary_commission` / `available_balance`

- Status: **chưa xác định / cần xác nhận**.
- Evidence found: `User::getAvailableBalanceAttribute()` returns `balance + temporary_commission`.
- Missing evidence: the authoritative lifecycle/source of `temporary_commission` and whether this computed value is a canonical withdrawable/account balance concept are not established by the inspected model/schema flow.
- Source: `app/Models/User.php`.
