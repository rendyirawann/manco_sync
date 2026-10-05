@php
    $socialIcons = ['linkedin' => ['fab fa-linkedin-in', 'LinkedIn'], 'github' => ['fab fa-github', 'GitHub'], 'instagram' => ['fab fa-instagram', 'Instagram'], 'tiktok' => ['fab fa-tiktok', 'TikTok']];
@endphp
@foreach($socialIcons as $key => [$icon, $label])
    @if($url = config("services.manco.social.$key"))
        <a href="{{ $url }}" class="{{ $class ?? '' }}" target="_blank" rel="noopener me" aria-label="{{ $label }}" title="{{ $label }}"><i class="{{ $icon }}"></i></a>
    @endif
@endforeach
