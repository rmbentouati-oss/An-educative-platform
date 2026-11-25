<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

if (current_user()) {
    redirect(is_admin() ? 'admin/dashboard.php' : 'student/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 8) {
        $error = 'Enter your name, a valid email, and a password of at least 8 characters.';
    } else {
        try {
            $st = $pdo->prepare("INSERT INTO users(full_name,email,password,role) VALUES(?,?,?,'student')");
            $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);

            flash('success', 'Your student account has been created. Please log in.');
            redirect('auth/login.php');

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'This email may already be registered.';
        }
    }
}

page_header('Student registration');
?>

<div class="register-page">

    <div class="register-side">

        <div class="register-brand">
            <div class="register-brand-icon">L</div>
            <span>LearnHub</span>
        </div>

        <div class="register-side-content">

            <span class="register-eyebrow">START LEARNING TODAY</span>

            <h1>Turn your curiosity into new skills.</h1>

            <p>
                Join LearnHub and discover a better way to learn,
                grow your knowledge, and achieve your goals.
            </p>

            <div class="register-benefits">

                <div class="register-benefit">
                    <span>01</span>
                    <div>
                        <strong>Explore courses</strong>
                        <small>Discover learning opportunities.</small>
                    </div>
                </div>

                <div class="register-benefit">
                    <span>02</span>
                    <div>
                        <strong>Learn your way</strong>
                        <small>Study whenever it suits you.</small>
                    </div>
                </div>

                <div class="register-benefit">
                    <span>03</span>
                    <div>
                        <strong>Grow every day</strong>
                        <small>Build skills for your future.</small>
                    </div>
                </div>

            </div>

        </div>

        <div class="register-side-footer">
            Your next chapter starts here.
        </div>

        <div class="register-circle circle-one"></div>
        <div class="register-circle circle-two"></div>

    </div>


    <div class="register-form-side">

        <div class="register-form-container">

            <div class="register-mobile-brand">
                <div class="register-brand-icon">L</div>
                <span>LearnHub</span>
            </div>

            <div class="register-heading">
                <span class="register-form-label">CREATE YOUR ACCOUNT</span>
                <h2>Join LearnHub</h2>
                <p>Fill in your details to get started.</p>
            </div>

            <?php if ($error): ?>
                <div class="register-error">
                    <span>!</span>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form class="register-form" method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <div class="register-field">
                    <label for="full_name">Full name</label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="150"
                        placeholder="Enter your full name"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="register-field">
                    <label for="email">Email address</label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="name@example.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="register-field">
                    <label for="password">Password</label>

                    <div class="register-password-wrapper">
                        <input
                            type="password"
                            id="register-password"
                            name="password"
                            minlength="8"
                            placeholder="Create a strong password"
                            autocomplete="new-password"
                            required
                        >

                        <button
                            type="button"
                            class="register-toggle"
                            onclick="toggleRegisterPassword()"
                        >
                            Show
                        </button>
                    </div>

                    <small class="register-hint">
                        Minimum 8 characters.
                    </small>
                </div>

                <button type="submit" class="register-submit">
                    Create my account
                    <span>→</span>
                </button>

            </form>

            <div class="register-login">
                Already have an account?
                <a href="<?= e(BASE_URL) ?>/auth/login.php">Log in</a>
            </div>

            <div class="register-footer">
                © <?= date('Y') ?> LearnHub. All rights reserved.
            </div>

        </div>

    </div>

</div>

<script>
function toggleRegisterPassword() {
    const password = document.getElementById('register-password');
    const button = document.querySelector('.register-toggle');

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
