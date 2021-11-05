<?php

declare(strict_types=1);

namespace Orm\Migration;

interface Migration
{
    public function up(): string;

    public function down(): string;
}
