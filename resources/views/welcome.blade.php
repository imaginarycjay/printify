<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <script>
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && 'IntersectionObserver' in window && 'animate' in document.documentElement) {
                document.documentElement.classList.add('landing-motion-enabled');
                // Keep headings readable if the application bundle fails to load.
                window.landingMotionFallback = window.setTimeout(() => {
                    document.documentElement.classList.remove('landing-motion-enabled');
                }, 4000);
            }
        </script>
        @include('partials.head', ['title' => 'Printing services for your next project'])
        <meta name="description" content="Find a print shop, order document printing or thesis binding, and follow your pages from payment to pickup. Printify connects customers, shop owners, and production teams.">
    </head>
    <body class="printify-landing font-sans antialiased selection:bg-amber-500 selection:text-stone-950">
        <a href="#main-content" class="landing-skip">Skip to content</a>
        <x-platform-header>
            <a href="#pricing" class="inline-flex min-h-11 items-center rounded-xl px-3 text-sm font-semibold focus-visible:outline-2 focus-visible:outline-offset-4">Shop plans</a>
        </x-platform-header>
        <main id="main-content" tabindex="-1" class="landing-page">
            <section id="start" aria-labelledby="hero-title" class="landing-section landing-hero">
                <div class="landing-container landing-hero-grid" data-landing-reveal>
                    <div class="landing-hero-copy">
                        <h1 id="hero-title" data-landing-heading><span class="landing-heading-word">Good</span> <span class="landing-heading-word">work</span> <span class="landing-heading-word">deserves</span><br><span class="landing-heading-word">a</span> <span><span class="landing-heading-word">great</span> <span class="landing-heading-word">finish.</span></span>
                        </h1>
                        <p class="landing-lead">Your printing projects, without the back-and-forth. Choose a shop, make it yours, and follow every stage to pickup.</p>
                        <div class="landing-actions">
                            <a href="{{ route('shops.index') }}" class="landing-button landing-primary">Find a print shop <flux:icon name="arrow-right" />
                            </a>
                            @auth
                                <a href="{{ route('dashboard') }}" class="landing-text-link">Open my dashboard <flux:icon name="arrow-up-right" />
                                </a>
                            @else
                                <a href="#for-shop-owners" class="landing-text-link">I run a print shop <flux:icon name="arrow-down" />
                                </a>
                            @endauth
                        </div>
                        <p class="landing-note">Browse before signing in. Choose your shop before you commit.</p>
                        <div class="landing-hero-details" aria-label="Available services">
                            <span>
                                <flux:icon name="document-text" /> Document printing</span>
                            <span>
                                <flux:icon name="book-open" /> Thesis binding</span>
                            <span>
                                <flux:icon name="building-storefront" /> And more</span>
                        </div>
                    </div>
                    <figure class="landing-order-preview" aria-labelledby="example-order-title">
                        <figcaption class="landing-ticket-top">
                            <span id="example-order-title">Example order</span>

                        </figcaption>
                        <div class="landing-ticket-body">
                            <div class="landing-ticket-shop">
                                <span class="landing-ticket-icon">
                                    <flux:icon name="building-storefront" />
                                </span>
                                <div>
                                    <strong>Campus Print Studio</strong>
                                    <p>Printing & book binding</p>
                                </div>
                            </div>
                            <div class="landing-ticket-heading">
                                <div>
                                    <p class="landing-note">ORD-2026-0148</p>
                                    <h2>Hardbound manuscript</h2>
                                </div>
                                <span class="landing-status">
                                    <span aria-hidden="true"></span>In production</span>
                            </div>
                            <p class="landing-ticket-file">
                                <flux:icon name="document-text" /> final-manuscript.pdf <span>120 pages</span>
                            </p>
                            <dl class="landing-ticket-specs">
                                <div>
                                    <dt>Paper</dt>
                                    <dd>A4 · Black & white</dd>
                                </div>
                                <div>
                                    <dt>Copies & sides</dt>
                                    <dd>2 copies · Single-sided</dd>
                                </div>
                                <div>
                                    <dt>Cover & finish</dt>
                                    <dd>Royal blue · Gold foil</dd>
                                </div>
                            </dl>
                            <dl class="landing-ticket-prices">
                                <div>
                                    <dt>Printing <span>240 pages × ₱1.50</span>
                                    </dt>
                                    <dd>₱360.00</dd>
                                </div>
                                <div>
                                    <dt>Binding <span>2 copies × ₱250.00</span>
                                    </dt>
                                    <dd>₱500.00</dd>
                                </div>
                                <div class="landing-ticket-total">
                                    <dt>Order total</dt>
                                    <dd>₱860.00</dd>
                                </div>
                            </dl>
                            <p class="landing-payment">
                                <flux:icon name="check-circle" /> Payment verified <span>GCash</span>
                            </p>
                            <ol class="landing-ticket-progress" aria-label="Example production progress">
                                <li class="is-complete">
                                    <flux:icon name="check" />
                                    <span>Printed</span>
                                </li>
                                <li aria-current="step">
                                    <span class="landing-progress-dot"></span>
                                    <span>Binding</span>
                                </li>
                                <li>
                                    <span class="landing-progress-dot"></span>
                                    <span>Quality check</span>
                                </li>
                                <li>
                                    <span class="landing-progress-dot"></span>
                                    <span>Pickup</span>
                                </li>
                            </ol>
                            <p class="landing-ticket-next">Now: binding and gold-foil lettering. Next: quality check.</p>
                        </div>
                        <p class="landing-ticket-disclaimer">Illustrative order, shop, and prices. Your total depends on your chosen shop and specifications.</p>
                    </figure>
                </div>
                <a href="#services" class="landing-section-next">See what you can make <flux:icon name="arrow-down" />
                </a>
            </section>
            <section id="services" aria-labelledby="services-title" class="landing-section landing-services">
                <div class="landing-container" data-landing-reveal>
                    <div class="landing-section-heading">
                        <h2 id="services-title" data-landing-heading><span class="landing-heading-word">Different</span> <span class="landing-heading-word">projects.</span><br><span class="landing-heading-word">The</span> <span class="landing-heading-word">right</span> <span class="landing-heading-word">details</span> <span class="landing-heading-word">for</span> <span class="landing-heading-word">each.</span></h2>
                        <p>From everyday documents to bound books, start with your project. Service-specific forms keep the options relevant to what you are ordering.</p>
                    </div>
                    <div class="landing-service-grid">
                        <article class="landing-service">
                            <flux:icon name="document-text" class="landing-service-icon" />
                            <h3>Print it your way.</h3>
                            <p>Lecture notes, project proposals, forms, and reports. Choose the paper, color, copies, and finish that fit the job.</p>
                            <ul class="landing-feature-list">
                                <li>
                                    <flux:icon name="check" /> Color or black & white</li>
                                <li>
                                    <flux:icon name="check" /> Single or double-sided pages</li>
                                <li>
                                    <flux:icon name="check" /> Finishing options set by your shop</li>
                            </ul>
                            <a href="{{ route('shops.index', ['service' => 'document_printing']) }}" class="landing-text-link">Find document printing shops <flux:icon name="arrow-right" />
                            </a>
                        </article>
                        <article class="landing-service">
                            <flux:icon name="book-open" class="landing-service-icon" />
                            <h3>Bring your pages together.</h3>
                            <p>Bind manuscripts, reports, and thesis copies with the cover, color, and finish offered by your selected shop.</p>
                            <ul class="landing-feature-list">
                                <li>
                                    <flux:icon name="check" /> Hardbound and softbound options</li>
                                <li>
                                    <flux:icon name="check" /> Printing with binding, or cover-only</li>
                                <li>
                                    <flux:icon name="check" /> Physical manuscript intake for cover-only jobs</li>
                            </ul>
                            <a href="{{ route('shops.index', ['service' => 'thesis_binding']) }}" class="landing-text-link">Find thesis binding shops <flux:icon name="arrow-right" />
                            </a>
                        </article>
                    </div>
                    <div class="landing-service-more">
                        <p><strong>More room for what your shop makes.</strong> Tarpaulin, PVC IDs, T-shirts, and more.</p>
                        <p class="landing-note">The service catalog grows with your shop. Additional services depend on shop availability; online ordering currently supports document printing and thesis binding.</p>
                    </div>
                </div>
            </section>
            <section id="how-it-works" aria-labelledby="ordering-title" class="landing-section landing-process">
                <div class="landing-container" data-landing-reveal>
                    <div class="landing-section-heading">
                        <h2 id="ordering-title" data-landing-heading><span class="landing-heading-word">Less</span> <span class="landing-heading-word">guessing.</span><br><span class="landing-heading-word">More</span> <span class="landing-heading-word">getting</span> <span class="landing-heading-word">it</span> <span class="landing-heading-word">done.</span></h2>
                        <p>Your order stays connected to the people making it. Here is what happens between choosing a shop and collecting your pages.</p>
                    </div>
                    <ol aria-label="Ordering steps" class="landing-steps">
                        <li>
                            <span class="landing-step-number" aria-hidden="true">01</span>
                            <h3>Find your shop.</h3>
                            <p>Browse services and current workload. Pick the shop you want to work with, then see its available options.</p>
                        </li>
                        <li>
                            <span class="landing-step-number" aria-hidden="true">02</span>
                            <h3>Make it yours.</h3>
                            <p>Upload the required files, set your specifications, and review the total. Follow the shop's payment instructions to submit your order.</p>
                        </li>
                        <li>
                            <span class="landing-step-number" aria-hidden="true">03</span>
                            <h3>Follow it to pickup.</h3>
                            <p>Check payment verification and production progress separately in your dashboard. Collect your order when it is ready for pickup.</p>
                        </li>
                    </ol>
                    <div class="landing-process-note">
                        <flux:icon name="information-circle" />
                        <p>Workload shows active jobs, not a guaranteed completion time. For cover-only binding, bring your printed manuscript to the shop.</p>
                    </div>
                    <a href="{{ route('shops.index') }}" class="landing-button landing-primary">Find my print shop <flux:icon name="arrow-right" />
                    </a>
                </div>
            </section>
            <section id="for-shop-owners" aria-labelledby="owners-title" class="landing-section landing-owners">
                <div class="landing-container landing-owner-grid" data-landing-reveal>
                    <div>
                        <h2 id="owners-title" data-landing-heading><span class="landing-heading-word">Your</span> <span class="landing-heading-word">counter.</span><br><span class="landing-heading-word">Your</span> <span class="landing-heading-word">team.</span><br>
                            <span><span class="landing-heading-word">One</span> <span class="landing-heading-word">connected</span> <span class="landing-heading-word">shop.</span></span>
                        </h2>
                        <p class="landing-lead">Spend less time piecing together job details. Keep services, incoming orders, production, and materials in one place.</p>
                        <p class="landing-note">Complete setup and submit your shop for approval. Your listing becomes public after setup is complete and approval is granted.</p>
                        <a href="#pricing" class="landing-button landing-primary">Explore shop plans <flux:icon name="arrow-down" />
                        </a>
                    </div>
                    <div class="landing-owner-tools">
                        <article>
                            <flux:icon name="adjustments-horizontal" />
                            <div>
                                <h3>Set your own services.</h3>
                                <p>Build your service lineup with relevant options, prices, and capacity. The catalog includes tarpaulin, PVC IDs, T-shirts, mugs, stickers, and trophies; availability depends on the shop.</p>
                            </div>
                        </article>
                        <article>
                            <flux:icon name="queue-list" />
                            <div>
                                <h3>Give every job a clear next step.</h3>
                                <p>Invite staff to verify payments, handle files, update production stages, and record quality checks.</p>
                            </div>
                        </article>
                        <article>
                            <flux:icon name="archive-box" />
                            <div>
                                <h3>Keep materials in the picture.</h3>
                                <p>Connect configured material recipes to orders, record spoilage, and review sales and inventory activity.</p>
                            </div>
                        </article>
                    </div>
                </div>
            </section>
            <section id="reviews" aria-labelledby="reviews-title" class="landing-section landing-reviews">
                <div class="landing-container" data-landing-reveal>
                    <div class="landing-section-heading">
                        <h2 id="reviews-title" data-landing-heading><span class="landing-heading-word">Different</span> <span class="landing-heading-word">roles.</span><br><span class="landing-heading-word">A</span> <span class="landing-heading-word">better</span> <span class="landing-heading-word">handoff.</span></h2>
                        <p>These sample reviews illustrate the customer, shop owner, and staff experience. They are demo content, not collected testimonials.</p>
                    </div>
                    <div class="landing-review-grid">
                        @foreach ([['name' => 'Jamie R.', 'role' => 'Student · Demo persona', 'quote' => 'I can check the order stage before going to the shop. That is the part I would use most.'], ['name' => 'Morgan L.', 'role' => 'Shop owner · Demo persona', 'quote' => 'The files, specifications, and payment status are together. It gives our team a clearer handoff.'], ['name' => 'Sam D.', 'role' => 'Production staff · Demo persona', 'quote' => 'A job ticket shows what needs printing and what comes next, without having to piece it together.']] as $review)
                            <figure class="landing-review">
                                <div class="landing-review-stars" role="img" aria-label="Sample rating: 5 out of 5">
                                    @for ($star = 0; $star < 5; $star++)
                                        <flux:icon name="star" variant="solid" />
                                    @endfor
                                </div>
                                <blockquote>“{{ $review['quote'] }}”</blockquote>
                                <figcaption>
                                    <strong>{{ $review['name'] }}</strong>
                                    <span>{{ $review['role'] }}</span>
                                </figcaption>
                                <span class="landing-demo-label">Demo review</span>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
            <section id="pricing" aria-labelledby="pricing-title" class="landing-section landing-pricing">
                <div class="landing-container" data-landing-reveal>
                    <div class="landing-section-heading">
                        <h2 id="pricing-title" data-landing-heading><span class="landing-heading-word">Your</span> <span class="landing-heading-word">shop's</span> <span class="landing-heading-word">next</span> <span class="landing-heading-word">step.</span><br><span class="landing-heading-word">Free</span> <span class="landing-heading-word">for</span> <span class="landing-heading-word">the</span> <span class="landing-heading-word">capstone.</span></h2>
                        <p>Explore Printify with your shop. All three access options are ₱0 during this capstone preview. Printing and binding orders are charged separately by each shop.</p>
                    </div>
                    <div class="landing-plan-grid">
                        @foreach ([['name' => 'Monthly', 'original' => '₱499', 'period' => '/ month', 'description' => 'A proposed subscription for shops that prefer a monthly option.'], ['name' => 'Annual', 'original' => '₱3,999', 'period' => '/ year', 'description' => 'A proposed yearly option for shops planning a full year ahead.'], ['name' => 'One-time', 'original' => '₱4,999', 'period' => 'one-time payment', 'description' => 'A proposed single-payment option for access to the current system.']] as $plan)
                            <article class="landing-plan" aria-label="{{ $plan['name'] }} access plan">
                                <div class="landing-plan-heading">
                                    <h3>{{ $plan['name'] }}</h3>
                                    <span class="landing-plan-badge">Capstone preview</span>
                                </div>
                                <p>{{ $plan['description'] }}</p>
                                <p class="landing-plan-original">
                                    <span>Proposed price</span>
                                    <s>{{ $plan['original'] }}</s>
                                </p>
                                <p class="landing-plan-price">
                                    <strong>₱0</strong>
                                    <span>{{ $plan['period'] }}</span>
                                </p>
                                <p class="landing-plan-free">No payment collected during the preview.</p>
                                <ul class="landing-feature-list">
                                    <li>
                                        <flux:icon name="check" /> Shop profile and service configuration</li>
                                    <li>
                                        <flux:icon name="check" /> Customer orders and production queue</li>
                                    <li>
                                        <flux:icon name="check" /> Staff, inventory, and sales tools</li>
                                </ul>
                                @guest
                                    <a href="{{ route('owner.register') }}" class="landing-button landing-primary">Register your print shop <flux:icon name="arrow-right" />
                                    </a>
                                @else
                                    <a href="{{ route('dashboard') }}" class="landing-button landing-primary">Open my dashboard <flux:icon name="arrow-right" />
                                    </a>
                                @endguest
                            </article>
                        @endforeach
                    </div>
                    <p class="landing-pricing-note">Preview pricing only. Crossed-out amounts are illustrative future prices, not previous charges. Future availability and pricing have not been finalized.</p>
                </div>
            </section>
        </main>
        <footer class="landing-footer">
            <div class="landing-container">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Printify') }}. Built for the next print.</p>
                <nav aria-label="Footer navigation">
                    <a href="{{ route('shops.index') }}">Find a shop</a>
                    <a href="#pricing">Shop plans</a>
                    <a href="#start">Back to top <flux:icon name="arrow-up" />
                    </a>
                </nav>
            </div>
        </footer>
        @fluxScripts
    </body>
</html>
