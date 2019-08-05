@extends('layouts.app')

@section('content')
    <h1>Edit Page</h1>
    <hr>
    <form action="{{url('pages', [$page->id])}}" method="post">
      <input type="hidden" name="_method" value="PUT">
      {{ csrf_field() }}
      <div class="form-group">
        <label for="title">name</label>
        <input type="text" class="form-control" value="{{$page->name}}" name="name">
      </div>
      <div class="form-group">
        <label for="description">path</label>
        <input type="text" class="form-control" value="{{$page->path}}" name="path">
      </div>
      <div class="form-group">
        <label for="description">content</label>
        <textarea class="form-control control-editor" id="editor" name="content">{{$page->content}}</textarea>

      </div>
      @if ($errors->any())
        <div class="alert alert-danger">
        <ul>
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        </div>
      @endif
      <button type="submit" class="btn btn-primary">Submit</button>
    </form>
@endsection