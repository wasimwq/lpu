<?php
session_start(); // ✅ Start session for duplicate check


// ─────────────────────────────────────────────
// DATABASE CONNECTION
// ─────────────────────────────────────────────
$link = mysqli_connect(
    'localhost',
    'LPU_Lps',
    'iMWbGQtK;XHNs6At',
    'LPU_Lps'
);

// Check connection
if (!$link) {

    die("Database connection failed: " . mysqli_connect_error());
}


// ─────────────────────────────────────────────
// SANITIZE & GET FORM DATA
// ─────────────────────────────────────────────
$name = htmlspecialchars(trim($_POST['name'] ?? ''));

$email = htmlspecialchars(trim($_POST['email'] ?? ''));

$city = htmlspecialchars(trim($_POST['city'] ?? ''));

$phone = htmlspecialchars(trim($_POST['phone'] ?? ''));

$program = htmlspecialchars(trim($_POST['program'] ?? ''));


// ─────────────────────────────────────────────
// GET ALL UTM PARAMETERS
// ─────────────────────────────────────────────
$utm_source =
    htmlspecialchars(trim($_POST['utm_source'] ?? ''));

$utm_campaign =
    htmlspecialchars(trim($_POST['utm_campaign'] ?? ''));

$utm_content =
    htmlspecialchars(trim($_POST['utm_content'] ?? ''));

$utm_medium =
    htmlspecialchars(trim($_POST['utm_medium'] ?? ''));

$utm_creative =
    htmlspecialchars(trim($_POST['utm_creative'] ?? ''));

$utm_university =
    htmlspecialchars(trim($_POST['utm_university'] ?? ''));

$utm_keyword =
    htmlspecialchars(
        trim(
            $_POST['utm_keyword']
            ?? $_POST['utm_term']
            ?? ''
        )
    );

$gclid =
    htmlspecialchars(trim($_POST['gclid'] ?? ''));


// ─────────────────────────────────────────────
// SESSION DUPLICATE PROTECTION
// ─────────────────────────────────────────────
$submission_key = $phone . $email;

if (
    isset($_SESSION['last_submission']) &&
    $_SESSION['last_submission'] === $submission_key
) {

    header(
        "Location: https://hikeeducation.com/thanks/?email=" .
        urlencode($email)
    );

    exit;
}

$_SESSION['last_submission'] = $submission_key;


// ─────────────────────────────────────────────
// INSERT DATA INTO MYSQL
// ─────────────────────────────────────────────
$sql = "
INSERT INTO LPU_online
(
    name,
    email,
    city,
    phone,
    program,

    utm_source,
    utm_campaign,
    utm_content,
    utm_medium,

    utm_creative,
    utm_university,

    utm_keyword,
    gclid
)

VALUES
(
    '$name',
    '$email',
    '$city',
    '$phone',
    '$program',

    '$utm_source',
    '$utm_campaign',
    '$utm_content',
    '$utm_medium',

    '$utm_creative',
    '$utm_university',

    '$utm_keyword',
    '$gclid'
)
";

mysqli_query($link, $sql);


// Close DB connection
mysqli_close($link);


// ─────────────────────────────────────────────
// SALESFORCE (SFDC) INTEGRATION
// ─────────────────────────────────────────────
$sfdc_url =
"https://business-agility-9703.my.salesforce-sites.com/services/apexrest/leadCreationAPI";


$sfdc_data = json_encode([

    "name" => $name,

    "phone" => $phone,

    "email" => $email,

    "mx_City" => $city,

    "Lead_Vendor_Source" => "LPU",

    // PROGRAM
    "EnquiredforProgram" => $program,

    // UNIVERSITY
    "EnquiredforUniversity" => "LPU",

    // UTM
    "utm_source" => $utm_source,

    "SourceCampaign" => $utm_campaign,

    "SourceContent" => $utm_content,

    "SourceMedium" => $utm_medium,

    "utm_creative" => $utm_creative,

    "utm_university" => $utm_university,

    "utm_keyword" => $utm_keyword,

    "mx_utm_gclid" => $gclid,

    // OTHER
    "SourceIPAddress" => $_SERVER['REMOTE_ADDR'],

    "LeadSource" => "Google",

    // "branch" => "Mumbai"
]);


// ─────────────────────────────────────────────
// SFDC API CALL
// ─────────────────────────────────────────────
$sfdc_options = [

    'http' => [

        'header'  => "Content-Type: application/json",

        'method'  => 'POST',

        'content' => $sfdc_data,
    ],
];

$sfdc_context =
    stream_context_create($sfdc_options);

file_get_contents(
    $sfdc_url,
    false,
    $sfdc_context
);


// ─────────────────────────────────────────────
// REDIRECT
// ─────────────────────────────────────────────
header("Location: https://hikeeducation.com/thanks/");

exit();

?>