<?php

declare(strict_types=1);

/*
| The language of the store is "es_SV", but the language of an application is
| whatever "config('app.locale')" says, and the translator looks for a folder
| with that exact name. Without this file an application with the locale
| "es_SV" would fall back to the English texts.
*/

return require __DIR__.'/../es/messages.php';
