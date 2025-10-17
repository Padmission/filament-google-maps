<?php

namespace Cheesegrits\FilamentGoogleMaps\Helpers;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

class FieldHelper
{
    public static function getTopComponent(Component $component): Component
    {
        $parentComponent = $component->getContainer()->getParentComponent();

        return $parentComponent ? static::getTopComponent($parentComponent) : $component;
    }

    public static function getFlatFields($topComponent): array
    {
        $flatFields = $topComponent->getContainer()->getFlatFields();

        foreach ($topComponent->getContainer()->getComponents() as $component) {
            if (($component instanceof Action) || ($component instanceof ActionGroup)) {
                continue;
            }
            
            foreach ($component->getChildComponentContainers() as $container) {
                if ($container->isHidden()) {
                    continue;
                }

                $flatFields = array_merge($flatFields, $container->getFlatFields());
            }
        }

        return $flatFields;
    }

    public static function getFieldId(string $field, Component $component): ?string
    {
        $topComponent = self::getTopComponent($component);
        $flatFields = static::getFlatFields($topComponent);
        $fieldsCollection = collect($flatFields)->whereInstanceOf(Field::class);

        $fieldsByName = $fieldsCollection->keyBy(fn ($field) => $field->getName());

        if ($fieldsByName->has($field)) {
            return $fieldsByName->get($field)->getStatePath();
        }

        $fieldsByPath = $fieldsCollection->keyBy(fn ($field) => $field->getStatePath());

        if ($fieldsByPath->has($field)) {
            return $fieldsByPath->get($field)->getStatePath();
        }

        $matchingField = $fieldsCollection->first(function ($f) use ($field) {
            return str_ends_with($f->getStatePath(), $field);
        });

        if ($matchingField) {
            return $matchingField->getStatePath();
        }

        return null;
    }
}
