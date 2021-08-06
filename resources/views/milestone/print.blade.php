@extends('layouts.app')
@section('title')
Milestones List
@stop
<!-- Main Content -->
@section('content')
<h1>{{$milestone->name}}</h1>
@if ($milestone->end_at <> '')
<p>Started on {{date('F jS, Y', strtotime($milestone->start_at))}}, Released {{date('F jS, Y', strtotime($milestone->end_at))}}</p>
@else
<p>Unreleased Version - Started on {{date('F jS, Y', strtotime($milestone->start_at))}}</p>
@endif
<?php foreach($types as $type) {

    $tickets = $milestone->tickets()->where('type_id',$type->id)->get();
    
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
@stop