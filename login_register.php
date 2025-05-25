<?php
session_start();
require_once 'config.php';

function containsInappropriateWords($text)
{
    $bannedWords = [
        // [Keep your existing long list here...]
        'fuck',
        'shit',
        'bitch', /* ... */
        'lickmepapi',  // English vulgarities
        'fuck',
        'shit',
        'bitch',
        'asshole',
        'bastard',
        'dick',
        'pussy',
        'slut',
        'whore',
        'faggot',
        'nigger',
        'cunt',
        'motherfucker',
        'cock',
        'cum',
        'cumshot',
        'creampie',
        'deepthroat',
        'blowjob',
        'handjob',
        'rimjob',
        'gangbang',
        'orgy',
        'anal',
        'fingering',
        'jerkoff',
        'jackoff',
        'masturbate',
        'masturbation',
        'facial',
        'cumrag',
        'pegging',
        'scat',
        'watersports',
        'pissing',
        'titty',
        'titties',
        'boobs',
        'boobies',
        'clit',
        'clitoris',
        'pornhub',
        'xvideos',
        'xnxx',
        'redtube',
        'onlyfans',
        'nudes',
        'sexchat',
        'sexting',
        'sugarbaby',
        'sugardaddy',
        'milf',
        'gilf',
        'camgirl',
        'camwhore',
        'vajayjay',
        'sex',
        'sexing',
        '69',
        'doggystyle',
        'threesome',
        'suck',
        'suckme',
        'suckma',
        'suckmaballs',
        'dildo',
        'strapon',
        'fap',
        'fapping',
        'orgasm',
        'load on face',
        'titsucker',
        'tittyfuck',

        // Hate speech and slurs
        'n1gger',
        'niqqa',
        'nlgger',
        'manigger',
        'ch1nk',
        'chingchong',
        'g00k',
        'kike',
        'spic',
        'wetback',
        'tranny',
        'shemale',
        'she-male',
        'pedo',
        'pedophile',
        'necrophile',
        'zoophile',
        'bestiality',
        'beastfuck',
        'towelhead',
        'raghead',
        'f@g',
        'f@ggot',
        'fa6got',
        'h0m0',

        // Tagalog profanities
        'putangina',
        'tangina',
        'gago',
        'ulol',
        'bobo',
        'tanga',
        'leche',
        'lintik',
        'puki',
        'pukinangina',
        'puke',
        'pwet',
        'burat',
        'bayag',
        'tamod',
        'jakol',
        'jakulan',
        'jakulin',
        'kantot',
        'kantutin',
        'kadyot',
        'barurot',
        'chumupa',
        'tsupa',
        'tsupain',
        'himurin',
        'salsal',
        'tsupon',
        'dilaan',
        'pokpok',
        'bayarang babae',
        'bugbog',
        'putakte',
        'pokpokin',
        'wasakin',
        'iyotin',
        'iyutan',
        'wasakin mo',
        'gapangin',
        'himod',
        'katorsex',
        'sulasoktv',
        'jaboltv',
        'jabolan',
        'jabolmoako',
        'katutin mo ako',
        'malakibilat',
        'malaki bilat',
        'bahogbilat',
        'bahog pwet',
        'pasok mo sa pwet',
        'pwetin',
        'pwetantayo',
        'kupal',
        'gagor',

        // Japanese-sounding + NSFW slangs
        'yamete',
        'loli',
        'lolislayer69',
        'hentai',
        'oppai',
        'tentacle',
        'onsen',
        'bukkake',

        // Misspellings & leetspeak
        'fuk',
        'fuq',
        'f*ck',
        'f@ck',
        'f0ck',
        'f***',
        'sh1t',
        's#it',
        'sh!t',
        'bi7ch',
        'b!tch',
        'b1tch',
        'b1tchez',
        'b!tchez',
        '@sshole',
        'a$$hole',
        'a55hole',
        '4sshole',
        'a$$',
        'a5s',
        'd1ck',
        'd!ck',
        'p00sy',
        'pussee',
        'pusy',
        'p0rn',
        'pr0n',
        'n00dz',
        'nud3s',
        's3x',
        '5ex',
        's3xt',
        's3xting',
        'c*m',
        'c*ck',
        'c0ck',
        'c0m',
        'j3rk',
        'j3rk0ff',
        'jerkin',
        'jackin',
        'jack1n',
        'cl1t',
        'd1ldo',
        'h0rni',
        'hornee',
        'horny69',
        'gayass',
        'gayboi',
        'lesbo',
        'ghey',
        'g@e',
        'g@y',
        'g4y',
        'lezbo',
        'lezbian',
        's3xual',
        'h0tsex',
        'freaky69',

        // Nonsense/creepy combos often used in bypassing
        'tralala',
        'tralalelo',
        'maderpaker',
        'minumulto',
        'beatcah',
        'lolu',
        'tulo',
        'fingeren mo ako',
        'fingering',
        '69 tayo',
        '69tayo',
        'onylatbi',
        'onylatbih',
        'dildomom',
        'dildomomtv',
        'dildomom69',
        'onlypinay',
        'pinayflix',
        'suckmaballs',
        'bahog pwet mo',
        'bahog pwet natin',
        'pasok mo sa pwet mo',
        'pasok mo sa pwet nila',
        'fingering',
        'fingeren',
        'fingermyhole',
        'lickmepapi'
    ];

    $text = strtolower($text);
    foreach ($bannedWords as $word) {
        if (strpos($text, $word) !== false) return true;
    }
    return false;
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) &&
        preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
}

function hasCharacterSpam($text)
{
    return preg_match('/(.)\1{4,}/', $text); // Detect 5+ repeated characters
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registration
    if (isset($_POST['register'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = 'user';
        $created_at = date('Y-m-d H:i:s');

        // Basic checks
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['register_error'] = 'All fields are required.';
        } elseif (containsInappropriateWords($name) || containsInappropriateWords($email)) {
            error_log("Inappropriate input: NAME=$name | EMAIL=$email\n", 3, 'logs/badword_log.txt');
            $_SESSION['register_error'] = 'Your name or email contains inappropriate words.';
        } elseif (!isValidEmail($email)) {
            $_SESSION['register_error'] = 'Invalid email format.';
        } elseif (hasCharacterSpam($name)) {
            $_SESSION['register_error'] = 'Avoid repeating characters too much.';
        } else {
            // Optional: Only allow emails from trusted providers
            $allowed_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'protonmail.com', 'edu.com', 'hotmail.com'];
            $domain = strtolower(substr(strrchr($email, "@"), 1));

            if (!in_array($domain, $allowed_domains)) {
                $_SESSION['register_error'] = 'Please use a trusted email provider.';
            } else {
                $stmt = $connect->prepare("SELECT email FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $_SESSION['register_error'] = 'Email is already registered!';
                } else {
                    registerUser($connect, $name, $email, $password, $role, $created_at);
                }
            }
        }

        $_SESSION['active_form'] = isset($_SESSION['register_error']) ? 'register' : null;
        header("Location: index.php");
        exit();
    }

    // Login
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Email and Password are required.';
        } elseif (!isValidEmail($email)) {
            $_SESSION['login_error'] = 'Invalid email format.';
        } else {
            $stmt = $connect->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    header("Location: " . ($user['role'] === 'admin' ? 'admin/admin_page.php' : 'user_page.php'));
                    $_SESSION[$user['role'] . '_login_success'] = true;
                    exit();
                } else {
                    $_SESSION['login_error'] = 'Incorrect email or password.';
                }
            } else {
                $_SESSION['login_error'] = 'Incorrect email or password.';
            }
        }

        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }
}

function registerUser($connect, $name, $email, $password, $role, $created_at)
{
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $connect->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $created_at);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'Registration successful! Please log in.';
        $_SESSION['active_form'] = 'login';
    } else {
        $_SESSION['register_error'] = 'Registration failed. Please try again.';
    }
}
