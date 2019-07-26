@extends('layouts.app')

@section('content')
  <h1>Add New Page</h1>
  <hr>
  <form action="/pages" method="post">
    {{ csrf_field() }}
    <div class="form-group">
      <label for="title">name</label>
      <input type="text" class="form-control" id="" name="name">
    </div>
    <div class="form-group">
      <label for="description">path</label>
      <input type="text" class="form-control" name="path">
    </div>
    <div class="form-group">
      <label for="description">content</label>
      <input type="text" class="form-control"  name="content">
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