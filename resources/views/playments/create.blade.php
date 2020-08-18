@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Выставить счёт</h1>
  <hr>
  <form action="/playments" method="post">
    {{ csrf_field() }}

    <div class="row">
      <div class="col-lg-6 col-mb-12 col-sm-6">
        <div class="form-group">
          <label for="title">Сумма</label>
          <div  class="input-group">
            <input class="form-control" value="{{ old('sum') }}" name="sum" type="number" step="0.01" min="0" placeholder="0,00"> 
            <div class="input-group-append">
              <span class="input-group-text">₽</span>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 col-mb-12 col-sm-6">
        <div class="form-group">
          <label for="title">Оплатить до</label>
          <input class="form-control" type="datetime-local" placeholder="дд.мм.гггг чч:мм" value="{{ old('date') }}" name="date"> 
        </div>
      </div>
    </div>
    <div class="form-group">
      <label for="description">Описание</label>
      <input type="text" class="form-control" value="{{ old('description') }}" name="description">
    
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
      <label for="description">Номер сделки в amoCRM</label>
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