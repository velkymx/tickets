@extends('layouts.app')
@section('title')
Releases List
@stop
<!-- Main Content -->
@section('content')
<h1>{{$release->title }}</h1>
<hr>
<div class="row-fluid">
Release Date: <?php echo date_format(date_create($release->completed_at),'m/d/Y'); ?>
</div>
<hr>
<div class="row-fluid">
<?php echo $release->body; ?>
</div>
<hr>
<?php if($release->tickets->count() == 0){ ?>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Add tickets to your release from the All Tickets tab using the bulk update feature.</div>   
<?php } else { ?>
  <?php 

foreach($projects as $project) {

    echo "<h3><i class='fas fa-folder'></i> {$project['project']}</h3><hr>";

foreach($project['tickets'] as $type => $tickets) {    
    
    if(count($tickets) >0){ 
    
    ?>
<div class="row">
    <div class="col-md-3"><h3><i class="{{$types[$type]}}" title="{{$type}}"></i> {{$type}}s</h3></div>
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
<?php } ?>
@stop