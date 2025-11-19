<?php

$public_key = 'pk_J_vYhAz9q8FMeE8ciDZ28EHQSH7U7RShDRI2T6ZRYEIJJj41';
$secret_key = 'sk_xYdSZ9yA0rNaytnIxVbTlhhyIT07LR2WF6VSA0SguyZ-gGvH';
$auth = base64_encode($public_key . ':' . $secret_key);

$data = json_decode(file_get_contents("php://input"), true);

$nome     = $data['name']  ?? 'Lucas Souza';
$email    = $data['email'] ?? 'lucas@email.com';
$cpf      = preg_replace('/\D/', '', $data['cpf'] ?? '12345678900');
$telefone = preg_replace('/\D/', '', $data['phone'] ?? '11999999999');
$amount   = intval($data['amount'] ?? 990); // valor em centavos

$payload = [
    "amount" => $amount,
    "paymentMethod" => "pix",
    "items" => [
        [
            "title" => "Produto Teste",
            "unitPrice" => $amount,
            "quantity" => 1,
            "tangible" => false
        ]
    ],
    "customer" => [
        "name" => $nome,
        "email" => $email,
        "phone" => $telefone,
        "document" => [
            "number" => $cpf,
            "type" => "cpf"
        ]
    ]
];

$ch = curl_init("https://api.pagloop.com/v1/transactions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');

if ($http_code === 200 || $http_code === 201) {
    $res = json_decode($response, true);
    $qrcode = $res['pix']['qrcode'] ?? ($res['data']['pix']['qrcode'] ?? null);
    $id = $res['id'] ?? ($res['data']['id'] ?? null);

    echo json_encode([
        'success' => true,
        'pix_data' => [
            'qrCode' => 'https://quickchart.io/qr?text=' . urlencode($qrcode),
            'qrCodeText' => $qrcode
        ],
        'transaction_id' => $id,
        'amount' => $amount
    ]);
} else {
    echo json_encode([
        'success' => false,
        'http_code' => $http_code,
        'response' => json_decode($response, true)
    ]);
}
?>