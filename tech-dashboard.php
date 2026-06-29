// Handle Closing a Job Card + LIVE SMS Gateway Trigger with Error Tracking
if (isset($_GET['close_id'])) {
    $job_id = intval($_GET['close_id']);

    try {
        // Fetch details to get the custom mobile number saved in the row
        $nStmt = $pdo->prepare("SELECT customer_name, customer_phone, device_model FROM job_cards WHERE job_id = ?");
        $nStmt->execute([$job_id]);
        $jobDetails = $nStmt->fetch();

        $stmt = $pdo->prepare("UPDATE job_cards SET status = 'Completed' WHERE job_id = ?");
        $stmt->execute([$job_id]);

        if ($jobDetails) {
            $c_name = htmlspecialchars($jobDetails['customer_name']);
            $d_model = htmlspecialchars($jobDetails['device_model']);
            
            $raw_phone = trim($jobDetails['customer_phone']);
            $customer_phone = (substr($raw_phone, 0, 1) == '0') ? '+254' . substr($raw_phone, 1) : $raw_phone;
            
            $sms_message = "Hello " . $c_name . ", your device (" . $d_model . ") has been successfully repaired and is ready for collection at Njuguna Electronics. Thank you!";

            // --- AFRICA'S TALKING REAL LIVE PRODUCTION SMS GATEWAY ---
            $username = "njugunarepair"; 
            $apiKey   = "atsk_8a553fd6f9ed962f50cbc077fa6a2789d0193b70f0dccb4fa4e5094d73c419844500b52c"; 
            $url = "https://api.africastalking.com/version1/messaging";

            $data = [
                'username' => $username,
                'to'       => $customer_phone,
                'message'  => $sms_message
            ];

            $ch = curl_init();
            guess_and_set_curl_opts($ch, $url, $data, $apiKey);

            $response = curl_exec($ch);
            
            // Catch network execution blocks instantly
            if (curl_errno($ch)) {
                $curl_error = curl_error($ch);
                $msg = "<div class='alert alert-danger py-2 small'>Server Connection Error: " . htmlspecialchars($curl_error) . "</div>";
            } else {
                $msg = "
                <div class='alert alert-success py-3 shadow-sm text-start'>
                    <h6 class='fw-bold mb-1 text-success'><i class='bi bi-phone-vibrate'></i> Network Request Handed Over to Gateway</h6>
                    <div class='p-2 bg-white rounded border border-success font-monospace small text-dark'>
                        <b>Sent To:</b> " . $customer_phone . "<br>
                        <b>Gateway Server Response:</b> " . htmlspecialchars($response) . "<br>
                        <b>Message:</b> " . $sms_message . "
                    </div>
                </div>";
            }
            curl_close($ch);
        }
    } catch (\PDOException $err) {
        $msg = "<div class='alert alert-danger py-2 small'>Database Error: " . htmlspecialchars($err->getMessage()) . "</div>";
    }
}
