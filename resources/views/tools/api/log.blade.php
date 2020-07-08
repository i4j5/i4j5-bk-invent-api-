@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1>Запросы к API</h1>
    <hr>

    <form method="GET" class="form-inline my-2 my-lg-0" action="{{ route('api.log') }}">
        <input class="form-control ml-sm-2 mr-sm-2" name="url" type="search" value="{{ $_url }}" placeholder="Адрес URL" aria-label="Адрес URL">
        <input class="form-control ml-sm-2 mr-sm-2" name="request" type="search" value="{{ $_request }}" placeholder="Тело запроса" aria-label="Тело запроса">
        <input class="form-control ml-sm-2 mr-sm-2" name="response" type="search" value="{{ $_response }}" placeholder="Тело ответа" aria-label="Тело ответа">
        <button type="submit" class="btn btn-primary my-2 my-sm-0">Поиск</button>
    </form>

    <br>

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
    {{ $logs->appends(['url' => $_url, 'request' => $_request, 'response' => $_response])->links('pagination/bootstrap-4') }}
</div>
@endsection