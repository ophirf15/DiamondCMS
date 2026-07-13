@extends('public.legal.layout', [
    'title' => 'Terms of Use',
    'description' => 'Terms governing use of '.$legal['website_url'].'.',
    'page' => 'terms',
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
    <h2>1. Agreement to these terms</h2>
    <p>
        By accessing <a href="{{ $site }}">{{ $site }}</a> (the “Site”), you agree to these Terms of Use.
        If you do not agree, please do not use the Site.
    </p>
    <div class="dc-legal-callout">
        <h3>Site operator</h3>
        <p><strong>{{ $name }}</strong></p>
        @if ($email !== '')
            <p>Email: <a href="mailto:{{ $email }}">{{ $email }}</a></p>
        @else
            <p class="dc-legal-missing">Add a contact email in Settings → Legal.</p>
        @endif
        @if ($address !== '')
            <p>Address: {{ $address }}</p>
        @endif
    </div>
</section>

<section>
    <h2>2. The Site</h2>
    <p>
        The Site is a personal / portfolio website operated by {{ $name }}. Content is provided for general informational
        purposes unless otherwise stated. Features may change, and we may update, suspend, or discontinue any part of the
        Site at any time.
    </p>
</section>

<section>
    <h2>3. Acceptable use</h2>
    <p>You agree not to:</p>
    <ul>
        <li>Use the Site in any way that violates applicable law</li>
        <li>Attempt to gain unauthorized access to accounts, systems, or data</li>
        <li>Interfere with or disrupt the Site’s security or performance</li>
        <li>Scrape, harvest, or misuse content or contact forms for spam</li>
        <li>Upload or transmit malware or harmful code</li>
    </ul>
</section>

<section>
    <h2>4. Intellectual property</h2>
    <p>
        Unless otherwise noted, the Site’s text, design, logos, images, and other materials are owned by {{ $name }}
        or used with permission. You may view and share links to public pages for personal, non-commercial purposes.
        You may not copy, modify, distribute, or create derivative works from Site content without prior written permission,
        except as allowed by law (such as fair use).
    </p>
</section>

<section>
    <h2>5. Third-party links and services</h2>
    <p>
        The Site may link to third-party websites or embed third-party content. We are not responsible for their content,
        policies, or practices. Your use of third-party services is governed by their terms.
    </p>
</section>

<section>
    <h2>6. Disclaimers</h2>
    <p>
        The Site is provided “as is” and “as available” without warranties of any kind, whether express or implied,
        including warranties of accuracy, merchantability, fitness for a particular purpose, or non-infringement.
        We do not warrant that the Site will be uninterrupted, secure, or error-free.
    </p>
</section>

<section>
    <h2>7. Limitation of liability</h2>
    <p>
        To the fullest extent permitted by law, {{ $name }} will not be liable for any indirect, incidental, special,
        consequential, or punitive damages, or any loss of profits, data, or goodwill, arising from your use of the Site.
        Our total liability for any claim relating to the Site will not exceed one hundred U.S. dollars (US $100)
        or the minimum amount allowed by applicable law.
    </p>
</section>

<section>
    <h2>8. Indemnity</h2>
    <p>
        You agree to defend and hold harmless {{ $name }} from claims, damages, and expenses (including reasonable
        attorneys’ fees) arising from your misuse of the Site or violation of these Terms.
    </p>
</section>

<section>
    <h2>9. Privacy</h2>
    <p>
        Our collection and use of personal information is described in the
        <a href="{{ url('/privacy') }}">Privacy Policy</a> and <a href="{{ url('/cookies') }}">Cookie Policy</a>.
    </p>
</section>

<section>
    <h2>10. Changes to these terms</h2>
    <p>
        We may revise these Terms of Use. The effective date above will be updated when changes are posted.
        Continued use of the Site after changes take effect constitutes acceptance of the updated Terms.
    </p>
</section>

<section>
    <h2>11. Governing law</h2>
    @if ($law !== '')
        <p>
            These Terms are governed by the laws of <strong>{{ $law }}</strong>, without regard to conflict-of-law rules.
            Courts located in that jurisdiction will have exclusive venue for disputes, except where prohibited by law.
        </p>
    @else
        <div class="dc-legal-callout dc-legal-callout--todo">
            <h3>Add your jurisdiction</h3>
            <p>Enter governing law under <strong>Admin → Settings → Legal</strong> (for example, “State of California, United States”).</p>
        </div>
    @endif
</section>

<section>
    <h2>12. Contact</h2>
    @if ($email !== '')
        <p>Questions about these Terms? Contact <a href="mailto:{{ $email }}">{{ $email }}</a>.</p>
    @else
        <div class="dc-legal-callout dc-legal-callout--todo">
            <h3>Add a contact email</h3>
            <p>Set your contact email under <strong>Admin → Settings → Legal</strong>.</p>
        </div>
    @endif
</section>
@endsection
