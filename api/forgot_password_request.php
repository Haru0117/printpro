<?php
// ─────────────────────────────────────────────────────────────
//  forgot_password_request.php — Handles password reset requests
// ─────────────────────────────────────────────────────────────
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

// Generic response for security
$genericResponse = ['success' => true, 'message' => 'If an account exists with this email, a reset link has been sent.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    // 1. Automatic Cleanup of expired tokens
    $pdo->prepare("DELETE FROM password_reset_tokens WHERE expires_at < NOW()")->execute();

    // 2. Rate Limiting (1 request per 60 seconds)
    $stmt = $pdo->prepare("SELECT created_at FROM password_reset_tokens WHERE email = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$email]);
    $lastRequest = $stmt->fetch();

    if ($lastRequest && (time() - strtotime($lastRequest['created_at'])) < 60) {
        echo json_encode(['success' => false, 'message' => 'Please wait at least 60 seconds before requesting another reset link.']);
        exit;
    }

    // 3. Check if user exists (Silent check)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // 4. Invalidate any previous tokens for this email
        $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?")->execute([$email]);

        // 5. Generate secure 64-char token
        $rawToken = bin2hex(random_bytes(32)); // 64 chars
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // 6. Save hashed token
        $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $tokenHash, $expiresAt]);

        // 7. Prepare Reset Link
        // For local testing, we use the current domain. 
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $domain = $_SERVER['HTTP_HOST'];
        $resetLink = "$protocol://$domain/index.html?token=$rawToken";

        // 8. Send Email (Branded Template)
        $subject = "Reset Your PrintPro Password";
        $message = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #dde3f0; border-radius: 10px;'>
            <h2 style='color: #1a2340;'>PrintPro Password Reset</h2>
            <p>Hello,</p>
            <p>We received a request to reset your password for your PrintPro account. Click the button below to choose a new password:</p>
            <div style='text-align: center; margin: 30px 0;'>
                <a href='$resetLink' style='background-color: #1d8cf8; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;'>Reset Password</a>
            </div>
            <p style='color: #6b7a99; font-size: 14px;'>This link will expire in <strong>30 minutes</strong>. If you did not request this, you can safely ignore this email.</p>
            <hr style='border: 0; border-top: 1px solid #dde3f0; margin: 20px 0;'>
            <p style='font-size: 12px; color: #8a96b0;'>&copy; 2026 PrintPro — Engineers for Efficiency</p>
        </div>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: PrintPro <noreply@printpro.com>" . "\r\n";

        // Attempt to send
        $mailSent = @mail($email, $subject, $message, $headers);

        // Fallback Logging for testing/AwardSpace
        $logFile = __DIR__ . '/../logs/mail.log';
        if (!is_dir(dirname($logFile))) mkdir(dirname($logFile), 0777, true);
        $logEntry = "[" . date('Y-m-d H:i:s') . "] Reset Link for $email: $resetLink (Mail Sent: " . ($mailSent ? 'Yes' : 'No') . ")\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }

    echo json_encode($genericResponse);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again later.']);
}
