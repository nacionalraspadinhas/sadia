<?php
$offer_hash    = '6bdh8exgrw';
$product_hash  = 'gnzb5cy6fi';
$access_token  = 'k2wXCZgeWi122VT0THf3Agp3cUVg2DZT7wRcitREvr6xwwlr64yN6Ppfek6g';
$api_url       = 'https://api.paradisepagbr.com/api/public/v1/transactions?api_token=' . $access_token;
$postback_url  = '/webhook/pix_webhook.php';

$data = json_decode(file_get_contents("php://input"), true);

$name   = $data['name']  ?? 'Lucas Souza';
$email  = $data['email'] ?? 'lucas@email.com';
$cpf    = $data['cpf']   ?? '12345678900';
$phone  = $data['phone'] ?? '11999999999';
$amount = $data['amount'] ?? 990;
$utm    = $data['utm']   ?? [];

$payload = [
    "amount" => $amount,
    "offer_hash" => $offer_hash,
    "payment_method" => "pix",
    "customer" => [
        "name" => $name,
        "email" => $email,
        "phone_number" => $phone,
        "document" => $cpf,
        "city" => "São Paulo",
        "state" => "SP",
        "zip_code" => "01001000"
    ],
    "cart" => [[
        "product_hash" => $product_hash,
        "title" => "Produto Teste",
        "price" => $amount,
        "quantity" => 1,
        "operation_type" => 1,
        "tangible" => false
    ]],
    "installments" => 1,
    "expire_in_days" => 1,
    "postback_url" => $postback_url,
    "tracking" => [
        "utm_source"  => $utm['utm_source']  ?? '',
        "utm_medium"  => $utm['utm_medium']  ?? '',
        "utm_campaign"=> $utm['utm_campaign']?? '',
        "utm_term"    => $utm['utm_term']    ?? '',
        "utm_content" => $utm['utm_content'] ?? ''
    ]
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_TIMEOUT_MS, 3000);

$response   = curl_exec($ch);
$http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 || $http_code === 201) {
    $res = json_decode($response, true);
    $qrCodeText = $res['pix']['pix_qr_code'] ?? '';
    $transactionHash = $res['hash'] ?? null;
    $transactionId = $res['transaction'] ?? null;

    file_put_contents('logs/debug_pix.log', json_encode([
        'horario' => date('Y-m-d H:i:s'),
        'input' => $data,
        'payload_enviado' => $payload,
        'resposta_api' => $res,
        'qrCodeText' => $qrCodeText,
        'http_code' => $http_code
    ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

    echo json_encode([
        'success' => true,
        'pix_data' => [
            'qrCode' => 'https://quickchart.io/qr?text=' . urlencode($qrCodeText),
            'qrCodeText' => $qrCodeText
        ],
        'transaction_id' => $transactionId,
        'transaction_hash' => $transactionHash,
        'amount' => $amount
    ]);
} else {
    $resErro = json_decode($response, true);
    file_put_contents('debug_pix.log', json_encode([
        'horario' => date('Y-m-d H:i:s'),
        'input' => $data,
        'payload_enviado' => $payload,
        'erro_api' => $resErro,
        'http_code' => $http_code
    ], JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

    echo json_encode([
        'success' => false,
        'error' => "Erro ao gerar pagamento. HTTP: $http_code",
        'debug' => $resErro
    ]);
}
?>