<?php

namespace App\Enums;

enum AuditType: string
{
    case VULNERABILITY = 'vulnerability';
    case LICENSE = 'license';
    case SECURITY_CONFIG = 'security_config';
    case FULL = 'full';
}
