<?php

declare(strict_types=1);

namespace Modules\Accounting\Enums;

enum EntryStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case REVERSED = 'reversed';
}
