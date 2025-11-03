<?php

namespace Monstrex\Ave\Core;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ViewErrorBag;
use Monstrex\Ave\Core\DataSources\ArrayDataSource;
use Monstrex\Ave\Core\DataSources\DataSourceInterface;
use Monstrex\Ave\Core\DataSources\ModelDataSource;

/**
 * Form context - manages form state and data source
 *
 * Provides context for form rendering and processing:
 * - Mode: create vs edit
 * - Record: the model being edited (if any)
 * - Old input: for displaying validation errors
 * - Errors: validation errors
 * - DataSource: abstraction for data access (model or array)
 *
 * This enables fields to work uniformly with different data sources
 * (models, JSON arrays, nested structures).
 */
class FormContext
{
    public const MODE_CREATE = 'create';

    public const MODE_EDIT = 'edit';

    protected ViewErrorBag $errors;

    protected ?DataSourceInterface $dataSource = null;

    /**
     * @param  array<string,mixed>  $oldInput
     * @param  array<string,mixed>  $meta
     */
    public function __construct(
        protected string $mode,
        protected ?Model $record = null,
        protected array $oldInput = [],
        ?ViewErrorBag $errors = null,
        protected array $meta = [],
        protected ?Request $request = null,
    ) {
        $this->errors = $errors ?? new ViewErrorBag;
    }

    /**
     * Create context for creating a new record
     *
     * @param  array  $meta  Additional metadata
     * @param  Request|null  $request  Optional request
     * @return static
     */
    public static function forCreate(array $meta = [], ?Request $request = null): self
    {
        return new self(self::MODE_CREATE, null, [], null, $meta, $request);
    }

    /**
     * Create context for editing an existing record
     *
     * @param  Model  $record  The record being edited
     * @param  array  $meta  Additional metadata
     * @param  Request|null  $request  Optional request
     * @return static
     */
    public static function forEdit(Model $record, array $meta = [], ?Request $request = null): self
    {
        return new self(self::MODE_EDIT, $record, [], null, $meta, $request);
    }

    /**
     * Create a form context for working with array/JSON data
     *
     * Used for:
     * - FieldSet items (working with JSON field data)
     * - Popup forms (editing media props, etc.)
     *
     * @param  array  $data  Reference to data array
     * @param  array  $meta  Additional metadata
     * @param  Request|null  $request  Optional request
     * @return static
     */
    public static function forData(array &$data, array $meta = [], ?Request $request = null): self
    {
        $context = new self(self::MODE_CREATE, null, [], null, $meta, $request);
        $context->dataSource = new ArrayDataSource($data);

        return $context;
    }

    /**
     * Add old input (for displaying form with errors)
     *
     * @param  array  $oldInput
     * @return $this
     */
    public function withOldInput(array $oldInput): self
    {
        $this->oldInput = $oldInput;

        return $this;
    }

    /**
     * Add validation errors
     *
     * @param  ViewErrorBag|null  $errors
     * @return $this
     */
    public function withErrors(?ViewErrorBag $errors): self
    {
        $this->errors = $errors ?? new ViewErrorBag;

        return $this;
    }

    /**
     * Check if this is create mode
     *
     * @return bool
     */
    public function isCreate(): bool
    {
        return $this->mode === self::MODE_CREATE;
    }

    /**
     * Check if this is edit mode
     *
     * @return bool
     */
    public function isEdit(): bool
    {
        return $this->mode === self::MODE_EDIT;
    }

    /**
     * Get the record being edited (null if creating)
     *
     * @return Model|null
     */
    public function record(): ?Model
    {
        return $this->record;
    }

    /**
     * Check if has old input for a key
     *
     * @param  string  $key
     * @return bool
     */
    public function hasOldInput(string $key): bool
    {
        return Arr::has($this->oldInput, $key);
    }

    /**
     * Get old input value for a key
     *
     * @param  string  $key
     * @return mixed
     */
    public function oldInput(string $key): mixed
    {
        return Arr::get($this->oldInput, $key);
    }

    /**
     * Check if there are errors for a key
     *
     * @param  string  $key
     * @return bool
     */
    public function hasError(string $key): bool
    {
        return $this->errors->has($key);
    }

    /**
     * Get error messages for a key
     *
     * @param  string  $key
     * @return array
     */
    public function getErrors(string $key): array
    {
        return $this->errors->get($key, []);
    }

    /**
     * Get all validation errors
     *
     * @return ViewErrorBag
     */
    public function errors(): ViewErrorBag
    {
        return $this->errors;
    }

    /**
     * Get data source for this context
     *
     * Returns appropriate data source:
     * - ArrayDataSource if created via forData()
     * - ModelDataSource if record exists
     * - null otherwise
     *
     * @return DataSourceInterface|null
     */
    public function dataSource(): ?DataSourceInterface
    {
        if ($this->dataSource !== null) {
            return $this->dataSource;
        }

        if ($this->record !== null) {
            $this->dataSource = new ModelDataSource($this->record);
            return $this->dataSource;
        }

        return null;
    }

    /**
     * Get metadata value
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->meta, $key, $default);
    }

    /**
     * Get request
     *
     * @return Request|null
     */
    public function request(): ?Request
    {
        return $this->request;
    }
}
