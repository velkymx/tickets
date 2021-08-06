@extends('layouts.app')
@section('title')
Milestones List
@stop
<!-- Main Content -->
@section('content')
<h1>Milestones List
  <span class="pull-right"><a href="/milestone/create" class="btn btn-sm btn-primary">Create Milestone</a></span></h1>
  <hr>
  <table class="table table-striped">
<thead>
  <tr>
    <th>Name</th>
    <th>Tickets</th>
    <th>Start</th>
    <th>Due</th>
    <th>Released</th>
    <th>Actions</th>
  </tr>
</thead>
<tbody>
  @foreach ($milestones as $milestone)
<tr>
  <td>{{$milestone->name}}</td>
  <td><span class="badge">{{$milestone->tickets()->whereNotIn('status_id',[5,8,9])->count()}} / {{$milestone->tickets()->count()}}</span></td>

  @if ($milestone->start_at <> '' && $milestone->start_at != '0000-00-00 00:00:00')
  <td>{{date('M jS, Y',strtotime($milestone->start_at))}}</td>
  @else
    <td></td>
  @endif

  @if ($milestone->due_at <> '' && $milestone->due_at != '0000-00-00 00:00:00')
  <td>{{date('M jS, Y',strtotime($milestone->due_at))}}</td>
  @else
    <td></td>
  @endif

  @if ($milestone->end_at <> '' && $milestone->end_at != '0000-00-00 00:00:00')
  <td>{{date('M jS, Y',strtotime($milestone->end_at))}}</td>
  @else
    <td></td>
  @endif
  
  <td align="right"><a href="/milestone/show/{{$milestone->id}}" class="btn btn-sm btn-success">View</a> <a href="/milestone/print/{{$milestone->id}}" class="btn btn-sm btn-default">Print</a> <a href="/milestone/edit/{{$milestone->id}}" class="btn btn-sm btn-primary">Edit</a></td>
  
</tr>
@endforeach
</tbody>
  </table>
@stop
