@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Добавить новую стоницу</h1>
  <hr>
  <form action="/pages" method="post">
    {{ csrf_field() }}
    <div class="form-group">
      <label for="title">Название</label>
      <input type="text" class="form-control" id="" name="name">
    </div>
    <div class="form-group">
      <label for="description">Путь</label>
      <input type="text" class="form-control" name="path">
    </div>
    <div class="form-group">
      <label for="description">Контент</label>
      <textarea class="form-control"  name="content"></textarea>
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
    <button type="submit" class="btn btn-primary">Добавить</button>
  </form>
</div>
@endsection