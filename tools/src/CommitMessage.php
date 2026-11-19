<?php

declare(strict_types=1);

namespace SivarcartCore\SivarcartCore\Tools;

/**
 * Validates the commit messages of the repository.
 *
 * The rules are the ones of commitlint: a type, an optional scope, an optional
 * breaking change marker and a description, all written in a single line.
 */
final class CommitMessage
{
    /**
     * The maximum number of characters a commit message may have.
     */
    public const int MAX_LENGTH = 300;

    /**
     * The commit types the repository accepts.
     *
     * @var list<string>
     */
    public const array TYPES = [
        'build',
        'chore',
        'ci',
        'docs',
        'feat',
        'fix',
        'perf',
        'refactor',
        'revert',
        'style',
        'test',
    ];

    /**
     * The prefixes git uses for the commits it writes on its own.
     *
     * @var list<string>
     */
    public const array GENERATED_PREFIXES = ['fixup!', 'squash!', 'amend!'];

    /**
     * The shape every commit message has to follow.
     */
    private const string PATTERN = '/^(?<type>[a-z]+)(?:\((?<scope>[^()\s]+)\))?!?: (?<description>\S.*)$/';

    /**
     * Validate a commit message.
     *
     * The commits git writes on its own, such as the ones made with
     * "git commit --fixup", are not validated at all.
     *
     * @param  string  $message  The message without the comments git adds.
     * @return string|null The error to show, or null when the message is valid.
     */
    public static function validate(string $message): ?string
    {
        $lines = self::lines($message);

        if ($lines === []) {
            return 'El mensaje del commit está vacío.';
        }

        $subject = $lines[0];

        foreach (self::GENERATED_PREFIXES as $prefix) {
            if (str_starts_with($subject, $prefix)) {
                return null;
            }
        }

        if (count($lines) > 1) {
            return 'El mensaje del commit debe tener una sola línea, sin cuerpo ni pie.';
        }

        $length = mb_strlen($subject);

        if ($length > self::MAX_LENGTH) {
            return sprintf(
                'El mensaje del commit no puede pasar de %d caracteres (tiene %d).',
                self::MAX_LENGTH,
                $length,
            );
        }

        if (preg_match(self::PATTERN, $subject, $matches) !== 1) {
            return 'El mensaje del commit debe seguir el formato "tipo(ámbito): descripción", por ejemplo "feat(orders): agrega el checkout de invitado".';
        }

        if (! in_array($matches['type'], self::TYPES, true)) {
            return sprintf(
                'El tipo "%s" no está permitido. Usa uno de estos: %s.',
                $matches['type'],
                implode(', ', self::TYPES),
            );
        }

        return null;
    }

    /**
     * Get the meaningful lines of a commit message.
     *
     * @return list<string>
     */
    private static function lines(string $message): array
    {
        $lines = [];

        foreach (preg_split('/\R/', $message) ?: [] as $line) {
            $line = trim($line);

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
