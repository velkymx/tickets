@extends('layouts.app')
@section('title')
{{$project->name}} Ticket List
@stop
<!-- Main Content -->
@section('content')
<h1>{{$project->name}} Ticket List
  <span class="pull-right">
   <a href="/projects/edit/{{$project->id}}" class="btn btn-sm btn-primary">Edit Project</a>
  </span>
</h1>
<h2>Progress: {{$percent}}% Complete</h2>
<div class="progress">
  <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width:{{$percent}}%;">
    <span class="sr-only">{{$percent}}% Complete</span>
  </div>
</div>
<div class="row-fluid">
  <div class="col-xs-1 ticketbadge" align="center">
    <h2>{{$project->tickets->count()}}</h2>
    <small>Total Tickets</small>
  </div>
  @foreach ($statuscodes as $code)
    <div class="col-xs-1 ticketbadge" align="center">
      <h2>{{$project->tickets()->where('status_id',$code->id)->count()}}</h2>
      <small>{{$code->name}}</small>
    </div>
  @endforeach
</div>
<br clear="all">
<hr>
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
    <td class="text-{{$tick->importance->class}}"><i class="{{$tick->type->icon}}" title="{{$tick->type->name}}"></i> <a href="/tickets/{{$tick->id}}" class="text-{{$tick->importance->class}}">#{{$tick->id}} {{$tick->subject}}</a></td>        
        <td><span class="text-{{$tick->importance->class}}" title="Priority: {{$tick->importance->name}}"><i class="{{$tick->importance->icon}}"></i></span></td>
      <td align="center"><span class="label label-base">{{$tick->status->name}}</span></td>
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
{!! $tickets->appends($queryfilter)->render() !!}
@stop
