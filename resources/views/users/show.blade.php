@extends('layouts.app')
@section('title')
User Tickets
@stop
@section('content')
<h1>{{$user->name}}</h1>
@if ($user->title)
<p><strong>{{$user->title}}</strong></p>
@endif
@if ($user->email)
<p><strong>Email:</strong> <a href="mailto:{{$user->email}}">{{$user->email}}</a></p>
@endif
@if ($user->phone)
<p><strong>Phone Number:</strong> <a href="tel:{{$user->phone}}">{{$user->phone}}</a></p>
@endif
@if ($currenttime)
<p><strong>Local time:</strong> {{$currenttime}}</p>
@endif
@if ($user->bio)
<?php echo $user->bio; ?>
@endif
<hr />
@foreach ($alltickets as $label => $tickets)
  <h3>{{ucwords($label)}}</h3>
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
      <td align="center"><span class="label label-default">{{$tick->status->name}}</span></td>
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
@endforeach

@stop
