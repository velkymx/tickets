@extends('layouts.app')
@section('title')
Import Tickets
@stop
<!-- Main Content -->
@section('content')
<h1>Import Tickets</h1>

<p>Columns must be:</p>
<p>Ticket Type, Ticket Subject, Ticket Details, Ticket Importance, Ticket Status, Ticket Project, Assigned to</p>


{!! Form::open(['url' => '/tickets/import', 'files' => true]) !!}
    <div class="form-group">
        {!! Form::label('milestone_id', 'Ticket Milestone') !!}
        {!! Form::select('milestone_id', $milestones, 1, ['class' => 'form-control', 'required' => 'required']) !!}
    </div>
    <div class="form-group">
        {{ Form::label('csv', 'CSV File') }} 
        {{ Form::file('csv', ['class' => 'form-control-file', 'required' => 'required']) }}
    </div>
    <div class="form-check">
        {{ Form::checkbox('hasHeader', true, true, ['class' => 'form-check-label']) }}
        {{ Form::label('hasHeader', 'Has Header Row', ['class' => 'form-check-input']) }} 
    </div>
    <div class="form-group">
        {{ Form::submit('Import', ['class' => 'btn btn-info']) }}
    </div>
{!! Form::close() !!}
@endsection

@section('javascript')
<style>
</style>
<script>
</script>
@endsection
