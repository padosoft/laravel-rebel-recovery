<?php

declare(strict_types=1);

return [

    // How many recovery codes to issue per generation.
    'code_count' => (int) env('REBEL_RECOVERY_CODE_COUNT', 10),

];
