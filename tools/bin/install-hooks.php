<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

/**
 * Run a git command inside the repository and return its output and status.
 *
 * @param  list<string>  $arguments
 * @return array{0: string, 1: int}
 */
$run = function (array $arguments) use ($root): array {
    $command = ['git'];

    foreach ($arguments as $argument) {
        $command[] = $argument;
    }

    $process = proc_open($command, [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $root);

    if (! is_resource($process)) {
        return ['', 1];
    }

    $output = (string) stream_get_contents($pipes[1]);
    $errors = (string) stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $status = proc_close($process);

    return [trim($output !== '' ? $output : $errors), $status];
};

if ($run(['rev-parse', '--git-dir'])[1] !== 0) {
    fwrite(STDERR, 'Este directorio todavía no es un repositorio Git. Ejecuta "git init" primero.'.PHP_EOL);

    exit(1);
}

if ($run(['config', 'core.hooksPath', '.githooks'])[1] !== 0) {
    fwrite(STDERR, 'No se pudo configurar "core.hooksPath". Revisa los permisos del repositorio.'.PHP_EOL);

    exit(1);
}

fwrite(STDOUT, 'Hooks instalados: core.hooksPath = .githooks'.PHP_EOL);
fwrite(STDOUT, 'commit-msg, pre-commit y pre-push se ejecutan en cada commit.'.PHP_EOL);
fwrite(STDOUT, 'Para quitarlos: git config --unset core.hooksPath'.PHP_EOL);
