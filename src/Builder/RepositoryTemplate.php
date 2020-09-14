<?php

declare(strict_types=1);

namespace Orm\Builder;

use function ICanBoogie\singularize;

class RepositoryTemplate
{
    private const TEMPLATE = <<<'STRING'
    <?php
    
    declare(strict_types=1);
    
    namespace Orm\Repository;
    
    use Orm\EntityManager;
    use Traversable;
    use [class];
    
    class [cacheClassName] extends EntityManager
    {
        /**
         * @inheritDoc
         * @return [short_class]|null
         */
        public function loadById($id): ?object
        {
            return $this->loadBy(['id' => $id]);
        }
        
        /**
         * @inheritDoc
         * @return [short_class]|null
         */
        public function loadBy(array $where): ?object
        {
            return $this->selectOne('[table]', $where);
        }
        
        /**
         * @param mixed[] $where
         * @return Traversable<[short_class]>
         */
        public function selectBy(array $where = []): Traversable
        {
            return $this->select('[table]', $where);
        }
    
        /**
         * @param [short_class] $entity
         */
        public function insert(object $entity): void
        {
            $statement = <<<SQL
                insert into [table] values (
                    [inline_fields]
                );
            SQL;
    
            $this->connection()->execute($statement, [
                [bindings],
            ]);
        }
    
        /**
         * @param [short_class] $entity
         */
        public function update(object $entity): void
        {
            $statement = <<<SQL
                update [table] set
                    [inline_field_values]
                where
                    id = :id
                ;
            SQL;
    
            $this->connection()->execute($statement, [
                [bindings],
            ]);
        }
    
        /**
         * @param [short_class] $entity
         */
        public function delete(object $entity): void
        {
            $statement = <<<SQL
                delete from [table] where id = :id;
            SQL;
    
            $this->connection()->execute($statement, [
                'id' => $entity->getId(),
            ]);
        }
        
        /**
         * @inheritDoc
         * @return [short_class]
         */
        public function parseDataIntoObject(array $item): object
        {
            return new [short_class](
                [array_fields],
            );
        }
    }
    STRING;

    private TableDefinition $definition;
    private string $repositoryName;

    public function __construct(TableDefinition $definition, string $repositoryName)
    {
        $this->definition = $definition;
        $this->repositoryName = $repositoryName;
    }

    /**
     * @return string[]
     */
    protected function getArrayFields(): array
    {
        $objectFields = [];

        foreach ($this->definition->getObjectFields() as $field) {
            $objectFields[$field->getObjectField()][] = $field;
        }

        return array_map(
            fn (array $objectField) => $this->prepareArrayFields($objectField),
            $objectFields
        );
    }

    /**
     * @param TableField[] $objectField
     */
    protected function prepareMultiFields(array $objectField): string
    {
        $fields = [];
        $voClass = current($objectField)->getValueObject();

        foreach ($objectField as $field) {
            $fields[] = sprintf("%s\$item['%s']", $field->getCast(), $field->getName());
        }

        return sprintf("%snew \%s(%s)", str_repeat(' ', 12), $voClass, implode(', ', $fields));
    }

    /**
     * @param TableField[] $objectField
     */
    private function prepareArrayFields(array $objectField): string
    {
        if (count($objectField) > 1) {
            return $this->prepareMultiFields($objectField);
        }

        return $this->prepareNormalFields($objectField);
    }

    /**
     * @param TableField[] $objectField
     */
    private function prepareNormalFields(array $objectField): string
    {
        $field = current($objectField);
        $valueObjectClass = $field->getValueObject();

        if ($field->isChild()) {
            return sprintf(
                "%s%s\$this->factory()->getRepository(\%s::class)->selectBy(['%s_id' => \$item['id']])",
                str_repeat(' ', 12),
                $field->getDefinition()->isVariadic() ? '...' : '',
                str_replace('[]', '', $field->getDefinition()->getType()),
                singularize($this->definition->getTableName())
            );
        }

        if ($field->getDefinition()->isEntity()) {
            return sprintf(
                "%s\$this->factory()->getRepository(\%s::class)->loadById(\$item['%s'])",
                str_repeat(' ', 12),
                $field->getDefinition()->getType(),
                $field->getName()
            );
        }

        if ($valueObjectClass) {
            return sprintf(
                "%snew \%s(%s\$item['%s'])",
                str_repeat(' ', 12),
                $valueObjectClass,
                $field->getCast(),
                $field->getName()
            );
        }

        return sprintf("%s%s \$item['%s']", str_repeat(' ', 12), $field->getCast(), $field->getName());
    }

    public function __toString(): string
    {
        $inlineFields = array_map(
            fn (TableField $field) => sprintf("%s:%s", str_repeat(' ', 16), $field->getName()),
            iterator_to_array($this->definition->getTableFields())
        );

        $fieldValues = array_map(
            fn (TableField $field) => sprintf(
                "%s`%s` = :%s",
                str_repeat(' ', 16),
                $field->getName(),
                $field->getName()
            ),
            iterator_to_array($this->definition->getTableFields())
        );

        $bindings = array_map(
            fn (TableField $field) => sprintf(
                "%s'%s' => \$entity->%s",
                str_repeat(' ', 12),
                $field->getName(),
                $field->getDefinition()->getGetter(),
            ),
            iterator_to_array($this->definition->getTableFields())
        );

        $template = self::TEMPLATE;
        $template = str_replace('[cacheClassName]', $this->repositoryName, $template);
        $template = str_replace('[class]', $this->definition->getClass()->getName(), $template);
        $template = str_replace('[short_class]', $this->definition->getClass()->getShortName(), $template);
        $template = str_replace('[table]', $this->definition->getTableName(), $template);
        $template = str_replace('[inline_fields]', trim(implode(",\n", $inlineFields)), $template);
        $template = str_replace('[inline_field_values]', trim(implode(",\n", $fieldValues)), $template);
        $template = str_replace('[bindings]', trim(implode(",\n", $bindings)), $template);
        $template = str_replace('[array_fields]', trim(implode(",\n", $this->getArrayFields())), $template);

        return $template;
    }
}
