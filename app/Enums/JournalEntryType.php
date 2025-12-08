<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

enum JournalEntryType: string
{
    case MANUAL = 'manual';
    case AUTO = 'auto';
}
