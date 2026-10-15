@extends('layouts.app')
@section('title', 'Create Project')

@section('content')
<h1 class="mb-4">Create New Project</h1>
<hr>

{{-- Replaced Form::open with standard HTML form. Using POST method. --}}
<form method="POST" action="/projects/store/new" class="form-horizontal" id="project_create_form">
    @csrf 
    {{-- Ensures CSRF protection for the POST request --}}
    <input type="hidden" name="id" value="new">

    {{-- Project Name Field --}}
    <div class="mb-3">
        <label for="name" class="form-label">Project Name</label>
        {{-- Replaced Form::text with standard input. 'null' is replaced by old('name') --}}
        <input type="text" name="name" id="name" value="{{ old('name') }}" 
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="description" class="form-label">Describe Project</label>
        <input type="hidden" name="description" id="description-input" value="{{ old('description') }}">
        <div data-rich-text data-target="#description-input" data-placeholder="Enter the project description here..."></div>
        @error('description')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    {{-- Submit Button --}}
    <div class="d-flex justify-content-end mt-4">
        {{-- Replaced Form::submit and pull-right with standard button and d-flex --}}
        <button type="submit" class="btn btn-success">Save Project</button>
    </div>
</form>
@endsection
