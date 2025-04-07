<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AddTranslationKey extends Command
{
    protected $signature = 'translation:add-key {file} {key} {--value=}';
    protected $description = 'Add a translation key to all languages';

    public function handle()
{
    $langsPath = resource_path('lang');
    $file = $this->argument('file');
    $key = $this->argument('key');
    $value = $this->option('value') ?? last(explode('.', $key));

    foreach (scandir($langsPath) as $lang) {
        if (in_array($lang, ['.', '..'])) continue;

        $filePath = "$langsPath/$lang/$file.php";

        if (!file_exists($filePath)) {
            $this->warn("Skipping missing file: $filePath");
            continue;
        }

        $translations = include $filePath;

        if (data_get($translations, $key) !== null) {
            $this->info("Key [$key] already exists in $lang/$file.php");
            continue;
        }

        $formatted = $this->buildNestedArrayString(
            $key,
            $value
        );

        $fileContent = file_get_contents($filePath);
        $newContent = preg_replace('/(\];\s*)$/', "$formatted\n$1", $fileContent);

        file_put_contents($filePath, $newContent);
        $this->info("Appended key [$key] to $lang/$file.php");
    }
}

protected function buildNestedArrayString(string $dotKey, string $value): string
{
    $keys = explode('.', $dotKey);
    $indent = '    ';
    $lines = ' ';
    $depth = count($keys);

    foreach ($keys as $i => $key) {
        $pad = str_repeat($indent, $i);
        $lines .= "{$pad}'{$key}' => [\n";
    }

    $valueLine = str_repeat($indent, $depth) . "'{$value}',\n";
    $lines .= $valueLine;

    // Close the brackets
    for ($i = $depth - 1; $i >= 0; $i--) {
        $pad = str_repeat($indent, $i);
        $lines .= "{$pad}],\n";
    }

    return $lines;
}

}
