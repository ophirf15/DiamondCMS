@extends('public.legal.layout', [
    'title' => 'Privacy Policy',
    'description' => 'How '.$legal['operator_name'].' collects, uses, and protects personal information.',
    'page' => 'privacy',
    'legal' => $legal,
])

@section('legal')
@php
    $name = $legal['operator_name'];
    $email = $legal['contact_email'];
    $address = $legal['contact_address'];
    $site = $legal['website_url'];
    $law = $legal['jurisdiction'];
@endphp

<section>
    <h2>1. Who we are</h2>
    <p>
        This Privacy Policy explains how <strong>{{ $name }}</strong>
        (“we”, “us”, or “our”) collects, uses, and shares information when you visit
        <a href="{{ $site }}">{{ $site }}</a>.
    </p>
    @if ($email !== '' || $address !== '')
        <div class="dc-legal-callout">
            <h3>Contact for privacy questions</h3>
            @if ($email !== '')
                <p>Email: <a href="mailto:{{ $email }}">{{ $email }}</a></p>
            @else
                <p class="dc-legal-missing">Add a contact email in Settings → Legal.</p>
            @endif
            @if ($address !== '')
                <p>Mailing address: {{ $address }}</p>
            @endif
        </div>
    @else
        <div class="dc-legal-callout dc-legal-callout--todo">
            <h3>Complete your legal contact details</h3>
            <p>Add your contact email (and optional mailing address) under <strong>Admin → Settings → Legal</strong> so visitors know how to reach you.</p>
        </div>
    @endif
</section>

<section>
    <h2>2. Information we collect</h2>
    <p>Depending on how you use the site, we may collect:</p>
    <ul>
        <li><strong>Information you provide</strong> — such as your name, email address, and message content when you submit a contact or other form.</li>
        <li><strong>Technical data</strong> — such as IP address, browser type, device type, referring pages, and general usage logs needed to operate and secure the site.</li>
        <li><strong>Cookies and similar technologies</strong> — described in our <a href="{{ url('/cookies') }}">Cookie Policy</a>.</li>
    </ul>
</section>

<section>
    <h2>3. How we use information</h2>
    <p>We use personal information to:</p>
    <ul>
        <li>Respond to inquiries and provide requested services</li>
        <li>Operate, maintain, and improve the website</li>
        <li>Monitor security, prevent abuse, and diagnose technical issues</li>
        <li>Comply with legal obligations</li>
    </ul>
    <p>We do not sell your personal information.</p>
</section>

<section>
    <h2>4. Sharing of information</h2>
    <p>
        We may share information with service providers who help us host the site, send email, or analyze traffic,
        only as needed to provide those services. We may also disclose information if required by law or to protect
        our rights, users, or the public.
    </p>
</section>

<section>
    <h2>5. Data retention</h2>
    <p>
        We keep personal information only as long as needed for the purposes described above, including to meet legal,
        accounting, or reporting requirements. Form submissions and related records may be retained while they remain
        relevant to ongoing communication or site administration.
    </p>
</section>

<section>
    <h2>6. Your choices and rights</h2>
    <p>
        Depending on where you live, you may have rights to access, correct, delete, or restrict use of your personal
        information, or to object to certain processing. To make a request, contact us using the details above.
        You may also unsubscribe from marketing emails if we ever send them (this site is primarily for personal/portfolio use).
    </p>
</section>

<section>
    <h2>7. Security</h2>
    <p>
        We take reasonable administrative and technical measures to protect information. No method of transmission
        or storage is fully secure, so we cannot guarantee absolute security.
    </p>
</section>

<section>
    <h2>8. Children’s privacy</h2>
    <p>
        This website is not directed to children under 13, and we do not knowingly collect personal information from children.
    </p>
</section>

<section>
    <h2>9. Changes</h2>
    <p>
        We may update this Privacy Policy from time to time. The effective date at the top of this page will change when we do.
        Continued use of the site after an update means you accept the revised policy.
    </p>
</section>

<section>
    <h2>10. Governing law</h2>
    @if ($law !== '')
        <p>This Privacy Policy is governed by the laws of <strong>{{ $law }}</strong>, without regard to conflict-of-law principles.</p>
    @else
        <div class="dc-legal-callout dc-legal-callout--todo">
            <h3>Add your jurisdiction</h3>
            <p>Enter the state/country whose laws apply under <strong>Admin → Settings → Legal</strong> (for example, “State of California, United States”).</p>
        </div>
    @endif
</section>
@endsection
