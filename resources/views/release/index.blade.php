@extends('layouts.app')
@section('title')
Releases List
@stop
<!-- Main Content -->
@section('content')
<h1>Releases
  <span class="pull-right"><a href="/releases/create" class="btn btn-sm btn-primary">Create Release</a></span></h1>
  <hr>
  <?php if(count($releases) == 0){ ?>
    <div class="panel panel-default">
  <div class="panel-body" align="center">
      <h2>No Releases Found</h2>
      <p>Release notes is a document, which is released as part of the final build that contains new enhancements that went in as part of that release and also the known issues of that build.</p>
      <a href="/releases/create" class="btn btn-sm btn-primary">Create A New Release</a>
    
    </div>
    </div>
<?php } else { ?>
  <table class="table table-striped">
<thead>
  <tr>
    <th>Name</th>
    <th>Tickets</th>
    <th>Date</th>
    <th></th>
  </tr>
</thead>
<tbody>
  @foreach ($releases as $release)
<tr>
  <td>{{$release->title}}</td>
  <td><span class="badge">{{$release->tickets()->count()}}</span></td>
  <td>{{date_format(date_create($release->completed_at),'Y-m-d')}}</td>
  <td align="right"><a href="/release/{{$release->id}}" class="btn btn-sm btn-success">View</a> <a href="/release/edit/{{$release->id}}" class="btn btn-sm btn-primary">Edit</a></td>
</tr>
@endforeach
</tbody>
  </table>
  <?php } ?>
@stop
