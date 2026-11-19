<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Models;

use SivarcartCore\SivarcartCore\Database\Factories\SettingFactory;

/**
 * A free form setting.
 *
 * This table is the extension point optional modules use to store their own
 * configuration without having to change the core schema.
 */
class Setting extends Model
{
    /**
     * @var string
     */
    protected $table = 'sivarcart_settings';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }
}
