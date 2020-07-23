@extends('layouts.app')

@section('content')
<div class="container-fluid">
        @if (Session::has('message'))
            <div class="alert alert-info">{{ Session::get('message') }}</div>
        @endif

        <form method="GET" class="form-inline my-2 my-lg-0" action="{{ URL::to('playments') }}">
            <input class="form-control ml-sm-2 mr-sm-2" name="search" type="search" value="{{ $search }}" placeholder="Поиск" aria-label="Поиск">
            <button type="submit" class="btn btn-primary my-2 my-sm-0">Поиск</button>
        </form>

        <table class="table table-striped table-bordered table-sm">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">ФИО</th>
                    <th scope="col">Телефон</th>
                    <th scope="col">E-mail</th>
                    <th scope="col">Описание заказа</th>
                    <th scope="col">Sberbank</th>
                    <th scope="col">amoCRM</th>
                    <th scope="col">Cумма</th>
                    <th scope="col">Оплатить до</th>
                    <th scope="col">
                        <a href="{{ URL::to('playments/create') }}">
                            <button type="button" class="btn btn-primary">Добавить</button>
                        </a>
                    </th>
                    </th>
                </tr>
            </thead>
            <tbody>
            @foreach ($playments as $playment)
                <tr>
                    <th scope="row">{{ $playment->id }}</th>
                    <td>{{ $playment->fio }}</td>
                    <td>{{ $playment->phone }}</td>
                    <td>{{ $playment->email }}</td>
                    <td>{{ $playment->description }}</td>
                    <td>{{ $playment->order_id }}</td>
                    <td>{{ $playment->deal_id }}</td>
                    <td>{{ $playment->amount / 100 }}</td>
                    <td>{{ $playment->date }}</td>
                    <td>
                      <div class="btn-group" role="group" aria-label="Basic example">
                          <a href="{{ URL::to('playments/' . $playment->id) }}">
                            <button type="button" class="btn btn-warning">Open</button>
                          </a>
                      </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $playments->appends(['search' => $search,])->links() }}
</div>
@endsection