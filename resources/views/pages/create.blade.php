@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Добавить новую стоницу</h1>
  <hr>
  <form action="/pages" method="post">
    {{ csrf_field() }}
    <div class="form-group">
      <label for="title">Название</label>
      <input type="text" class="form-control" value="{{ old('name') }}" name="name">
    </div>
    <div class="form-group">
      <label for="description">Путь</label>
      <input type="text" class="form-control" value="{{ old('path') }}" name="path">
    </div>
    <div class="form-group">
        <label for="description">Контент</label>
        <input type="hidden" class="form-control" id="data-editor" value="{{ old('content') }}" name="content">
        <div class="toolbar-container"></div>
        <div id="editor">{!! old('content') !!}</div>
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