@extends('layouts.app')
@section('title')
Create Release
@stop
@section('content')
<h1>Create Release</h1>
<hr>
<form method="POST" action="/release/store" class="form-horizontal" id="formRelease">
    @csrf 

    <div class="mb-3">
        <label for="title" class="form-label">Release Title</label>
        <input type="text" name="title" id="title" value="{{ old('title') }}" class="form-control" required>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="started_at" class="form-label">Start Date</label>
                <input type="date" name="started_at" id="started_at" value="{{ old('started_at', date('Y-m-d')) }}" class="form-control" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="completed_at" class="form-label">Release Date</label>
                <input type="date" name="completed_at" id="completed_at" value="{{ old('completed_at') }}" class="form-control">
            </div>
        </div>    
    </div>
    <div class="mb-3">
        <label for="editor-container" class="form-label">Describe Release</label>
        <div id="editor-container" style="height: 250px;">
        </div>
        <input type="hidden" name="body" id="description-input" value="{{ old('body') }}">
    </div>

<div class="alert alert-info"><i class="fas fa-info-circle"></i> Add tickets to your release from the All Tickets tab using the bulk update feature.</div>
<div class="d-flex justify-content-end mt-4">
    <button type="submit" class="btn btn-success">Save Release</button>
</div>
</form>
@endsection
@section('javascript')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        const quillToolbarOptions = [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }], 
            ['bold', 'italic', 'underline', 'strike'], 
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'color': [] }, { 'background': [] }], 
            ['link', 'image', 'video'],
            ['clean']
        ];

        const quill = new Quill('#editor-container', {
            modules: { toolbar: quillToolbarOptions },
            theme: 'snow',
            placeholder: 'Describe the release contents and goals here...'
        });
        
        const initialContentInput = document.getElementById('description-input');
        
        if (initialContentInput && initialContentInput.value) {
            quill.clipboard.dangerouslyPasteHTML(initialContentInput.value);
        }

        const form = document.getElementById('formRelease');
        const hiddenInput = initialContentInput;

        form.addEventListener('submit', function() {
            hiddenInput.value = quill.root.innerHTML;
        });
    });
</script>
@endsection