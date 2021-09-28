@extends('layouts.app')
@section('title')
Ticket List
@stop
<!-- Main Content -->
@section('content')
<h1>Tickets</h1>
{!! Form::open(['method' => 'GET', 'url' => 'tickets', 'class' => 'form']) !!}
<table class="table">
<tr>
<td><span class="btn btn-text">Filter Tickets</span></td>
<td><input type="text" placeholder="search" class="form-control" name="q"></td>
<td>
<select name="perpage" id="perpage" class="form-control">
<option value=""># Rows</option>X
<option value="10">10 Rows</option>X
<option value="20">20 Rows</option>
<option value="30">30 Rows</option>
<option value="40">40 Rows</option>
<option value="50">50 Rows</option>
</select>
</td>
<td>
{!! Form::select('status_id', $viewfilters['statuses'], $filter['status_id'], ['class' => 'form-control', 'required' => 'required']) !!}
</td>
<td>
{!! Form::select('milestone_id', $viewfilters['milestones'], $filter['milestone_id'], ['class' => 'form-control', 'required' => 'required']) !!}
</td>
<td>
{!! Form::select('type_id', $viewfilters['types'], $filter['type_id'], ['class' => 'form-control', 'required' => 'required']) !!}
</td>
<td>
<input type="submit" value="Refresh Rows" class="btn btn-primary">
</td>
</tr>
</table>
{!! Form::close() !!}
{!! Form::open(['method' => 'POST', 'url' => 'tickets/batch', 'class' => 'form']) !!}
<table class="table table-striped">
  <thead>
    <tr>
      <th>Title</th>
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
    @foreach ($tickets->sortByDesc('importance_id') as $tick)
    <tr>
    <td class="text-{{$tick->importance->class}}"><input type="checkbox" name="tickets[{{$tick->id}}]" value="{{$tick->id}}"> <i class="{{$tick->type->icon}}" title="{{$tick->type->name}}"></i> <a href="/tickets/{{$tick->id}}" class="text-{{$tick->importance->class}}">#{{$tick->id}} {{$tick->subject}}</a></td>        
        <td><span class="text-{{$tick->importance->class}}" title="Priority: {{$tick->importance->name}}"><i class="{{$tick->importance->icon}}"></i></span></td>
      <td align="center"><span class="label label-default">{{$tick->status->name}}</span></td>
      <td>{{$tick->project->name}}</td>
      <td>{{$tick->assignee->name}}</td>
      <td>
        @if ($tick->notes()->where('hide','0')->where('notetype','message')->count() > 0)
          <span class="badge">{{$tick->notes()->where('hide','0')->where('notetype','message')->count()}}</span>
        @endif
    </td>
      <td>{{date('M jS, Y g:ia',strtotime($tick->created_at))}}</td>
      <td>{{date('M jS, Y g:ia',strtotime($tick->updated_at))}}</td>
    </tr>
    @endforeach
  </tbody>
</table>
<span class="btn btn-danger" id="checkAll">Check All</span>

{!! $tickets->appends($queryfilter)->links() !!}

<br clear="all"
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
<div class="form-group">
    {!! Form::label('release_id', 'Add to Release') !!}
    {!! Form::select('release_id', $lookups['releases'],0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
{!! Form::submit('Save and Update Checked Tickets', ['class' => 'btn btn-info pull-right']) !!}
{!! Form::close() !!}

@section('javascript')
<script>
 $('#checkAll').click(function () {    
  if (! $('input:checkbox').is('checked')) {
      $('input:checkbox').attr('checked','checked');
  } 
 });
</script>
@stop
@stop
