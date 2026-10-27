
<?php
/*
Pixio Shop & eCommerce HTML Template | DexignZone

Variables:
    $listID : Mailchimp List ID
    $email  : Subscription Email
*/

function pr($value)
{
    echo "<pre>";
    print_r($value);
    echo "</pre>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dzRes = [
        'status' => 0,
        'msg' => ''
    ];

    // Get and validate the submitted email
    $email = isset($_POST['dzEmail'])
        ? trim(strip_tags($_POST['dzEmail']))
        : '';

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {

        // Load the API key from an environment variable
        $apiKey = getenv('MAILCHIMP_API_KEY');

        // Mailchimp List ID
        $listID = 'c905f0cf1b';

        if (!$apiKey) {
            $dzRes['msg'] = 'Subscription service is not configured.';
            echo json_encode($dzRes);
            exit;
        }

        // Get the Mailchimp data center from the API key suffix
        $keyParts = explode('-', $apiKey);
        $dataCenter = end($keyParts);

        if (empty($dataCenter)) {
            $dzRes['msg'] = 'Subscription service is not configured.';
            echo json_encode($dzRes);
            exit;
        }

        // Mailchimp API URL
        $memberID = md5(strtolower($email));
        $url = 'https://' . $dataCenter
            . '.api.mailchimp.com/3.0/lists/'
            . $listID . '/members/' . $memberID;

        // Member information
        $json = json_encode([
            'email_address' => $email,
            'status' => 'subscribed'
        ]);

        // Send the request to Mailchimp
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        // Keep SSL certificate verification enabled
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);

        curl_close($ch);

        // Handle the API response
        if ($curlError !== 0 || $result === false) {
            $dzRes['msg'] = 'Unable to connect. Please try again later.';
        } elseif ($httpCode === 200) {
            $dzRes['status'] = 1;
            $dzRes['msg'] = 'You have successfully subscribed.';
        } else {
            $response = json_decode($result, true);
            $errorTitle = $response['title'] ?? '';

            if ($httpCode === 400 && $errorTitle === 'Member Exists') {
                $dzRes['msg'] = 'You are already subscribed.';
            } else {
                $dzRes['msg'] = 'Some problem occurred, please try again.';
            }
        }
    } else {
        $dzRes['msg'] = 'Please enter a valid email address.';
    }

    header('Content-Type: application/json');
    echo json_encode($dzRes);
    exit;
}
