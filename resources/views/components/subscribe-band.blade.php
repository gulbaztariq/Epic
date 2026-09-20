<section class="section section-sm">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>{{ setting('subscribe_title', 'Stay informed') }}</h2>
                <p>{{ setting('subscribe_text', 'Get EPIC research, policy briefs, events and opportunities delivered to your inbox.') }}</p>
            </div>
            <form class="subscribe-inline" action="{{ route('subscribe.store') }}" method="post" style="flex:1 1 320px;max-width:520px">
                @csrf
                <input type="hidden" name="source" value="footer-band">
                <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">
                <input type="email" name="email" placeholder="Your email address" required aria-label="Email address">
                <button class="btn btn-green" type="submit">Subscribe</button>
            </form>
        </div>
    </div>
</section>
