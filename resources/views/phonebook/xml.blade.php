<?php header('Content-Type: text/xml'); ?>

<CiscoIPPhoneDirectory>
@foreach ($contacts as $contact)
    <DirectoryEntry>
        <Name>{{ $contact->name }}</Name>
        @foreach ($contact->values as $field)
            @if($field->type == 'PHONE')
                <Telephone>{{ $field->value }}</Telephone>
            @endif
        @endforeach

    </DirectoryEntry>
@endforeach
</CiscoIPPhoneDirectory>