@extends('layouts.app')

@section('content')
<div class="container">
    @if ($sber->actionCode == '-100')
        <div class="alert alert-warning">Не было попыток оплаты</div>
    @elseif ($sber->actionCode == '0')
        <div class="alert alert-success">Платёж успешно прошёл</div>
    @endif
    @if ($sber->actionCodeDescription)
        <div class="alert alert-secondary">{{ $sber->actionCodeDescription }}</div>
    @endif
    <p>Номер заказа: {{ $playment->id }}</p>
    <p>Cумма: {{ $playment->amount / 100 }} ₽</p>
    <p>Оплатить до: {{ $playment->date }}</p>
    <p>Описание заказа: {{ $playment->description }}</p>
    <p>ФИО: {{ $playment->fio }}</p>
    <p>Телефон: {{ $playment->phone }}</p>
    <p>E-mail: {{ $playment->email }}</p>
    <p>Номер сделки в amoCRM: {{ $playment->deal_id }}</p>
    <p>Номер заказа в sberbank: {{ $playment->order_id }}</p>
    <input type="text" class="form-control" value="{{ $playment->payment_url }}">
</div>
@endsection