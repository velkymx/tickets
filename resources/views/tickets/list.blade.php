@extends('layouts.app')
@section('title')
Ticket List
@stop
<!-- Main Content -->
@section('content')
<h1>Ticket List
<span class="pull-right"><a href="/tickets/create" class="btn btn-sm btn-primary">Create Ticket</a></span></h1>
{!! Form::open(['method' => 'POST', 'url' => 'tickets/batch', 'class' => 'form']) !!}
<table class="table table-striped">
  <thead>
    <tr>
      <th>Title</th>
      <th>T</th>
      <th>P</th>
      <th>Status</th>
      <th>Project</th>
      <th>Assignee</th>
      <th>Notes</th>
      <th>Created</th>
      <th>Updated</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($tickets as $tick)
    <tr>
      <td><input type="checkbox" name="tickets[{{$tick->id}}]" value="{{$tick->id}}"> &nbsp; <a href="/tickets/{{$tick->id}}">#{{$tick->id}} {{$tick->subject}}</a></td>
      <td>{{$tick->type->name}}</td>
      <td>{{$tick->importance->name}}</td>
      <td align="center"><span class="label label-base">{{$tick->status->name}}</span></td>
      <td>{{$tick->project->name}}</td>
      <td>{{$tick->assignee->name}}</td>
      <td>
        @if ($tick->notes()->where('hide','0')->count() > 0)
          <span class="badge">{{$tick->notes()->where('hide','0')->count()}}</span>
        @endif
    </td>
      <td>{{date('M jS, Y g:ia',strtotime($tick->created_at))}}</td>
      <td>{{date('M jS, Y g:ia',strtotime($tick->updated_at))}}</td>
    </tr>
    @endforeach
  </tbody>
</table>
<hr>
<h2>Batch Update Checked</h2>
<div class="form-group">
    {!! Form::label('type_id', 'Ticket Type') !!}
    {!! Form::select('type_id', $lookups['types'],0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('importance_id', 'Ticket Importance') !!}
    {!! Form::select('importance_id', $lookups['importances'],0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('milestone_id', 'Ticket Milestone') !!}
    {!! Form::select('milestone_id', $lookups['milestones'],0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('status_id', 'Ticket Status') !!}
    {!! Form::select('status_id', $lookups['statuses'],0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('project_id', 'Ticket Project') !!}
    {!! Form::select('project_id', $lookups['projects'],0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('user_id2', 'Assign To') !!}
    {!! Form::select('user_id2', $lookups['users'],0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
{!! Form::submit('Save and Update Checked Tickets', ['class' => 'btn btn-info pull-right']) !!}
{!! Form::close() !!}
{!! $tickets->appends($queryfilter)->render() !!}
<style>
.label-base {
border: 1px solid #2e6da4;
border-radius: 3px;
color:#2e6da4

}
</style>
@stop
