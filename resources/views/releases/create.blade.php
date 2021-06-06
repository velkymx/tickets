@extends('layouts.app')
@section('title')
Create Release
@endsection
<!-- Main Content -->
@section('content')
<h1>Create Release</h1>
<form action="/release/store" class="form form-horizonal">
  <div class="form-group">
    <label for="started_at">Start Date</label>
    <input type="date" class="form-control" id="started_at" placeholder="01/02/2019">
  </div>
  <div class="form-group">
    <label for="completed_at">Release Date</label>
    <input type="date" class="form-control" onblur="fetchTickets()" id="completed_at" placeholder="02/02/2019">
  </div>  
  <div class="form-group">
    <label for="title">Title</label>
    <input type="text" class="form-control" id="title" placeholder="Release 1.0.0">
  </div>
  <div class="row col-md-12">
      <h3>Select Tickets</h3>
      <ul class="list-group" id="select-tickets"></ul>
  </div>



  <div class="form-group">
    <label for="body">Release Notes</label>  
  <textarea class="form-control summernote" rows="3" name="body" placeholder="Release Notes"></textarea>
  </div>
</form>
@endsection
@section('javascript')
<script src="/js/summernote.min.js"></script>
<script>
    
    function fetchTickets(){
        let started_at = $("#started_at").val()
        let completed_at = $("#completed_at").val()

        $("#select-tickets").empty()

        $.getJSON(`/tickets/fetch/?started_at=${started_at}&completed_at=${completed_at}`, function( data ) {
            
        var items = [];
        $.each( data.data, function( key, val ) {

            items.push(`
                <li class="list-group-item checkbox">
                <label>
                <input type="checkbox" name="tickets[]" id="${val.id}"> ${val.subject}
                </label>
                </li>
            `)

        });
        
        $("#select-tickets").append(items);

    });        
    }

    $(function() {
      $( ".datepicker" ).datepicker();
      $('.summernote').summernote({
        height: 300,
        onImageUpload: function(files, editor) {
                        sendFile(files[0],'.summernote');
                    }
            });
        });

function sendFile(file, editor) {
     data = new FormData();
     data.append("file", file);
     data.append("_token",'{{csrf_token()}}');
     $.ajax({
         data: data,
         type: "POST",
         url: "/tickets/upload",
         cache: false,
         contentType: false,
         processData: false,
         success: function(url) {
             $(editor).summernote('editor.insertImage', url);
         }
     });
 }
</script>
@stop
