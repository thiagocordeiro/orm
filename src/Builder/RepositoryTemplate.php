<?php

declare(strict_types=1);

namespace Orm\Builder;

use function ICanBoogie\underscore;

class RepositoryTemplate
{
    private const TEMPLATE = <<<'PHP'
    <?php
    
    declare(strict_types=1);
    
    namespace Orm\Repository;
    
    use Orm\Repository;
    use _entity_namespaced_;
    use Throwable;
    
    class _repository_name_ extends Repository
    {
        public function getTable(): string
        {
            return '_table_name_';
        }
        
        /**
         * @inheritDoc
         */
        public function getOrder(array $order): array
        {
            return !empty($order) ? $order : _default_order_;
        }
        
        public function getColumns(): string
        {
            return <<<'STRING'
                _columns_
            STRING;
        }
        
        public function getBindings(): string
        {
            return <<<'STRING'
                _bindings_
            STRING;
        }
        
        public function getColumnsEqualBindings(): string
        {
            return <<<'STRING'
                _columns_equal_bindings_
            STRING;
        }
        
        /**
         * @inheritDoc
         * @param _entity_name_ $entity
         */
        public function getDeleteCriteria(object $entity): array
        {
            return ['id' => $entity->getId()];
        }
        
        /**
         * @inheritDoc
         * @param _entity_name_ $entity
         */
        public function entityToDatabaseRow(object $entity): array
        {
            return [
                _object_to_array_,
            ];
        }

        /**
         * @inheritDoc
         * @return _entity_name_
         * @throws Throwable
         */
        public function databaseRowToEntity(array $item): object
        {
            return new _entity_name_(
                _array_fields_,
            );
        }
    }
    PHP;

    private TableDefinition $definition;
    private string $repositoryName;

    /** @var mixed[] */
    private array $config;

    /**
     * @param mixed[] $config
     */
    public function __construct(TableDefinition $definition, string $repositoryName, array $config)
    {
        $this->definition = $definition;
        $this->repositoryName = $repositoryName;
        $this->config = $config;
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
            $objectFields,
        );
    }

    /**
     * @param TableField[] $objectField
     */
    protected function prepareMultiFields(array $objectField): string
    {
        $field = current($objectField);
        assert($field instanceof TableField);

        $subFields = [];
        $voClass = $field->getValueObject();

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
        assert($field instanceof TableField);

        $valueObjectClass = $field->getValueObject();

        if ($field->isChildList()) {
            return $this->prepareNullableArrayProperty(
                $field,
                'id',
                sprintf(
                    "%siterator_to_array(\$this->em->getRepository(\%s::class)->select(['%s_id' => \$item['id']]))",
                    $field->getDefinition()->isVariadic() ? '...' : '',
                    str_replace('[]', '', $field->getDefinition()->getType()),
                    underscore($this->definition->getClass()->getShortName()),
                ),
            );
        }

        if ($field->isChild()) {
            return sprintf(
                "%s\$this->em->getRepository(\%s::class)->loadBy(['%s' => \$item['id']])",
                str_repeat(' ', 12),
                $field->getDefinition()->getType(),
                $field->getDefinition()->getChildName(),
            );
        }

        if ($field->getDefinition()->isEntity()) {
            return $this->prepareNullableArrayProperty(
                $field,
                $field->getName(),
                sprintf(
                    "\$this->em->getRepository(\%s::class)->loadById(\$item['%s'])",
                    $field->getDefinition()->getType(),
                    $field->getName(),
                ),
            );
        }

        if ($valueObjectClass) {
            return $this->prepareNullableArrayProperty(
                $field,
                $field->getName(),
                sprintf("new \%s(%s\$item['%s'])", $valueObjectClass, $field->getCast(), $field->getName()),
            );
        }

        return $this->prepareNullableArrayProperty(
            $field,
            $field->getName(),
            sprintf("%s\$item['%s']", $field->getCast(), $field->getName()),
        );
    }

    private function prepareNullableArrayProperty(TableField $field, string $property, string $strNotNull): string
    {
        if ($field->isNullable()) {
            $strNotNull = sprintf("\$item['%s'] ? %s : null", $property, $strNotNull);
        }

        return sprintf('%s%s', str_repeat(' ', 12), $strNotNull);
    }

    private function getDefaultOrder(): string
    {
        $ordering = [];
        $orders = $this->config['order'] ?? [];

        foreach ($orders as $column => $direction) {
            $ordering[] = sprintf("'%s' => '%s'", underscore($column), $direction);
        }

        return sprintf('[%s]', implode(', ', $ordering));
    }

    public function __toString(): string
    {
        $inlineFields = array_map(
            fn (TableField $field) => sprintf("%s:%s", str_repeat(' ', 12), $field->getName()),
            iterator_to_array($this->definition->getTableFields()),
        );

        $inlineColumns = array_map(
            fn (TableField $field) => sprintf("%s`%s`", str_repeat(' ', 12), $field->getName()),
            iterator_to_array($this->definition->getTableFields()),
        );

        $fieldValues = array_map(
            fn (TableField $field) => sprintf(
                "%s`%s` = :%s",
                str_repeat(' ', 12),
                $field->getName(),
                $field->getName(),
            ),
            iterator_to_array($this->definition->getTableFields()),
        );

        $bindings = array_map(
            fn (TableField $field) => sprintf(
                match ($field->getType()) {
                    'bool' => "%s'%s' => \$entity->%s ? 1 : 0",
                    'float' => "%s'%s' => \$this->floatToDbString(\$entity->%s)",
                    default => "%s'%s' => \$entity->%s",
                },
                str_repeat(' ', 12),
                $field->getName(),
                $field->getDefinition()->getGetter(),
            ),
            iterator_to_array($this->definition->getTableFields()),
        );

        $template = self::TEMPLATE;
        $template = str_replace('_columns_equal_bindings_', trim(implode(",\n", $fieldValues)), $template);
        $template = str_replace('_entity_namespaced_', $this->definition->getClass()->getName(), $template);
        $template = str_replace('_object_to_array_', trim(implode(",\n", $bindings)), $template);
        $template = str_replace('_repository_name_', $this->repositoryName, $template);
        $template = str_replace('_default_order_', $this->getDefaultOrder(), $template);
        $template = str_replace('_array_fields_', trim(implode(",\n", $this->getArrayFields())), $template);
        $template = str_replace('_entity_name_', $this->definition->getClass()->getShortName(), $template);
        $template = str_replace('_table_name_', $this->definition->getTableName(), $template);
        $template = str_replace('_bindings_', trim(implode(",\n", $inlineFields)), $template);
        $template = str_replace('_columns_', trim(implode(",\n", $inlineColumns)), $template);

        return $template;
    }
}
