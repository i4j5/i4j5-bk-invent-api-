@extends('layouts.app')

@section('content')
<div class="container">
  <h1>ADD</h1>
  <hr>
  <form action="/playments" method="post">
    {{ csrf_field() }}
    <div class="form-group">
      <label for="title">Сумма</label>
      <input class="form-control" value="{{ old('sum') }}" name="sum" type="number" step="0.01" min="0" placeholder="0,00"> ₽
    </div>
    <div class="form-group">
      <label for="title">Оплатить до</label>
      <input class="form-control" type="datetime-local" placeholder="дд.мм.гггг чч:мм" value="{{ old('date') }}" name="date"> 
    </div>
    <div class="form-group">
      <label for="description">Описание</label>
      <textarea class="form-control" rows="3" name="description">{{ old('description') }}</textarea>
    
    </div>
    <div class="form-group">
      <label for="description">ФИО</label>
      <input type="text" class="form-control" value="{{ old('fio') }}" name="fio">
    </div>
    <div class="form-group">
      <label for="description">Телефон</label>
      <input type="text" class="form-control" value="{{ old('phone') }}" name="phone">
    </div>
    <div class="form-group">
      <label for="description">E-mail</label>
      <input type="text" class="form-control" value="{{ old('email') }}" name="email">
    </div>
    <div class="form-group">
      <label for="description">DEAL ID</label>
      <input type="text" class="form-control" value="{{ old('deal_id') }}" name="deal_id">
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