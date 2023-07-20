@extends('layouts.app')
@section('title')
Ticket Board
@endsection
<!-- Main Content -->
@section('content')
<h1>Notes to Tickets</h1>
<p>Describe the worked needed. You can include one or more lists of items and tickets.</p>
<div id="ai-form">
<textarea name="input" id="input" cols="30" rows="10" class="form-control"></textarea>
<br>
<button id="submit" class="btn btn-primary">Process Input</button>
<div id="loading" style="display:none">Loading Results...</div>
</div>
@endsection
@section('javascript')
<script>
    // when the submit button is clicked, pass the data onto the server for processing

    $("#submit").on('click', function() {
        $("#loading").show();
        $("#submit").hide();
        // get the data from the input field
        $input = $("#input").val();

        $.ajax({
            headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
            url: '/tickets/ai/process',
            type: 'POST',
            data: {
                input: $input,                
            },
            success: function(data) {
                // display the data returned from the server
                $("#ai-form").html(data);
            }
        });
    });

</script>
@endsection