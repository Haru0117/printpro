<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="container animate-fade-in" style="text-align: center; padding: 8rem 5%;">
    <h1
        style="font-size: 4.5rem; line-height: 1.1; margin-bottom: 1.5rem; background: linear-gradient(to right, #fff, var(--text-muted)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        Print Management <br> <span style="color: var(--primary)">Redefined.</span>
    </h1>
    <p style="font-size: 1.25rem; color: var(--text-muted); max-width: 700px; margin: 0 auto 3rem; line-height: 1.8;">
        The all-in-one platform for seamless print ordering, real-time status tracking, and effortless subscription
        management. High precision, zero friction.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="/register.html" class="btn btn-primary" style="padding: 1rem 2.5rem; font-size: 1.1rem;">Start Free
            Trial</a>
        <a href="#features" class="btn btn-outline" style="padding: 1rem 2.5rem; font-size: 1.1rem;">Explore
            Features</a>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="container" style="padding-top: 4rem;">
    <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 4rem;">Powerful Features</h2>

    <div class="grid grid-cols-3">
        <div class="glass-card">
            <div
                style="width: 50px; height: 50px; background: rgba(99, 102, 241, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; color: var(--primary);">
                <i class="fa-solid fa-bolt fa-xl"></i>
            </div>
            <h3>Real-time Tracking</h3>
            <p style="color: var(--text-muted); margin-top: 1rem;">Monitor every step of your print order from pending
                to shipping with instant status updates.</p>
        </div>

        <div class="glass-card">
            <div
                style="width: 50px; height: 50px; background: rgba(236, 72, 153, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; color: var(--secondary);">
                <i class="fa-solid fa-box-archive fa-xl"></i>
            </div>
            <h3>Smart Management</h3>
            <p style="color: var(--text-muted); margin-top: 1rem;">A centralized dashboard for all your print needs,
                subscriptions, and historical order data.</p>
        </div>

        <div class="glass-card">
            <div
                style="width: 50px; height: 50px; background: rgba(20, 184, 166, 0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1.5rem; color: var(--accent);">
                <i class="fa-solid fa-shield-halved fa-xl"></i>
            </div>
            <h3>Secure Backend</h3>
            <p style="color: var(--text-muted); margin-top: 1rem;">Enterprise-grade security for your files, user data,
                and transaction information.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="container" style="margin-top: 4rem; padding-bottom: 8rem;">
    <div class="glass-card"
        style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(236, 72, 153, 0.1)); text-align: center; border: 1px solid rgba(255, 255, 255, 0.1);">
        <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Ready to streamline your prints?</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Join 1,000+ businesses using PrintPro to automate
            their workflows.</p>
        <a href="/register.html" class="btn btn-primary" style="padding: 1rem 3rem;">Create Your Account</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>