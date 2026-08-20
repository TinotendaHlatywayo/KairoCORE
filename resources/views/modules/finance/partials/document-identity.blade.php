{{-- School name, motto and contact block used by the document header.
     Expects: $h (header section), $profile. --}}
@if($h['show_school_name'])
    <div class="school-name">{{ $profile['name'] }}</div>
@endif
@if($h['show_motto'])
    <div class="school-motto">"{{ $profile['motto'] }}"</div>
@endif
@if($h['show_contact'])
    <div class="school-contact">
        Address: {{ $profile['address'] }} | Contact: {{ $profile['phone'] }} | Email: {{ $profile['email'] }}<br/>
        Website: {{ $profile['website'] }}
    </div>
@endif