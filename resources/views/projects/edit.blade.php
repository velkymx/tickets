@extends('layouts.app')
@section('title', 'Edit Project')

@section('content')
<h1 class="mb-4">Edit Project</h1>
<hr>

{{-- Replaced Form::open with standard HTML form --}}
<form method="POST" action="/projects/store/{{ $project->id }}" class="form-horizontal" id="project_edit_form">
    @csrf 
    {{-- Assuming CSRF is handled by Laravel's @csrf directive --}}
    <input type="hidden" name="id" value="{{ $project->id }}">

    {{-- Project Name Field --}}
    <div class="mb-3">
        <label for="name" class="form-label">Project Name</label>
        {{-- Replaced Form::text with standard input --}}
        <input type="text" name="name" id="name" value="{{ old('name', $project->name) }}" 
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Describe Project</label>
        <input type="hidden" name="description" id="description-input" value="{{ old('description', $project->description) }}">
        <div data-rich-text data-target="#description-input" data-placeholder="Enter the project description here..."></div>
        @error('description')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Active Status Field --}}
    <div class="mb-3">
        <label for="active" class="form-label">Active</label>
        {{-- Replaced Form::select with standard select --}}
        <select name="active" id="active" class="form-select @error('active') is-invalid @enderror" required>
            <option value="0" @if (old('active', $project->active) == '0') selected @endif>Inactive</option>
            <option value="1" @if (old('active', $project->active) == '1') selected @endif>Active</option>
        </select>
        @error('active')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end mt-4">
        {{-- Replaced Form::submit and pull-right with standard button and d-flex --}}
        <button type="submit" class="btn btn-success">Save Project</button>
    </div>
</form>
@endsection
