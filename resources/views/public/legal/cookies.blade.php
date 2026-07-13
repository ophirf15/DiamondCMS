@extends('public.legal.layout', [
    'title' => 'Cookie Policy',
    'description' => 'How '.$legal['operator_name'].' uses cookies and similar technologies.',
    'page' => 'cookies',
    'legal' => $legal,
])

@section('legal')
@php
    $name = $legal['operator_name'];
    $email = $legal['contact_email'];
    $site = $legal['website_url'];
@endphp

<section>
    <h2>1. What this policy covers</h2>
    <p>
        This Cookie Policy explains how <strong>{{ $name }}</strong> uses cookies and similar technologies on
        <a href="{{ $site }}">{{ $site }}</a>. It should be read together with our
        <a href="{{ url('/privacy') }}">Privacy Policy</a>.
    </p>
</section>

<section>
    <h2>2. What are cookies?</h2>
    <p>
        Cookies are small text files stored on your device when you visit a website. They help the site remember
        preferences, keep sessions secure, and understand how the site is used. Similar technologies include local storage
        and pixels.
    </p>
</section>

<section>
    <h2>3. Cookies we use</h2>
    <p>We may use the following categories:</p>
    <ul>
        <li>
            <strong>Strictly necessary</strong> — required for core functions such as security, load balancing,
            remembering theme preference, or keeping an admin session signed in.
        </li>
        <li>
            <strong>Functional</strong> — remember choices you make (for example light/dark mode) to improve your experience.
        </li>
        <li>
            <strong>Analytics</strong> — help us understand aggregate traffic and popular pages. Where used, we aim to use
            privacy-respecting settings and avoid unnecessary personal identifiers.
        </li>
    </ul>
    <p>
        This site may set first-party cookies for session and preference purposes. Third-party cookies are only used if
        a feature you interact with requires them (for example embedded media).
    </p>
</section>

<section>
    <h2>4. Managing cookies</h2>
    <p>
        Most browsers let you block or delete cookies through their settings. If you block strictly necessary cookies,
        parts of the site (including the admin area) may not work correctly. You can also clear site data for this domain
        at any time from your browser’s privacy controls.
    </p>
</section>

<section>
    <h2>5. Updates</h2>
    <p>
        We may update this Cookie Policy when our practices change. The effective date at the top of this page will be revised accordingly.
    </p>
</section>

<section>
    <h2>6. Contact</h2>
    @if ($email !== '')
        <p>Questions about cookies? Email <a href="mailto:{{ $email }}">{{ $email }}</a>.</p>
    @else
        <div class="dc-legal-callout dc-legal-callout--todo">
            <h3>Add a contact email</h3>
            <p>Set your contact email under <strong>Admin → Settings → Legal</strong>.</p>
        </div>
    @endif
</section>
@endsection
