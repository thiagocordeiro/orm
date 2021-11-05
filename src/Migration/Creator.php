<?php

declare(strict_types=1);

namespace Orm\Migration;

class Creator
{
    private const CLASS_TEMPLATE = <<<PHP
    <?php
    
    declare(strict_types=1);
    
    use Orm\Migration\Migration;
    
    class MigrationClassName implements Migration
    {
        public function up(): string
        {
            return <<<SQL
                # query
            SQL;
        }
    
        public function down(): string
        {
            return <<<SQL
                # query
            SQL;
        }
    }
    PHP;

    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    public function create(string $name): void
    {
        $class = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

        $content = str_replace('MigrationClassName', $class, self::CLASS_TEMPLATE);
        $datetime = date('Y_m_d_His');

        file_put_contents("{$this->directory}/{$datetime}_{$name}.php", $content);
    }
}
