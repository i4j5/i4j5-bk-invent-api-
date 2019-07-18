@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Телефонная книга</div>

                <div class="card-body">
                    <div class="row">
                        @foreach ($data as $contact)
                            <div class="col-lg-4 col-mb-12 col-sm-6">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <b class="card-title">{{ $contact['name'] }}</b>
                                        <br>
                                        @if (isset($contact['custom_fields'])) 
                                            @foreach ($contact['custom_fields'] as $field)
                                                @if (isset($field['code']))   
                                                    @if($field['code'] == 'PHONE')
                                                        @foreach ($field['values'] as $value)
                                                            <a href="tel:{{ $value['value'] }}">{{ $value['value'] }}</a>
                                                            <br>
                                                        @endforeach 
                                                    @elseif ($field['code'] == 'EMAIL')
                                                        @foreach ($field['values'] as $value)
                                                            <p>{{ $value['value'] }}</p>
                                                        @endforeach  
                                                    @endif
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
