<?php

declare(strict_types=1);

/**
 * The API's UserType codes, in words a user recognises.
 *
 * ? The API transmits two-letter codes because they are sealed into a token and
 *   every byte there is paid for on every request. Showing "OW" in a user menu is
 *   a leak of an internal encoding, so the translation happens here.
 */
return [
    'SA' => 'Platform admin',
    'OW' => 'Owner',
    'MG' => 'Management',
    'MB' => 'Member',
];
