<?php

declare(strict_types=1);

use SivarcartCore\SivarcartCore\Tools\CommitMessage;

require __DIR__.'/../../vendor/autoload.php';

/**
 * The character git uses to mark the comments of the message file.
 */
$commentChar = '#';

$process = proc_open(['git', 'config', '--get', 'core.commentChar'], [
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
], $pipes);

if (is_resource($process)) {
    $configured = trim((string) stream_get_contents($pipes[1]));

    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    if ($configured !== '') {
        $commentChar = $configured;
    }
}

/**
 * The file git wrote with the message of the commit.
 */
$file = $argv[1] ?? '';

if (! is_file($file)) {
    fwrite(STDERR, 'No se encontró el archivo del mensaje del commit.'.PHP_EOL);

    exit(1);
}

$lines = [];

foreach (preg_split('/\R/', (string) file_get_contents($file)) ?: [] as $line) {
    if (str_starts_with(ltrim($line), $commentChar)) {
        continue;
    }

    $lines[] = $line;
}

$error = CommitMessage::validate(implode("\n", $lines));

if ($error === null) {
    exit(0);
}

fwrite(STDERR, PHP_EOL.'✖ El mensaje del commit no es válido:'.PHP_EOL.PHP_EOL);
fwrite(STDERR, '  '.$error.PHP_EOL.PHP_EOL);
fwrite(STDERR, 'Formato esperado:'.PHP_EOL);
fwrite(STDERR, '  <tipo>(<ámbito>): <descripción>'.PHP_EOL.PHP_EOL);
fwrite(STDERR, '  Ejemplo: feat(orders): agrega el checkout de invitado'.PHP_EOL.PHP_EOL);
fwrite(STDERR, 'Tipos permitidos: '.implode(', ', CommitMessage::TYPES).PHP_EOL.PHP_EOL);
fwrite(STDERR, 'Máximo: '.CommitMessage::MAX_LENGTH.' caracteres, en una sola línea.'.PHP_EOL.PHP_EOL);

exit(1);
