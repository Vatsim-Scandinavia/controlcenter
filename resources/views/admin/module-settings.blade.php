@extends('layouts.app')

@section('title', $module->manifest->name . ' Settings')

@section('content')

<div class="row">
    <div class="col-xl-6 col-md-12">

        @if(Session::has('success'))
            <x-cc.alert type="success">{{ Session::pull('success') }}</x-cc.alert>
        @endif

        @if(Session::has('error'))
            <x-cc.alert type="danger">{{ Session::pull('error') }}</x-cc.alert>
        @endif

        <x-cc.form :action="route('admin.modules.settings.store', $slug)">
            <x-cc.card :title="$module->manifest->name . ' Settings'">
                @if($module->manifest->description)
                    <p class="text-muted fs-sm">{{ $module->manifest->description }}</p>
                @endif

                @foreach($schema->fields() as $field)
                    @if($field->secret)
                        <x-cc.secret :name="$field->key" :label="$field->label" :isSet="$secretState[$field->key] ?? false" :help="$field->help" />
                    @elseif($field->type === 'boolean')
                        <x-cc.checkbox :name="$field->key" :label="$field->label" :checked="(bool) ($values[$field->key] ?? false)" :help="$field->help" />
                    @elseif($field->type === 'textarea')
                        <x-cc.textarea :name="$field->key" :label="$field->label" :value="$values[$field->key] ?? $field->default" :help="$field->help" />
                    @elseif($field->type === 'select')
                        <x-cc.select :name="$field->key" :label="$field->label" :options="$field->options" :value="$values[$field->key] ?? $field->default" :help="$field->help" />
                    @else
                        <x-cc.input :name="$field->key" :label="$field->label" :type="$field->type === 'number' ? 'number' : 'text'" :value="$values[$field->key] ?? $field->default" :help="$field->help" />
                    @endif
                @endforeach

                <button class="btn btn-success mt-2" type="submit">Save</button>
            </x-cc.card>
        </x-cc.form>

    </div>
</div>

@endsection
