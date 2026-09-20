<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * A small CRUD engine. Each resource controller declares its field schema and
 * table columns; the engine renders the list and form screens, validates input
 * and handles image/file uploads consistently across the dashboard.
 */
abstract class ResourceController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Route/URI segment, e.g. "publications" (routes are admin.{uri}.*) */
    protected string $uri;

    protected string $title = 'Records';

    protected string $singular = 'Record';

    protected string $description = '';

    protected string $icon = 'layers';

    /** Folder inside public/uploads for this resource's files. */
    protected string $uploadFolder = 'general';

    /** @var array<int, string> Columns included in the search box query. */
    protected array $searchable = ['title'];

    protected string $orderBy = 'id';

    protected string $orderDir = 'desc';

    protected int $perPage = 20;

    /** Optional secondary ordering applied before {@see $orderBy}. */
    protected ?string $primaryOrderBy = null;

    /** The record created or updated by the last store()/update() call. */
    protected ?Model $savedRecord = null;

    /**
     * Field schema for the create/edit form.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function fields(): array;

    /**
     * Columns shown on the index table.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function columns(): array;

    /** Dropdown filters shown above the table: ['field' => ['label' => ..., 'options' => [value => label]]] */
    protected function filters(): array
    {
        return [];
    }

    /** Extra buttons or notes for the index screen. */
    protected function indexNote(): ?string
    {
        return null;
    }

    /** An extra Blade partial rendered under the edit form (e.g. gallery photos). */
    protected function formPartial(?Model $record): ?string
    {
        return null;
    }

    public function index(Request $request)
    {
        $query = $this->newQuery();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchable as $column) {
                    $q->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        foreach ($this->filters() as $field => $filter) {
            if (filled($value = $request->query($field))) {
                $query->where($field, $value);
            }
        }

        if ($this->primaryOrderBy) {
            $query->orderBy($this->primaryOrderBy);
        }

        $records = $query->orderBy($this->orderBy, $this->orderDir)
            ->paginate($this->perPage)
            ->withQueryString();

        return view('admin.resource.index', $this->viewData([
            'records' => $records,
            'columns' => $this->columns(),
            'filters' => $this->filters(),
            'search' => $search,
            'note' => $this->indexNote(),
        ]));
    }

    public function create()
    {
        $model = new $this->model;

        foreach ($this->fields() as $field) {
            if (array_key_exists('default', $field) && $field['type'] !== 'section') {
                $model->{$field['name']} = $field['default'];
            }
        }

        return view('admin.resource.form', $this->viewData([
            'record' => $model,
            'fields' => $this->fields(),
            'action' => route('admin.'.$this->uri.'.store'),
            'method' => 'POST',
            'isNew' => true,
            'partial' => null,
        ]));
    }

    public function store(Request $request, MediaService $media)
    {
        $validated = $request->validate($this->rules(null), [], $this->attributeNames());

        $record = new $this->model;
        $data = $this->dataFromRequest($request, null, $media);
        $record->fill($this->beforeSave($data, $record, $request));
        $record->save();

        $this->savedRecord = $record;
        $this->afterSave($record, $request, $media);

        return redirect()
            ->route('admin.'.$this->uri.'.index')
            ->with('success', $this->singular.' created successfully.');
    }

    public function edit(string|int $key)
    {
        $record = $this->resolve($key);

        return view('admin.resource.form', $this->viewData([
            'record' => $record,
            'fields' => $this->fields(),
            'action' => route('admin.'.$this->uri.'.update', $record),
            'method' => 'PUT',
            'isNew' => false,
            'partial' => $this->formPartial($record),
        ]));
    }

    public function update(Request $request, MediaService $media, string|int $key)
    {
        $record = $this->resolve($key);

        $request->validate($this->rules($record), [], $this->attributeNames());

        $data = $this->dataFromRequest($request, $record, $media);
        $record->fill($this->beforeSave($data, $record, $request));
        $record->save();

        $this->savedRecord = $record;
        $this->afterSave($record, $request, $media);

        return redirect()
            ->route('admin.'.$this->uri.'.index')
            ->with('success', $this->singular.' updated successfully.');
    }

    public function destroy(MediaService $media, string|int $key)
    {
        $record = $this->resolve($key);

        if ($blocked = $this->cannotDelete($record)) {
            return back()->with('error', $blocked);
        }

        foreach ($this->fields() as $field) {
            if (in_array($field['type'], ['image', 'file'], true) && filled($record->{$field['name']})) {
                $media->delete($record->{$field['name']});
            }
        }

        $record->delete();

        return redirect()
            ->route('admin.'.$this->uri.'.index')
            ->with('success', $this->singular.' deleted.');
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */

    protected function newQuery(): Builder
    {
        return $this->model::query();
    }

    protected function resolve(string|int $key): Model
    {
        $model = new $this->model;

        return $model->newQuery()
            ->where($model->getRouteKeyName(), $key)
            ->firstOrFail();
    }

    /** Reason this record may not be deleted, or null when deletion is allowed. */
    protected function cannotDelete(Model $record): ?string
    {
        return null;
    }

    protected function beforeSave(array $data, Model $record, Request $request): array
    {
        return $data;
    }

    protected function afterSave(Model $record, Request $request, MediaService $media): void
    {
        //
    }

    protected function viewData(array $extra = []): array
    {
        return array_merge([
            'uri' => $this->uri,
            'title' => $this->title,
            'singular' => $this->singular,
            'description' => $this->description,
            'resourceIcon' => $this->icon,
        ], $extra);
    }

    /** Build the validation rules from the field schema. */
    protected function rules(?Model $record): array
    {
        $rules = [];

        foreach ($this->fields() as $field) {
            if ($field['type'] === 'section') {
                continue;
            }

            $name = $field['name'];
            $rule = $field['rules'] ?? null;

            if (is_callable($rule)) {
                $rule = $rule($record);
            }

            if ($rule === null) {
                $rule = match ($field['type']) {
                    'image' => ['nullable', 'image', 'max:6144'],
                    'file' => ['nullable', 'file', 'max:20480'],
                    'checkbox' => ['nullable', 'boolean'],
                    'number' => ['nullable', 'numeric'],
                    'date' => ['nullable', 'date'],
                    'datetime' => ['nullable', 'date'],
                    'email' => ['nullable', 'email', 'max:190'],
                    'url' => ['nullable', 'string', 'max:500'],
                    'textarea', 'richtext' => ['nullable', 'string'],
                    default => ['nullable', 'string', 'max:255'],
                };
            }

            $rules[$name] = $rule;
        }

        return $rules;
    }

    protected function attributeNames(): array
    {
        $names = [];

        foreach ($this->fields() as $field) {
            if ($field['type'] !== 'section') {
                $names[$field['name']] = Str::lower($field['label'] ?? $field['name']);
            }
        }

        return $names;
    }

    /** Map the request onto model attributes, handling uploads and checkboxes. */
    protected function dataFromRequest(Request $request, ?Model $record, MediaService $media): array
    {
        $data = [];

        foreach ($this->fields() as $field) {
            $name = $field['name'] ?? null;

            if (! $name || $field['type'] === 'section') {
                continue;
            }

            switch ($field['type']) {
                case 'checkbox':
                    $data[$name] = $request->boolean($name);
                    break;

                case 'image':
                case 'file':
                    if ($request->hasFile($name)) {
                        if ($record && filled($record->{$name})) {
                            $media->delete($record->{$name});
                        }
                        $data[$name] = $media->store($request->file($name), $field['folder'] ?? $this->uploadFolder);
                    } elseif ($request->boolean('remove_'.$name)) {
                        if ($record && filled($record->{$name})) {
                            $media->delete($record->{$name});
                        }
                        $data[$name] = null;
                    }
                    break;

                case 'password':
                    if (filled($request->input($name))) {
                        $data[$name] = $request->input($name);
                    }
                    break;

                case 'number':
                    $value = $request->input($name);
                    $data[$name] = $value === null || $value === '' ? 0 : $value;
                    break;

                default:
                    $value = $request->input($name);
                    $data[$name] = $value === '' ? null : $value;
            }
        }

        return $data;
    }

    /* ------------------------------------------------------------------ */
    /* Field builders — keep resource controllers short and readable */
    /* ------------------------------------------------------------------ */

    protected static function field(string $name, string $label, string $type = 'text', array $extra = []): array
    {
        return array_merge(['name' => $name, 'label' => $label, 'type' => $type, 'col' => 12], $extra);
    }

    protected static function section(string $label): array
    {
        return ['name' => Str::slug($label, '_'), 'label' => $label, 'type' => 'section', 'col' => 12];
    }
}
