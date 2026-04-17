<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

echo 'autoloaders=' . count(spl_autoload_functions() ?: []) . PHP_EOL;

foreach (spl_autoload_functions() ?: [] as $f) {
    if (is_array($f)) {
        $target = is_object($f[0]) ? get_class($f[0]) : (string) $f[0];
        echo $target . '::' . $f[1] . PHP_EOL;
        continue;
    }

    if ($f instanceof Closure) {
        echo 'closure' . PHP_EOL;
        continue;
    }

    echo (string) $f . PHP_EOL;
}

// Provoke one autoload call
class_exists(\App\Entity\OrganizerProfile::class);
echo 'class_loaded=' . (class_exists(\App\Entity\OrganizerProfile::class, false) ? 'yes' : 'no') . PHP_EOL;

