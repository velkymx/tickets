@extends('layouts.app')
@section('title')
Ticket List
@stop
<!-- Main Content -->
@section('content')
<h1>Ticket List
<span class="pull-right"><a href="/tickets/create" class="btn btn-sm btn-primary">Create Ticket</a></span></h1>
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
      <td><a href="/tickets/{{$tick->id}}">#{{$tick->id}} {{$tick->subject}}</a></td>
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
{!! $tickets->appends($queryfilter)->render() !!}
<style>
.label-base {
border: 1px solid #2e6da4;
border-radius: 3px;
color:#2e6da4

}
</style>
@stop
