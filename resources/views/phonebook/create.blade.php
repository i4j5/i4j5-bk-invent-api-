@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Добавить контакт</h1>
  <hr>
  <form action="/phonebook/store" method="post">
    {{ csrf_field() }}
    <div class="form-group">
      <label for="title">Имя</label>
      <input type="text" class="form-control" value="{{ old('name') }}" name="name">
    </div>
    <div class="form-group">
      {{-- <label for="title"></label> --}}
      <select name="type" class="form-control">
        <option selected value="contact">Контакт</option>
        <option @if (old('type') == 'company') selected @endif value="company"> Компания</option>
      </select>
    </div>
    <div class="form-group">
      <label for="title">Тип</label>
      <select name="group" class="form-control">
        {{-- <option selected value="Клиент">Клиент</option>
        <option value="Подрядчик">Подрядчик</option>
        <option value="Поставщик">Поставщик</option>
        <option value="Партнер">Партнер</option>
        <option value="Конкурент">Конкурент</option> --}}
        @foreach($groups as $el)
            <option value="{{ $el }}" @if ($el == old('group')) selected @endif>{{ $el }}</option>
        @endforeach
      </select>
    </div>
    <br><br>
    <div class="form-group">
      <label for="title">Телефон</label>
      <input type="text" class="form-control" value="{{ old('phone.0') }}" name="phone[]">
    </div>
    <div class="form-group">
      <label for="title">Телефон 2</label>
      <input type="text" class="form-control" value="{{ old('phone.1') }}" name="phone[]">
    </div>
    <div class="form-group">
      <label for="title">Телефон 3</label>
      <input type="text" class="form-control" value="{{ old('phone.2') }}" name="phone[]">
    </div>
    <div class="form-group">
      <label for="title">Телефон 4</label>
      <input type="text" class="form-control" value="{{ old('phone.3') }}" name="phone[]">
    </div>
    <br><br>
    <div class="form-group">
      <label for="title">E-mail</label>
      <input type="text" class="form-control" value="{{ old('email.0') }}" name="email[]">
    </div>
    <div class="form-group">
      <label for="title">E-mail 2</label>
      <input type="text" class="form-control" value="{{ old('email.1') }}" name="email[]">
    </div>
    <div class="form-group">
      <label for="title">E-mail 3</label>
      <input type="text" class="form-control" value="{{ old('email.2') }}" name="email[]">
    </div>
    <div class="form-group">
      <label for="title">E-mail 4</label>
      <input type="text" class="form-control" value="{{ old('email.3') }}" name="email[]">
    </div>
    <br><br>
    <div class="form-group">
      <label for="title">Описание</label>
      <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" name="description">{{ old('description') }}</textarea>
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