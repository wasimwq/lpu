<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// ── SFDC API ─────────────────────────────────────────────────────────────────
define('SFDC_API_URL', 'https://business-agility-9703.my.salesforce-sites.com/services/apexrest/leadCreationAPI');

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ── Form fields ───────────────────────────────────────────────────────────
    $FullName    = htmlspecialchars($_POST["name"]        ?? "");
    $EmailId     = htmlspecialchars($_POST["email"]       ?? "");
    $Phone       = htmlspecialchars($_POST["mobile"]      ?? "");
    $City        = htmlspecialchars($_POST["city"]        ?? "");
    $Program = htmlspecialchars($_POST["program"] ?? "");

    // ── UTM params (all dynamic from URL) ─────────────────────────────────────
    $utm_source   = htmlspecialchars($_POST["utm_source"]   ?? "");
    $utm_campaign = htmlspecialchars($_POST["utm_campaign"] ?? "");
    $utm_content  = htmlspecialchars($_POST["utm_content"]  ?? "");
    $utm_medium   = htmlspecialchars($_POST["utm_medium"]   ?? "");
    $utm_keyword  = htmlspecialchars($_POST["utm_keyword"]  ?? "");
    $gclid        = htmlspecialchars($_POST["gclid"]        ?? "");

    // ── Current URL (only DB me jayega) ───────────────────────────────────────
    $current_url  = htmlspecialchars($_POST["current_url"] ?? "");

    $currentTime  = date('Y-m-d H:i:s');

    // ── Database ──────────────────────────────────────────────────────────────
    $conn = mysqli_connect("localhost", "LPU_Lps", "iMWbGQtK;XHNs6At", "LPU_Lps");
    if (!$conn) {
        die("Database Connection failed: " . mysqli_connect_error());
    }

    $sql = "INSERT INTO LPU_Chatbot 
                (fname, email, city, phoneno, program, utm_source, SourceCampaign, SourceContent, SourceMedium, utm_keyword, gclid, current_url, created_at) 
            VALUES 
                ('$FullName', '$EmailId', '$City', '$Phone', '$Program', '$utm_source', '$utm_campaign', '$utm_content', '$utm_medium', '$utm_keyword', '$gclid', '$current_url', '$currentTime')";

    if (mysqli_query($conn, $sql)) {

        // ── SFDC payload (current_url ) ─────────────────────────────
        $sfdc_data = json_encode([
            "name"            => $FullName,
            "phone"           => $Phone,
            "email"           => $EmailId,
            "mx_City"         => $City,
            "LeadSource"      => "Chatbot",
            "Lead_Vendor_Source" => "LPU",
            "EnquiredforUniversity" => "LPU",
            "EnquiredforProgram" => $Program,
            "SourceCampaign"  => $utm_campaign,
            "utm_source"  => $utm_source,
            "SourceContent"   => $utm_content,
            "SourceMedium"    => $utm_medium,
            "utm_keyword"     => $utm_keyword,
            "mx_utm_gclid"    => $gclid,
            "SourceIPAddress" => $_SERVER['REMOTE_ADDR']
        ]);

        sfdcCurl(SFDC_API_URL, $sfdc_data);

        // ── Redirect ──────────────────────────────────────────────────────────
        echo "<script>window.location.href = 'https://hikeeducation.com/lpu-online';</script>";

    } else {
        echo "DB Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}

// ── SFDC cURL ─────────────────────────────────────────────────────────────────
function sfdcCurl($url, $json_data) {
    try {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => $json_data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_data)
            ]
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            file_put_contents('sfdc_error.log', 'Curl Error: ' . curl_error($curl) . "\n", FILE_APPEND);
        }

        file_put_contents('sfdc_debug.log', "HTTP Code: $httpCode\nResponse: $response\n", FILE_APPEND);
        curl_close($curl);
        return $response;

    } catch (Exception $ex) {
        file_put_contents('sfdc_exception.log', 'Exception: ' . $ex->getMessage() . "\n", FILE_APPEND);
        return null;
    }
}
?>