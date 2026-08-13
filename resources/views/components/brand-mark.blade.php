{{--
    The Runovia mark.

    ? Inline SVG rather than an image file so it inherits currentColor and needs no
      HTTP request, and so it renders in the PDF view where an external asset would
      not resolve.
--}}
@props(['class' => 'h-8 w-8'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 40 40" fill="none" aria-hidden="true">
    <rect width="40" height="40" rx="10" class="fill-brand-600" />
    <path d="M13 27V13h7.2a4.4 4.4 0 0 1 1.5 8.54L25.5 27h-4.1l-3.2-5.1H16.5V27H13Zm3.5-8.2h3.4a1.6 1.6 0 0 0 0-3.2h-3.4v3.2Z"
          fill="white" />
</svg>
