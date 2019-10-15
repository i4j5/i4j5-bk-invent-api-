@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Запросы к API</h1>
    <hr>
    <table class="table table-striped table-bordered table-sm">
        <thead>
            <tr>
                <th scope="col">Дата</th>
                <th scope="col">Метод</th>
                <th scope="col">Адрес URL</th>
                <th scope="col">Код ответа</th>
                <th scope="col">Тело ответа</th>
                <th scope="col">Тело запроса</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($logs as $log)
            <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ $log->method }}</td>
                <td>{{ $log->url }}</td>
                <td>{{ $log->code }}</td>
                <td>{{ $log->response }}</td>
                <td>{{ $log->request }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $logs->links('pagination/bootstrap-4') }}
</div>
@endsection