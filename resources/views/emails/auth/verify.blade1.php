@component('mail::message')

Подтвердите эмайл пройдя по ссылке или нажав на кнопку.

@component('mail::button', ['url' => $url])
Подтвердить
@endcomponent

{{ $url }}

{{-- Thanks,<br>
{{ config('app.name') }} --}}

@endcomponent
