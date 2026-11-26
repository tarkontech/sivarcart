<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * The palette of Tailwind.
 *
 * A color of the palette is a color of a theme, and the panel does not have
 * themes of its own: it has tokens. A theme is a change of the CSS.
 */
const palette = [
    'slate', 'gray', 'zinc', 'neutral', 'stone', 'red', 'orange', 'amber',
    'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan', 'sky', 'blue',
    'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose', 'white', 'black',
];

/**
 * The properties of Tailwind that take a color.
 */
const colorProperties = [
    'bg', 'text', 'border', 'ring', 'fill', 'stroke', 'from', 'to', 'via',
    'outline', 'decoration', 'accent', 'caret', 'divide', 'shadow',
    'placeholder',
];

/**
 * The files of the source of the panel.
 *
 * @return list<string>
 */
function sourcesOfThePanel(): array
{
    $directory = dirname(__DIR__, 3).'/resources/js/admin';

    if (! is_dir($directory)) {
        return [];
    }

    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && in_array($file->getExtension(), ['ts', 'tsx'], true)) {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

it('keeps the source of the panel free of the colors of the palette', function () {
    $offenders = [];

    foreach (sourcesOfThePanel() as $file) {
        $contents = (string) File::get($file);

        foreach (colorProperties as $property) {
            foreach (palette as $color) {
                $pattern = '/\b'.preg_quote($property, '/').'-'.preg_quote($color, '/').'\b/';

                if (preg_match($pattern, $contents) === 1) {
                    $offenders[] = basename($file).': '.$property.'-'.$color;
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('keeps the source of the panel free of the colors written by hand', function () {
    $offenders = [];

    foreach (sourcesOfThePanel() as $file) {
        $contents = (string) File::get($file);

        if (preg_match('/#[0-9a-fA-F]{3,8}\b/', $contents) === 1) {
            $offenders[] = basename($file).': a hexadecimal color';
        }

        foreach (['rgb(', 'rgba(', 'hsl(', 'hsla(', 'oklch('] as $function) {
            if (str_contains($contents, $function)) {
                $offenders[] = basename($file).': '.$function;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('declares every token of the design system of the panel', function () {
    $styles = (string) File::get(dirname(__DIR__, 3).'/resources/css/admin.css');

    $tokens = [
        'background', 'foreground', 'card', 'card-foreground', 'popover',
        'popover-foreground', 'primary', 'primary-foreground', 'primary-hover',
        'secondary', 'secondary-foreground', 'muted', 'muted-foreground',
        'accent', 'accent-foreground', 'destructive', 'destructive-foreground',
        'success', 'success-foreground', 'warning', 'warning-foreground',
        'border', 'input', 'ring', 'chart-1', 'chart-2', 'chart-3', 'chart-4',
        'chart-5', 'sidebar', 'sidebar-foreground', 'sidebar-primary',
        'sidebar-primary-foreground', 'sidebar-accent',
        'sidebar-accent-foreground', 'sidebar-border', 'sidebar-ring',
    ];

    $missing = [];

    foreach ($tokens as $token) {
        if (preg_match('/--'.$token.':\s/', $styles) !== 1) {
            $missing[] = $token;
        }
    }

    expect($missing)->toBe([]);
});

it('gives the panel a token for the light theme and another one for the dark one', function () {
    $styles = (string) File::get(dirname(__DIR__, 3).'/resources/css/admin.css');

    expect($styles)->toContain('@custom-variant dark')
        ->toContain(':root {')
        ->toContain('.dark {');
});

it('maps every token of the design system to a color of Tailwind', function () {
    $styles = (string) File::get(dirname(__DIR__, 3).'/resources/css/admin.css');

    expect($styles)->toContain('--color-background: var(--background);')
        ->toContain('--color-foreground: var(--foreground);')
        ->toContain('--color-primary: var(--primary);')
        ->toContain('--color-success: var(--success);')
        ->toContain('--color-warning: var(--warning);')
        ->toContain('--color-border: var(--border);');
});
