@extends('layouts.app')
@section('title')
{{$milestone->name}} Ticket List
@stop
<!-- Main Content -->
@section('content')
<h1>{{$milestone->name}}</h1>
@if ($milestone->end_at <> '')
<p>Started on {{date('F jS, Y', strtotime($milestone->start_at))}}, Released {{date('F jS, Y', strtotime($milestone->end_at))}}</p>
@else
<p>Unreleased Version - Started on {{date('F jS, Y', strtotime($milestone->start_at))}}</p>
@endif
<?php 

foreach($projects as $project_id => $project) {

    echo "<h3><i class='fas fa-folder'></i> $project</h3><hr>";

foreach($types as $type) {

    $tickets = $milestone->tickets()->where('type_id',$type->id)->whereNotIn('status_id',[8,9])->where('project_id',$project_id)->orderBy('subject')->get();
    
    if(count($tickets)>0){ 
    
    ?>
<div class="row">
    <div class="col-md-3"><h3><i class="{{$type->icon}}" title="{{$type->name}}"></i> {{$type->name}}s</h3></div>
    <div class="col-md-9">
    <ul class="list-group">
        <?php foreach($tickets as $ticket){ ?>
            <li class="list-group-item"><?php echo $ticket->subject;?> (<a href="/tickets/<?php echo $ticket->id;?>">#<?php echo $ticket->id;?></a>)</li>
            <?php } ?>
    </ul>
    </div>
</div>
<?php }

    }

?>
<br><br>
<?php
    }
?>
<div>Milestone {{$milestone->name}} Ticket List Generated <?php echo date('F dS, Y H:i'); ?></div>
@stop