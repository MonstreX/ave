<?php

namespace Monstrex\Ave\Core\Fields;

use Illuminate\Http\Request;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\Forms\FormContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Fieldset extends AbstractField
{
    protected array $childSchema = [];
    protected array $itemInstances = [];
    protected array $itemIds = [];
    protected bool $sortable = true;
    protected bool $collapsible = false;
    protected bool $collapsed = false;
    protected int $minItems = 0;
    protected int $maxItems = 999;
    protected string $addButtonLabel = 'Добавить';
    protected string $rowTitleTemplate = '';
    protected string $containerClass = '';

    public static function make(string $key): static
    {
        return parent::make($key)->default([]);
    }

    public function schema(array $fields): static
    {
        $this->childSchema = $fields;
        return $this;
    }

    public function getChildSchema(): array
    {
        return $this->childSchema;
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;
        return $this;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;
        if ($collapsed) {
            $this->collapsible = true;
        }
        return $this;
    }

    public function minItems(int $min): static
    {
        $this->minItems = $min;
        return $this;
    }

    public function maxItems(int $max): static
    {
        $this->maxItems = $max;
        return $this;
    }

    public function addButtonLabel(string $label): static
    {
        $this->addButtonLabel = $label;
        return $this;
    }

    public function rowTitleTemplate(string $template): static
    {
        $this->rowTitleTemplate = $template;
        return $this;
    }

    public function containerClass(string $class): static
    {
        $this->containerClass = $class;
        return $this;
    }

    /**
