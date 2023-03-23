@extends('layouts.app')
@section('title')
{{$milestone->name}} Milestone
@stop
<!-- Main Content -->
@section('content')
  <h1>{{$milestone->name}} Milestone
  <span class="pull-right">
 <a href="/milestone/edit/{{$milestone->id}}" class="btn btn-sm btn-primary">Edit Milestone</a>
 <a href="/milestone/print/{{$milestone->id}}" class="btn btn-sm btn-primary">Print</a>
</span>

  </h1>
  <div class="row">
  <div class="col-md-9">
 
  <ul class="nav nav-tabs" role="tablist">        
    <?php  
 
    $i = 0;

    $available_status = [];

    foreach ($statuscodes as $code_id => $code) {       

      if(in_array($code_id,[5,8,9])) continue;
      
      if($milestone->tickets()->where('status_id',$code_id)->count() == 0) continue;

      $available_status[$code_id] = $code['slug'];

      $i++;

      ?>
    <li role="{{$code['slug']}}" <?php if($i == 1) echo ' class="active"'; ?>><a href="#{{$code['slug']}}" aria-controls="{{$code['slug']}}" role="tab" data-toggle="tab">{{$code['name']}} <span class="badge">{{$milestone->tickets()->where('status_id',$code_id)->count()}}</span></a></li>    
    <?php } ?>
    <li role="all"><a href="#all" aria-controls="all" role="tab" data-toggle="tab">All Tickets <span class="badge">{{$milestone->tickets->count()}}</span></a></li>
    <li role="all"><a href="#closed" aria-controls="closed" role="tab" data-toggle="tab">Closed <span class="badge">{{$milestone->tickets->whereIn('status_id',['5','8','9'])->count()}}</span></a></li>
  </ul>

  <div class="tab-content">
  <?php 
  $i = 0;
  
  foreach($available_status as $st => $code){

    $i++;
    
    ?>
    <div role="tabpanel" class="tab-pane <?php if($i == 1) echo ' active'; ?>" id="{{$code}}">
    <table class="table table-striped">
    <thead>
      <tr>
        <th>Title</th>        
        <th>P</th>
        <th>Status</th>
        <th>Project</th>
        <th>Assignee</th>
        <th>Est</th>
        <th>Notes</th>        
        <th>Updated</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($milestone->tickets->where('status_id',$st)->sortByDesc('importance_id') as $tick)
      <tr>
        <td class="text-{{$tick->importance->class}}"><i class="{{$tick->type->icon}}" title="{{$tick->type->name}}"></i> <a href="/tickets/{{$tick->id}}" class="text-{{$tick->importance->class}}">#{{$tick->id}} {{$tick->subject}}</a></td>        
        <td><span class="text-{{$tick->importance->class}}" title="Priority: {{$tick->importance->name}}"><i class="{{$tick->importance->icon}}"></i></span></td>
        <td align="center"><span class="label label-default">{{$tick->status->name}}</span></td>
        <td>{{$tick->project->name}}</td>
        <td>{{$tick->assignee->name}}</td>
        <td><span class="label label-default">{{$tick->storypoints}}SP</span></td>
        <td>
          @if ($tick->notes()->where('hide','0')->where('notetype','message')->count() > 0)
            <span class="badge">{{$tick->notes()->where('hide','0')->where('notetype','message')->count()}}</span>
          @endif
      </td>        
        <td>{{date('M jS, Y g:ia',strtotime($tick->updated_at))}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>    
    </div>
    <?php } ?>
  
  <div role="tabpanel" class="tab-pane" id="all">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Title</th>        
        <th>P</th>
        <th>Status</th>
        <th>Project</th>
        <th>Assignee</th>
        <th>Est</th>
        <th>Notes</th>        
        <th>Updated</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($milestone->tickets->sortByDesc('importance_id') as $tick)
      <tr>
        <td class="text-{{$tick->importance->class}}"><i class="{{$tick->type->icon}}" title="{{$tick->type->name}}"></i> <a href="/tickets/{{$tick->id}}" class="text-{{$tick->importance->class}}">#{{$tick->id}} {{$tick->subject}}</a></td>        
        <td><span class="text-{{$tick->importance->class}}" title="Priority: {{$tick->importance->name}}"><i class="{{$tick->importance->icon}}"></i></span></td>
        <td align="center"><span class="label label-default">{{$tick->status->name}}</span></td>
        <td>{{$tick->project->name}}</td>
        <td>{{$tick->assignee->name}}</td>
        <td><span class="label label-default">{{$tick->storypoints}}SP</span></td>
        <td>
          @if ($tick->notes()->where('hide','0')->where('notetype','message')->count() > 0)
            <span class="badge">{{$tick->notes()->where('hide','0')->where('notetype','message')->count()}}</span>
          @endif
      </td>        
        <td>{{date('M jS, Y g:ia',strtotime($tick->updated_at))}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  </div>
  <div role="tabpanel" class="tab-pane" id="closed">
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Title</th>        
        <th>P</th>
        <th>Status</th>
        <th>Project</th>
        <th>Assignee</th>
        <th>Est</th>
        <th>Notes</th>        
        <th>Updated</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($milestone->tickets->whereIn('status_id',['5','8','9'])->sortByDesc('importance_id') as $tick)
      <tr>
        <td class="text-{{$tick->importance->class}}"><i class="{{$tick->type->icon}}" title="{{$tick->type->name}}"></i> <a href="/tickets/{{$tick->id}}" class="text-{{$tick->importance->class}}">#{{$tick->id}} {{$tick->subject}}</a></td>        
        <td><span class="text-{{$tick->importance->class}}" title="Priority: {{$tick->importance->name}}"><i class="{{$tick->importance->icon}}"></i></span></td>
        <td align="center"><span class="label label-default">{{$tick->status->name}}</span></td>
        <td>{{$tick->project->name}}</td>
        <td>{{$tick->assignee->name}}</td>
        <td><span class="label label-default">{{$tick->storypoints}}SP</span></td>
        <td>
          @if ($tick->notes()->where('hide','0')->where('notetype','message')->count() > 0)
            <span class="badge">{{$tick->notes()->where('hide','0')->where('notetype','message')->count()}}</span>
          @endif
      </td>        
        <td>{{date('M jS, Y g:ia',strtotime($tick->updated_at))}}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  </div>
  </div>
  </div>
  <div class="col-md-3">
  <ul class="list-group">
    @if ($milestone->owner)
  <li class="list-group-item">Product Owner: {{$milestone->owner->name}}</li>
  @endif
  @if ($milestone->scrummaster)
  <li class="list-group-item">Scrum Master: {{$milestone->scrummaster->name}}</li>
  @endif
  <li class="list-group-item">Team Members</li>
  <?php 
  $mem = array();
  
  foreach ($milestone->tickets as $tick) {
    
    if(!in_array($tick->assignee->name,$mem)){

      $mem[] = $tick->assignee->name;
    
    ?>
  <li class="list-group-item"><a href="/users/{{$tick->assignee->id}}"><i class="fas fa-user"></i> {{$tick->assignee->name}}</a></li>
  <?php } ?>
  <?php } ?>
  <li class="list-group-item"><strong>Sprint Summary</strong></li>
  <li class="list-group-item">Total Tickets: {{$milestone->tickets->count()}}</li>
  <li class="list-group-item">Estimated Effort: {{$milestone->tickets->sum('storypoints')}} Story Points</li>
  <li class="list-group-item">Estimated Time: {{$milestone->tickets->sum('estimate')}} Hours</li>
  <li class="list-group-item"><strong>Completed</strong></li>
  <li class="list-group-item">
  <strong>Progress: {{$percent}}% Complete</strong>
  <div class="progress">
    <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width:{{$percent}}%;">
      <span class="sr-only">{{$percent}}% Complete</span>
    </div>
  </div>
  </li>
  <li class="list-group-item">Closed Tickets: {{$milestone->tickets()->whereIn('status_id',[5,9])->count()}}</li>
  <li class="list-group-item">Actual Time: {{$milestone->tickets->sum('actual')}} Hours</li>

  </ul>
  </div>
  </div>
  <style>
  .progress {margin-bottom: 0;}
  </style>
@stop
