@extends('layouts.app')
@section('title')
Import Tickets
@stop
@section('content')
<h1 class="mb-4">Import Tickets</h1>

<p class="mb-1">Columns must be:</p>
<p class="mb-4 text-muted">Type Name, Subject, Details, Importance Name, Status Name, Project Name, Assigned To User Name</p>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ url('/tickets/import') }}" enctype="multipart/form-data" class="bg-white p-4 rounded-3 shadow">
    @csrf
    <div class="mb-3">
        <label for="milestone_id" class="form-label">Ticket Milestone</label>
        <select name="milestone_id" id="milestone_id" class="form-select" required>
            <option value="">Select</option>
            @foreach ([null=>'Select'] + $milestones->toArray() as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="csv" class="form-label">CSV File</label> 
        <input type="file" name="csv" id="csv" class="form-control" required>
    </div>
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="hasHeader" id="hasHeader" value="1" checked>
        <label class="form-check-label" for="hasHeader">Has Header Row</label>
    </div>
    <div class="mb-3">
        <button type="submit" class="btn btn-info text-white">Import</button>
    </div>
</form>
@endsection

@section('javascript')
<style>
</style>
<script>
</script>
@endsection