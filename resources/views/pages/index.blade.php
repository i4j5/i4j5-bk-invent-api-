@extends('layouts.app')

@section('content')
        @if (Session::has('message'))
            <div class="alert alert-info">{{ Session::get('message') }}</div>
        @endif


        <table class="table table-striped table-bordered table-sm">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Заголовок страницы</th>
                    <th scope="col">URL</th>
                    <th scope="col">-</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($pages as $page)
                <tr>
                    <th scope="row">{{ $page->id }}</th>
                    <td><a href="{{ URL::to($page->path) }}">{{ $page->name }}</a></td>
                    <td>{{ $page->path }}</td>
                    {{-- <td>{{$page->created_at->toFormattedDateString()}}</td> --}}
                    <td>
                      <div class="btn-group" role="group" aria-label="Basic example">
                          <a href="{{ URL::to('pages/' . $page->id . '/edit') }}">
                            <button type="button" class="btn btn-warning">Edit</button>
                          </a>&nbsp;
                          <form action="{{url('pages', [$page->id])}}" method="POST">
                            <input type="hidden" name="_method" value="DELETE">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="submit" class="btn btn-danger" value="Delete"/>
                          </form>
                      </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $pages->appends([])->links() }}
@endsection