@extends('layouts.app')

@section('content')
<div class="container">
    <h1>------</h1>
    <hr>
    <form action="{{url('pages/menu')}}" method="post">
      {{ csrf_field() }}
      <div class="form-group">
        <input type="hidden" class="form-control" id="data-editor" value="{{ old('value', $menu->value) }}" name="value">
        <div class="toolbar-container"></div>
        <div id="editor">{!! old('value', $menu->value) !!}</div>
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
    </form>
</div>
@endsection