<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (current_user()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'student/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';

    $st = $pdo->prepare('SELECT id,full_name,email,password,role FROM users WHERE email=? LIMIT 1');
    $st->execute([$email]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;

        redirect($u['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
    }

    $error = 'Incorrect email or password.';
}

page_header('Log in');
?>

<div class="login-page">

    <!-- Left Side -->
    <div class="login-welcome">
        <div class="login-welcome-content">

            <div class="login-brand">
                <div class="login-brand-icon">L</div>
                <span>LearnHub</span>
            </div>

            <div class="welcome-text">
                <span class="welcome-label">WELCOME BACK</span>

                <h1>Your learning journey continues here.</h1>

                <p>
                    Access your courses, track your progress,
                    and keep building the skills that move you forward.
                </p>

                <div class="welcome-features">
                    <div class="welcome-feature">
                        <span>✓</span>
                        Learn at your own pace
                    </div>

                    <div class="welcome-feature">
                        <span>✓</span>
                        Track your learning progress
                    </div>

                    <div class="welcome-feature">
                        <span>✓</span>
                        Access your courses anytime
                    </div>
                </div>
            </div>

            <div class="welcome-decoration decoration-one"></div>
            <div class="welcome-decoration decoration-two"></div>

            <div class="welcome-bottom">
                Learn something new. Every single day.
            </div>

        </div>
    </div>


    <!-- Right Side -->
    <div class="login-form-side">

        <div class="login-form-container">

            <div class="login-mobile-brand">
                <div class="login-brand-icon">L</div>
                <span>LearnHub</span>
            </div>

            <div class="login-heading">
                <span class="form-eyebrow">YOUR ACCOUNT</span>
                <h2>Welcome back!</h2>
                <p>Enter your details to access your account.</p>
            </div>

            <?php if ($error): ?>
                <div class="login-error">
                    <span>!</span>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form class="login-form" method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="login-field">
                    <label for="email">Email address</label>

                    <div class="login-input-wrapper">
                        <span class="input-icon">✉</span>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="name@example.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>

                    <div class="login-input-wrapper">
                        <span class="input-icon">♙</span>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            type="button"
                            class="toggle-password"
                            onclick="togglePassword()"
                            aria-label="Show password"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-submit">
                    Log in
                    <span>→</span>
                </button>

            </form>

            <div class="login-divider">
                <span>NEW TO LEARNHUB?</span>
            </div>

            <p class="login-register">
                Don't have an account?
                <a href="<?= e(BASE_URL) ?>/auth/register.php">
                    Create an account
                </a>
            </p>

            <div class="login-footer">
                © <?= date('Y') ?> 
                This platform was created by Ms.BENTOUATI.
            </div>

        </div>
    </div>

</div>

<script>
function togglePassword() {
    const password = document.getElementById('password');
    const button = document.querySelector('.toggle-password');

    if (password.type === 'password') {
        password.type = 'text';
        button.textContent = 'Hide';
    } else {
        password.type = 'password';
        button.textContent = 'Show';
    }
}
</script>

<?php page_footer(); ?>