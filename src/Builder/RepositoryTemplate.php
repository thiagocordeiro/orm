<?php

declare(strict_types=1);

namespace Orm\Builder;

use function ICanBoogie\singularize;

class RepositoryTemplate
{
    private const TEMPLATE = <<<'PHP'
    <?php
    
    declare(strict_types=1);
    
    namespace Orm\Repository;
    
    use Orm\Repository;
    use Traversable;
    use _class_name_;
    
    class _cache_class_name_ extends Repository
    {
        /**
         * @inheritDoc
         * @return _short_class_|null
         */
        public function loadById($id): ?object
        {
            return $this->loadBy(['id' => $id]);
        }
        
        /**
         * @inheritDoc
         * @return _short_class_|null
         */
        public function loadBy(array $where): ?object
        {
            return $this->selectOne('_table_name_', $where);
        }
        
        /**
         * @param mixed[] $where
         * @return Traversable<_short_class_>
         */
        public function selectBy(
            array $where = [],
            string $order = '',
            ?int $limit = null,
            ?int $offset = null
        ): Traversable {
            return $this->select('_table_name_', $where);
        }
    
        /**
         * @param _short_class_ $entity
         */
        public function insert(object $entity): void
        {
            $statement = <<<SQL
                insert into _table_name_ values (
                    _inline_fields_
                );
            SQL;
    
            $this->connection()->execute($statement, [
                _bindings_,
            ]);
        }
    
        /**
         * @param _short_class_ $entity
         */
        public function update(object $entity): void
        {
            $statement = <<<SQL
                update _table_name_ set
                    _inline_field_values_
                where
                    id = :id
                ;
            SQL;
    
            $this->connection()->execute($statement, [
                _bindings_,
            ]);
        }
    
        /**
         * @param _short_class_ $entity
         */
        public function delete(object $entity): void
        {
            $statement = <<<SQL
                delete from _table_name_ where id = :id;
            SQL;
    
            $this->connection()->execute($statement, [
                'id' => $entity->getId(),
            ]);
        }
        
        /**
         * @inheritDoc
         * @return _short_class_
         */
        public function parseDataIntoObject(array $item): object
        {
            return new _short_class_(
                _array_fields_,
            );
        }
    }
    PHP;

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
        $field = current($objectField);

        $subFields = [];
        $voClass = current($objectField)->getValueObject();

        foreach ($objectField as $subField) {
            if (false === $subField->isScalar()) {
                $subFields[] = sprintf("new \%s(\$item['%s'])", $subField->getType(), $subField->getName());

                continue;
            }

            $subFields[] = sprintf("%s\$item['%s']", $subField->getCast(), $subField->getName());
        }

        return $this->prepareNullableArrayProperty(
            $field,
            $field->getName(),
            sprintf("new \%s(%s)", $voClass, implode(', ', $subFields)),
        );
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
            return $this->prepareNullableArrayProperty(
                $field,
                'id',
                sprintf(
                    "%s\$this->em()->getRepository(\%s::class)->selectBy(['%s_id' => \$item['id']])",
                    $field->getDefinition()->isVariadic() ? '...' : '',
                    str_replace('[]', '', $field->getDefinition()->getType()),
                    singularize($this->definition->getTableName()),
                )
            );
        }

        if ($field->getDefinition()->isEntity()) {
            return $this->prepareNullableArrayProperty(
                $field,
                $field->getName(),
                sprintf(
                    "\$this->em()->getRepository(\%s::class)->loadById(\$item['%s'])",
                    $field->getDefinition()->getType(),
                    $field->getName()
                )
            );
        }

        if ($valueObjectClass) {
            return $this->prepareNullableArrayProperty(
                $field,
                $field->getName(),
                sprintf("new \%s(%s\$item['%s'])", $valueObjectClass, $field->getCast(), $field->getName())
            );
        }

        return $this->prepareNullableArrayProperty(
            $field,
            $field->getName(),
            sprintf("%s\$item['%s']", $field->getCast(), $field->getName())
        );
    }

    private function prepareNullableArrayProperty(TableField $field, string $property, string $strNotNull): string
    {
        if ($field->isNullable()) {
            $strNotNull = sprintf("\$item['%s'] ? %s : null", $property, $strNotNull);
        }

        return sprintf('%s%s', str_repeat(' ', 12), $strNotNull);
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
        $template = str_replace('_cache_class_name_', $this->repositoryName, $template);
        $template = str_replace('_class_name_', $this->definition->getClass()->getName(), $template);
        $template = str_replace('_short_class_', $this->definition->getClass()->getShortName(), $template);
        $template = str_replace('_table_name_', $this->definition->getTableName(), $template);
        $template = str_replace('_inline_fields_', trim(implode(",\n", $inlineFields)), $template);
        $template = str_replace('_inline_field_values_', trim(implode(",\n", $fieldValues)), $template);
        $template = str_replace('_bindings_', trim(implode(",\n", $bindings)), $template);
        $template = str_replace('_array_fields_', trim(implode(",\n", $this->getArrayFields())), $template);

        return $template;
    }
}
