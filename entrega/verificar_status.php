<?php
$public_key = 'pk_mwodQeTwLB_DfWIG-gipQeSCwOvPobbAYUnYL-jyXAliZKue';
$secret_key = 'sk_qrlzCp1IywHASl54Xve8jR9clZZcbz1IpiL0ZN42M-1NmNYT';
$auth = base64_encode($public_key . ':' . $secret_key);

$transactionId = $_GET['id'] ?? null;
if (!$transactionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da transação não informado']);
    exit;
}

$url = 'https://api.pagloop.com/v1/transactions/' . urlencode($transactionId);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['status'])) {
    echo json_encode([
        'success' => true,
        'status' => $data['status'],
        'http_code' => $httpCode,
        'full_response' => $data
    ]);
} else {
    echo json_encode([
        'success' => false,
        'http_code' => $httpCode,
        'response' => $data
    ]);
}
?>