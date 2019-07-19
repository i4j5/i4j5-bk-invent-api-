@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-6 col-mb-6 col-sm-8">
                            <input class="form-control" type="search" placeholder="Поиск" aria-label="Поиск">
                        </div>
                        <div class="col-lg-6 col-mb-6 col-mb-4 text-right">
                            <a class="btn btn-outline-info" href="{{ route('phonebook.update') }}">Обновить базу</a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        @foreach ($contacts as $contact)
                            <div class="col-lg-4 col-mb-12 col-sm-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <b class="card-title">{{ $contact->name }}</b>
                                        <br>
                                        @foreach ($contact->values as $field)
                                            @if($field->type == 'PHONE')
                                                <a href="tel:{{ $field->value }}">{{ $field->value }}</a>
                                                <br>
                                            @elseif ($field->type == 'EMAIL')
                                                {{ $field->value }}
                                                <br>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{ $contacts->appends(['sort' => 'votes'])->links() }}

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
