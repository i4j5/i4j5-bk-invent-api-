@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-6 col-mb-6 col-sm-8">
                            <form method="GET" class="form-inline my-2 my-lg-0" action="{{ route('phonebook.search') }}">
                                {{-- {{ csrf_field() }}  --}}
                            <div class="form-group">
                                <select name="group" class="form-control">
                                    <option value="" @if ($group == '') selected @endif>Все</option>
                                    @foreach($groups as $el)
                                        <option value="{{ $el }}" @if ($el == $group) selected @endif>{{ $el }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <input class="form-control ml-sm-2 mr-sm-2" name="search" type="search" value="{{ $search }}" placeholder="Поиск" aria-label="Поиск">
                                <button type="submit" class="btn btn-primary my-2 my-sm-0">Поиск</button>
                            </form>
                        </div>
                        <div class="col-lg-6 col-mb-6 col-mb-4 text-right">
                            <div class="text-right">
                                <a class="btn btn-success" href="{{ route('phonebook.create') }}">Добавить</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        @foreach ($contacts as $contact)
                            <div class="col-lg-4 col-mb-12 col-sm-6">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <b class="card-title">
                                            @if($contact->type == 'company')
                                                <small class="float-right badge badge-pill badge-warning">Компания</small>
                                            @endif
                                            @if ($contact->group != "")
                                                <small class="float-right badge badge-pill badge-secondary">{{ $contact->group }}</small>
                                            @endif
                                            {{ $contact->name }}
                                        </b>
                                        
                                    </div>
                                    <div class="card-body">
                                        <p>{{ $contact->description }}</p>
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
                                    <div class="text-right card-footer text-muted">
                                        @if($contact->type == 'company')
                                            <a class="btn btn-info btn-sm" href="https://bkinvent.amocrm.ru/companies/detail/{{$contact->amo_id}}">Открыть в amoCRM</a>
                                        @else
                                            <a class="btn btn-info btn-sm" href="https://bkinvent.amocrm.ru/contacts/detail/{{$contact->amo_id}}">Открыть в amoCRM</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    {{ $contacts->appends(['search' => $search, 'group' => $group])->links('pagination/bootstrap-4') }}
                    <div class="text-right">
                        <a class="btn btn-outline-info" href="{{ route('phonebook.update') }}">Обновить базу</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
