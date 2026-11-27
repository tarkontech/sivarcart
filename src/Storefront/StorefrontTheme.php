<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Storefront;

use Illuminate\Support\Facades\Config;

/**
 * The theme of the store the shopper buys in.
 *
 * The store brings its own design system: the colors are CSS custom
 * properties, so the merchant changes them in the configuration of the
 * package, without compiling anything.
 *
 * The properties the merchant owns are the ones of the brand: the primary
 * color, the danger, the success, the warning, the radius and the font. The
 * surfaces —the background, the cards, the muted text— belong to the
 * stylesheet of the package, because they are the ones that keep the store
 * readable in the light theme and in the dark one.
 */
final class StorefrontTheme
{
    /**
     * The variables the theme of the package owns.
     *
     * @return array<string, string>
     */
    public static function variables(): array
    {
        return self::read('sivarcart.storefront.theme');
    }

    /**
     * The variables the theme of the package owns for the dark theme.
     *
     * They are written after the ones of the light theme, so they win on it.
     *
     * @return array<string, string>
     */
    public static function darkVariables(): array
    {
        return self::read('sivarcart.storefront.theme_dark');
    }

    /**
     * The variables of a section of the configuration.
     *
     * @return array<string, string>
     */
    private static function read(string $key): array
    {
        $theme = Config::get($key);

        if (! is_array($theme)) {
            return [];
        }

        $variables = [];

        foreach ($theme as $name => $value) {
            if (! is_string($name) || ! is_string($value) || $value === '') {
                continue;
            }

            $variables[self::variable($name)] = $value;
        }

        return $variables;
    }

    /**
     * The name of the CSS variable of a key of the theme.
     */
    private static function variable(string $key): string
    {
        if ($key === 'font') {
            return '--font-sans';
        }

        return '--'.str_replace('_', '-', $key);
    }
}
