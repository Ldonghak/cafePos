<?php
require_once __DIR__ . '/../BaseGateway.php';

/**
 * NICE페이먼츠 연동
 * 문서: https://developers.nicepay.co.kr
 * 방식: JavaScript SDK (AUTHNICE.requestPay) → 서버 승인
 */
class NiceGateway extends BaseGateway {

    public static function code(): string { return 'nice'; }
    public static function uiType(): string { return 'js_sdk'; }

    public static function configFields(): array {
        return [
            ['key' => 'client_id',     'label' => '클라이언트 ID',          'type' => 'text',     'required' => true],
            ['key' => 'secret_key',    'label' => '시크릿 키',               'type' => 'password', 'required' => true],
            ['key' => 'merchant_id',   'label' => '가맹점 ID (MID)',          'type' => 'text',     'required' => true],
        ];
    }

    public function getClientParams(int $orderId, int $amount, string $orderName): array {
        $ediDate  = date('YmdHis');
        $mid      = $this->config['merchant_id'] ?? '';
        $signKey  = $this->config['secret_key'] ?? '';
        $signData = hash('sha256', $mid . $amount . $ediDate . $signKey);

        return [
            'gateway'    => 'nice',
            'clientId'   => $this->config['client_id'] ?? '',
            'orderId'    => 'NICE-' . $orderId . '-' . time(),
            'orderName'  => $orderName,
            'amount'     => $amount,
            'merchantId' => $mid,
            'ediDate'    => $ediDate,
            'signData'   => $signData,
            'mode'       => $this->mode,
            'sdkUrl'     => $this->mode === 'live'
                ? 'https://pay.nicepay.co.kr/v1/js/'
                : 'https://sandbox-pay.nicepay.co.kr/v1/js/',
        ];
    }

    public function confirm(array $payload): array {
        $clientId  = $this->config['client_id'] ?? '';
        $secretKey = $this->config['secret_key'] ?? '';
        $authHeader = 'Authorization: Basic ' . base64_encode($clientId . ':' . $secretKey);
        $baseUrl    = $this->mode === 'live'
            ? 'https://api.nicepay.co.kr/v1/payments/'
            : 'https://sandbox-api.nicepay.co.kr/v1/payments/';

        $res = $this->httpPost(
            $baseUrl . $payload['tid'],
            ['amount' => $payload['amount']],
            [$authHeader]
        );

        if (($res['body']['resultCode'] ?? '') === '0000') {
            $cardNum = $res['body']['card']['cardNum'] ?? '****';
            return ['success' => true, 'card_last4' => substr($cardNum, -4), 'raw' => $res['body']];
        }
        return ['success' => false, 'message' => $res['body']['resultMsg'] ?? '결제 승인 실패'];
    }
}
