
<?php

session_start();

$loginPage = 'index.html';

function redirectLogin(string $error): void {
    global $loginPage;

    header('Location: ' . $loginPage . '?error=' . urlencode($error));
    exit();
}


if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['captcha']) &&
    $_GET['captcha'] === 'generate'
) {

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    try {

        $num1 = random_int(1, 20);
        $num2 = random_int(1, 20);

        $answer = $num1 + $num2;

        $token = bin2hex(random_bytes(32));

        $_SESSION['math_captcha'] = [
            'answer' => (string)$answer,
            'token_hash' => hash('sha256', $token),
            'created_at' => time()
        ];

        echo json_encode([
            'success' => true,
            'question' => $num1 . ' + ' . $num2 . ' = ?',
            'token' => $token
        ]);

    } catch (Throwable $e) {

        error_log('CAPTCHA generation error: ' . $e->getMessage());

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Unable to generate CAPTCHA.'
        ]);

    }

    exit();
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $loginPage);
    exit();
}

$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');

$captchaAnswer = trim(
    (string)($_POST['captcha_answer'] ?? '')
);

$captchaToken = trim(
    (string)($_POST['captcha_token'] ?? '')
);

if ($username === '' || $password === '') {
    redirectLogin('invalid');
}

$captcha = $_SESSION['math_captcha'] ?? null;

unset($_SESSION['math_captcha']);

if (
    !is_array($captcha) ||
    !isset(
        $captcha['answer'],
        $captcha['token_hash'],
        $captcha['created_at']
    ) ||
    !is_string($captcha['answer']) ||
    !is_string($captcha['token_hash']) ||
    !is_int($captcha['created_at']) ||
    (time() - $captcha['created_at']) > 600 ||
    (time() - $captcha['created_at']) < 0 ||
    $captchaAnswer === '' ||
    !ctype_digit($captchaAnswer) ||
    !preg_match('/^[a-f0-9]{64}$/', $captchaToken) ||
    !hash_equals(
        $captcha['token_hash'],
        hash('sha256', $captchaToken)
    ) ||
    !hash_equals(
        $captcha['answer'],
        $captchaAnswer
    )
) {

    redirectLogin('captcha');

}


require_once __DIR__ . '/db.php';

if (!isset($conn) || !($conn instanceof mysqli)) {

    error_log('Login error: Database connection unavailable.');

    http_response_code(500);

    exit('Database connection failed. Please check db.php.');

}


$stmt = $conn->prepare(
    "SELECT
        id,
        fname,
        lname,
        username,
        phone,
        dob,
        email,
        password,
        role,
        is_blocked
     FROM member
     WHERE username = ?
     LIMIT 1"
);

if (!$stmt) {

    error_log('Login prepare error: ' . $conn->error);

    http_response_code(500);

    exit('Unable to prepare login query.');

}

$stmt->bind_param('s', $username);

if (!$stmt->execute()) {

    error_log('Login execution error: ' . $stmt->error);

    $stmt->close();

    http_response_code(500);

    exit('Unable to process login.');

}

$stmt->bind_result(
    $id,
    $fname,
    $lname,
    $dbUsername,
    $phone,
    $dob,
    $email,
    $storedPassword,
    $role,
    $isBlocked
);

$found = $stmt->fetch();

$stmt->close();

if (!$found) {
    redirectLogin('invalid');
}

$storedPassword = (string)$storedPassword;

$isPasswordCorrect = password_verify(
    $password,
    $storedPassword
);

$isPlainTextPassword = false;

if (
    !$isPasswordCorrect &&
    $storedPassword !== '' &&
    hash_equals($storedPassword, $password)
) {

    $isPasswordCorrect = true;

    $isPlainTextPassword = true;

}

if (!$isPasswordCorrect) {
    redirectLogin('invalid');
}



if ((int)$isBlocked === 1) {
    redirectLogin('blocked');
}


if ($isPlainTextPassword) {

    $newHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $update = $conn->prepare(
        'UPDATE member SET password = ? WHERE id = ?'
    );

    if ($update) {

        $update->bind_param(
            'si',
            $newHash,
            $id
        );

        if (!$update->execute()) {

            error_log(
                'Password migration error: ' . $update->error
            );

        }

        $update->close();

    }

}

session_regenerate_id(true);

$_SESSION['user'] = [
    'id' => $id,
    'fname' => $fname,
    'lname' => $lname,
    'username' => $dbUsername,
    'phone' => $phone,
    'dob' => $dob,
    'email' => $email,
    'role' => $role,
    'is_blocked' => $isBlocked
];

$conn->close();

if ($role === 'elder') {

    header('Location: admindash.html');

} elseif ($role === 'staff') {

    header('Location: staffdash.html');

} else {

    header('Location: userdash.html');

}

exit();

?>
