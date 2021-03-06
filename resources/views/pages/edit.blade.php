@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Редактирование страницы</h1>
    <hr>
    <form action="{{url('pages', [$page->id])}}" method="post">
      <input type="hidden" name="_method" value="PUT">
      {{ csrf_field() }}
      <div class="form-group">
        <label for="title">Название</label>
        <input type="text" class="form-control" value="{{ old('name', $page->name) }}" name="name">
      </div>
      <div class="form-group">
        <label for="description">Путь</label>
        <input type="text" class="form-control" value="{{ old('path', $page->path) }}" name="path">  
      </div>
      <div class="form-group">
        <label for="description">Контент</label>
        <input type="hidden" class="form-control" id="data-editor" value="{{ old('content', $page->content) }}" name="content">
        <div class="toolbar-container"></div>
        <div id="editor">{!! old('content', $page->content) !!}</div>
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
      <button type="submit" class="btn btn-primary">Сохранить</button>
      &nbsp;&nbsp;
      <a href="{{ URL::to($page->path) }}.html">
        <button type="button" class="btn btn-warning">Просмотр</button>
      </a>
    </form>
</div>
@endsection