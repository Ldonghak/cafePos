<?php
require_once __DIR__ . '/../BaseGateway.php';

/**
 * 토스페이먼츠 연동
 * 문서: https://docs.tosspayments.com
 * 방식: JavaScript SDK → 서버 승인
 */
class TossGateway extends BaseGateway {

    public static function code(): string { return 'toss'; }
    public static function uiType(): string { return 'js_sdk'; }

    public static function configFields(): array {
        return [
            ['key' => 'client_key',  'label' => '클라이언트 키 (테스트: test_ck_...)',  'type' => 'text',     'required' => true],
            ['key' => 'secret_key',  'label' => '시크릿 키 (테스트: test_sk_...)',       'type' => 'password', 'required' => true],
        ];
    }

    public function getClientParams(int $orderId, int $amount, string $orderName): array {
        return [
            'gateway'    => 'toss',
            'clientKey'  => $this->config['client_key'] ?? '',
            'orderId'    => 'ORDER-' . $orderId . '-' . time(),
            'orderName'  => $orderName,
            'amount'     => $amount,
            'customerName' => '현장고객',
            'mode'       => $this->mode,
        ];
    }

    public function confirm(array $payload): array {
        // payload: { paymentKey, orderId, amount }
        $secretKey  = $this->config['secret_key'] ?? '';
        $authHeader = 'Authorization: Basic ' . base64_encode($secretKey . ':');

        $res = $this->httpPost(
            'https://api.tosspayments.com/v1/payments/confirm',
            ['paymentKey' => $payload['paymentKey'], 'orderId' => $payload['orderId'], 'amount' => $payload['amount']],
            [$authHeader]
        );

        if ($res['status'] === 200) {
            return ['success' => true, 'card_last4' => substr($res['body']['card']['number'] ?? '****', -4), 'raw' => $res['body']];
        }
        return ['success' => false, 'message' => $res['body']['message'] ?? '결제 승인 실패'];
    }
}
