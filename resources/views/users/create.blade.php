@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Добавить пользователя</h1>
    <hr>
    <form action="{{url('users')}}" method="post">
        {{ csrf_field() }}

        <div class="form-group">
            <label for="title">Имя</label>
            <input type="text" class="form-control" value="{{ old('name') }}" name="name">
        </div>

        <div class="form-group">
            <label for="title">E-mail</label>
            <input type="text" class="form-control" value="{{ old('email') }}" name="email">
        </div>

        <div class="form-group">
            <label for="title">Добавочный номер</label>
            <input type="text" class="form-control" value="{{ old('extension_phone_number') }}" name="extension_phone_number">
        </div>

        <div class="form-group">
            <label for="title">amo id</label>
            <input type="text" class="form-control" value="{{ old('amo_user_id') }}" name="amo_user_id">
        </div>

        <div class="form-group">
            <label for="title">asana id</label>
            <input type="text" class="form-control" value="{{ old('asana_user_id') }}" name="asana_user_id">
        </div>

        <div class="form-group">
            <label for="title">uis id</label>
            <input type="text" class="form-control" value="{{ old('uis_id') }}" name="uis_id">
        </div>

        <div class="form-group">
          <label for="title">Роль</label>
          <select name="is_admin" class="form-control">
            <option value="0">Сотрудник</option>

            <option value="1" @if ( old('is_admin') ) selected @endif>Администратор</option>
          </select>
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