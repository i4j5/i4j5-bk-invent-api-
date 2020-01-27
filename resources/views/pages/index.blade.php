@extends('layouts.app')

@section('content')
<div class="container">
        @if (Session::has('message'))
            <div class="alert alert-info">{{ Session::get('message') }}</div>
        @endif

        <table class="table table-striped table-bordered table-sm">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Заголовок страницы</th>
                    <th scope="col">Путь</th>
                    <th scope="col">
                        <a href="{{ URL::to('pages/create') }}">
                            <button type="button" class="btn btn-primary">Добавить</button>
                        </a>
                        <a href="{{ URL::to('pages/sidebar') }}">
                            <button type="button" class="btn btn-primary">Редактировать боковую панель</button>
                        </a>
                    </th>
                    </th>
                </tr>
            </thead>
            <tbody>
            @foreach ($pages as $page)
                <tr>
                    <th scope="row">{{ $page->id }}</th>
                    <td><a href="{{ URL::to($page->path) }}.html">{{ $page->name }}</a></td>
                    <td>/{{ $page->path }}.html</td>
                    <td>
                      <div class="btn-group" role="group" aria-label="Basic example">
                          <a href="{{ URL::to('pages/' . $page->id . '/edit') }}">
                            <button type="button" class="btn btn-warning">Редактировать</button>
                          </a>&nbsp;
                          <form action="{{url('pages', [$page->id])}}" method="POST">
                            <input type="hidden" name="_method" value="DELETE">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="submit" class="btn btn-danger" value="Удалить"/>
                          </form>
                      </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $pages->appends([])->links() }}
</div>
@endsection