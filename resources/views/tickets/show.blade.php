@extends('layouts.app')
@section('title')
Ticket #{{$ticket->id}}
@stop
<!-- Main Content -->
@section('content')

  <ol class="breadcrumb">
    <li><a href="/">Home</a></li>
    <li><a href="/tickets/">Tickets</a></li>
    <li class="active">Ticket #{{$ticket->id}}</li>
  </ol>

  @if (session()->has('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif


  @if($ticket->closed_at <> '')
  <div class="alert alert-danger">
    <strong>Closed</strong> This ticket was closed {{date('m/d/Y g:ia',strtotime($ticket->closed_at))}}
  </div>
  @endif
  <div class="row-fluid">
    <div class="col-md-8">
<h2><i class="{{$ticket->type->icon}}" title="{{$ticket->type->name}}"></i> {{$ticket->subject}}</h2>
<hr />
{!!html_entity_decode($ticket->description)!!}
<hr />
<div class="alert alert-info alert-dismissible" role="alert" id="alert" style="display:none">
  <button type="button" class="close" onclick="$('#alert').hide()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <div id="alert_messsage"></div>
</div>

<ul class="nav nav-tabs" role="tablist">
<li role="presentation" class="active"><a href="#messages" aria-controls="messages" role="tab" data-toggle="tab">Notes ({{$ticket->notes()->where('hide','0')->where('notetype','message')->count()}})</a></li>
    <li role="presentation"><a href="#changelog" aria-controls="changelog" role="tab" data-toggle="tab">Changelog ({{$ticket->notes()->where('hide','0')->where('notetype','changelog')->count()}})</a></li>
  </ul>
  <br>
  <div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="messages">
    <?php if($ticket->notes()->where('hide','0')->where('notetype','message')->count() == 0){?>
      <div class="panel panel-default">
  <div class="panel-body">
    No Notes Found
  </div>
</div>
    <?php } ?>
@foreach ($ticket->notes()->where('hide','0')->where('notetype','message')->get() as $note)
<div class="panel panel-default" id="note_{{$note->id}}">
  <div class="panel-heading">
<strong><i class="glyphicon glyphicon-user"></i> {{$note->user->name}}</strong> | posted {{date('M jS, Y g:ia',strtotime($note->created_at))}}
<span class="pull-right"><button onclick="hideNote('{{$note->id}}');" class="btn btn-default btn-xs">Remove</button></span>
</div>
<div class="panel-body">
{!!html_entity_decode($note->body)!!}
</div>
</div>
@endforeach
</div>
<div role="tabpanel" class="tab-pane" id="changelog">
@foreach ($ticket->notes()->where('hide','0')->where('notetype','changelog')->get() as $change)
<div class="panel panel-default" id="note_{{$change->id}}">
  <div class="panel-heading">
<strong><i class="glyphicon glyphicon-user"></i> {{$change->user->name}}</strong> changed ticket {{date('M jS, Y g:ia',strtotime($change->created_at))}}
<span class="pull-right"><button onclick="hideNote('{{$change->id}}');" class="btn btn-default btn-xs">Remove</button></span>
</div>
<div class="panel-body">
{!!html_entity_decode($change->body)!!}
</div>
</div>
@endforeach
</div>
</div>
<hr />
{!! Form::open(['url'=>'notes']) !!}

<div class="form-group">
    {!! Form::label('body', 'Status Update and Notes') !!}
    {!! Form::textarea('body', null, ['class' => 'form-control summernote', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('status_id', 'Change Status') !!}
    {!! Form::select('status_id', $lookups['statuses'], $ticket->status->id, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
<div class="form-group">
    {!! Form::label('hours', 'Add Time or Quantity') !!}
    {!! Form::text('hours', 0, ['class' => 'form-control', 'required' => 'required']) !!}
</div>
{!! Form::hidden('ticket_id',$ticket->id) !!}
{!! Form::submit('Save Note', ['class' => 'btn btn-success']) !!}
{!! Form::close() !!}
</div>
<div class="col-md-4" align="right">
<span class="btn btn-info" id="watch"><i class="far fa-eye"></i> Watch</span>
<a href="/tickets/edit/{{$ticket->id}}" class="btn btn-default"><i class="far fa-edit"></i> Edit</a> 
<a href="/tickets/clone/{{$ticket->id}}" class="btn btn-default"><i class="far fa-copy"></i> Clone</a>
<br /><br />
<div class="panel panel-default">
  <div class="panel-heading">
  Details
</div>
  <div class="panel-body">
    <table class="table">
      @if ($ticket->due_at > 0)
        <tr>
          <td>Due</td>
          <td>{{date('M jS, Y',strtotime($ticket->due_at))}}</td>
        </tr>
      @endif
      <tr>
        <td>Importance</td>
        <td><span class="text-{{$ticket->importance->class}}" title="{{$ticket->importance->name}}"><i class="{{$ticket->importance->icon}}"></i> {{$ticket->importance->name}}</span></td>
      </tr>
      <tr>
        <td>Status</td>
        <td>{{$ticket->status->name}}</td>
      </tr>
      <tr>
        <td>Assignee</td>
        <td><a href="/users/{{$ticket->assignee->id}}">{{$ticket->assignee->name}}</a></td>
      </tr>
      <tr>
        <td>Type</td>
        <td><i class="{{$ticket->type->icon}}" title="{{$ticket->type->name}}"></i> {{$ticket->type->name}}</td>
      </tr>
      <tr>
        <td>Owner</td>
        <td><a href="/users/{{$ticket->user->id}}">{{$ticket->user->name}}</a></td>
      </tr>
      <tr>
        <td>Milestone</td>
        <td><a href="/milestone/show/{{$ticket->milestone->id}}">{{$ticket->milestone->name}}</a></td>
      </tr>
      <tr>
        <td>Project</td>
        <td><a href="/projects/show/{{$ticket->project->id}}">{{$ticket->project->name}}</a></td>
      </tr>
      <tr>
        <td>Story Points</td>
        <td>
        <span class="pull-right btn btn-xs btn-default" onclick="estimate()">Estimate</span>
        
        {{$ticket->storypoints}} Points
        <br>
        <br>
        <ul class="list-group">
        <?php foreach($ticket->userstorypoints as $usp){ ?>
        <li class="list-group-item"><?php echo $usp->storypoints; ?> <?php echo $usp->user->name; ?></li>
        <?php } ?>
        </ul>
        
        </td>
      </tr>    <tr>
        <td>Time Estimate</td>
        <td>{{$ticket->estimate}} hours</td>
      </tr>
      <tr>
        <td>Time Actual</td>
        <td>{{$ticket->notes()->where('hide',0)->sum('hours')}} hours</td>
      </tr>
      <tr>
        <td>Created</td>
        <td>{{date('M jS, Y g:ia',strtotime($ticket->created_at))}}</td>
      </tr>
      <tr>
        <td>Updated</td>
        <td>{{date('M jS, Y g:ia',strtotime($ticket->updated_at))}}</td>
      </tr>
      @if($ticket->closed_at <> '')
      <tr>
        <td>Closed</td>
        <td>{{date('M jS, Y g:ia',strtotime($ticket->closed_at))}}</td>
      </tr>
      @endif
      @foreach ($ticket->watchers as $watcher)
        <tr>
          <td>Watcher</td>
          <td><a href="mailto:{{$watcher->user->email}}?subject=Ticket #{{$ticket->id}}">{{$watcher->user->name}}</a></td>
        </tr>
      @endforeach
      @foreach ($ticket->views()->select([DB::raw('DISTINCT user_id'),DB::raw('max(created_at) as viewed_at')])->groupBy('user_id')->get() as $view)
        <tr>
          <td>User View</td>
          <td>{{$view->user->name}} - {{\Carbon\Carbon::createFromTimeStamp(strtotime($view->viewed_at))->diffForHumans()}}</td>
        </tr>

      @endforeach
    </table>
  </div>
</div>
</div>
</div>

<span id="ticket_id" style="display:none">{{$ticket->id}}</span>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Estimate Story Points</h4>
      </div>
      <form action="/tickets/estimate/<?php echo $ticket->id; ?>" method="post">
      @csrf
      <div class="modal-body">
<?php foreach([
  0 => "No Effort",
  1 => "XS (Extra Small), Dachshund, Kid Hot Chocolate, One",
  2 => "Somewhere between XS and S",
  3 => "S (Small), Terrier, Tall Late, Cookie",  
  5 => "M (Medium), Labrador, Grande Mocha, Cheeseburger",
  8 => "L (Large), Saint Bernard, Vente Iced Late, Cheeseburge with Fries and Soda",
  13 => "Somewhere between L and XL",
  21 => "XL (Extra Large), Great Dane, Trenta Mocha Frap, 5 Course Meal"
] as $est => $label){ ?>
<div class="radio">
  <label>
    <input type="radio" name="storypoints" id="storypoints" value="<?php echo $est; ?>" <?php if($est == 0) echo 'checked'; ?>>
    <?php echo $est; ?> - <?php echo $label; ?>
  </label>
</div>
<?php } ?>

       
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <input type="submit" class="btn btn-primary" value="Save Estimate">
      </div>
      </form>
    </div>
  </div>
</div>

@stop
@section('javascript')
<script src="/js/summernote.min.js"></script>
<script>
    $(function() {
      $( ".datepicker" ).datepicker();
      $('.summernote').summernote({
  height: 200,
  toolbar: [

  ['style', ['fontsize','bold', 'italic', 'underline', 'clear']],
  ['color', ['color']],
  ['para', ['ul', 'ol', 'paragraph','hr','link','picture']]
  ],
  onImageUpload: function(files, editor) {
                  sendFile(files[0],'.summernote');
              }
            });


          $('.summernote2').summernote({
      height: 200,
      toolbar: [

      ['style', ['fontsize','bold', 'italic', 'underline', 'clear']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph','hr','link','picture']]
      ],
      onImageUpload: function(files, editor) {
                      sendFile(files[0],'.summernote2');
                  }
                });
              });

function estimate(){
  $('#myModal').modal()
}

function sendFile(file, editor) {
     data = new FormData();
     data.append("file", file);
     data.append("_token",'{{csrf_token()}}');
     $.ajax({
         data: data,
         type: "POST",
         url: "/tickets/upload/?folder=t{{$ticket->id}}",
         cache: false,
         contentType: false,
         processData: false,
         success: function(url) {
             $(editor).summernote('editor.insertImage', url);
         }
     });
 }

function hideNote(noteid) {

  $("#note_"+noteid).load('/notes/hide/'+noteid);

  $("#note_"+noteid).slideUp();

}

$("#watch").click(

  function(){

    $("#alert_messsage").load('/users/watch/'+$("#ticket_id").html())

    $("#alert").slideDown()

  }

)

</script>
@stop
