<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use SivarcartCore\SivarcartCore\Database\Factories\NotificationSettingFactory;
use SivarcartCore\SivarcartCore\Enums\OrderNotification;
use SivarcartCore\SivarcartCore\Models\Concerns\HasUuidColumn;

/**
 * Whether the store sends one of the notifications of the buyer.
 *
 * The table only holds the notifications the merchant has changed: a
 * notification without a row follows the default of the configuration.
 */
class NotificationSetting extends Model
{
    use HasUuidColumn;

    /**
     * @var string
     */
    protected $table = 'sivarcart_notification_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'notification',
        'is_enabled',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification' => OrderNotification::class,
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): NotificationSettingFactory
    {
        return NotificationSettingFactory::new();
    }
}
