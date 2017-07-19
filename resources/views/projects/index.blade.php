@extends('layouts.app')
@section('title')
Ticket List
@stop
<!-- Main Content -->
@section('content')
<h1>Projects List
  <span class="pull-right"><a href="/projects/create" class="btn btn-sm btn-primary">Create Project</a></span></h1>
  <hr>
  <table class="table table-striped">
<thead>
  <tr>
    <th>Name</th>
    <th>Tickets</th>
    <th></th>
  </tr>
</thead>
<tbody>
  @foreach ($projects as $project)
<tr>
  <td>{{$project->name}}</td>
  <td><span class="badge">{{$project->tickets()->whereIn('status_id',['1','2','3','6'])->count()}}</span></td>
  <td align="right"><a href="/projects/show/{{$project->id}}" class="btn btn-sm btn-success">View</a> <a href="/projects/edit/{{$project->id}}" class="btn btn-sm btn-primary">Edit</a></td>
</tr>
@endforeach
</tbody>
  </table>
@stop
