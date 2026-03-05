<?php

namespace App\Enums;

enum LicenseRisk: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case UNKNOWN = 'unknown';
}
