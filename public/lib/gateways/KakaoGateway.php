<?php
require_once __DIR__ . '/../BaseGateway.php';

/**
 * 카카오페이 연동
 * 문서: https://developers.kakao.com/docs/latest/ko/kakaopay/single-payment
 * 방식: 서버 → 카카오 API 호출 → 앱/QR 결제
 */
class KakaoGateway extends BaseGateway {

    public static function code(): string { return 'kakao'; }
    public static function uiType(): string { return 'redirect'; }

    public static function configFields(): array {
        return [
            ['key' => 'secret_key', 'label' => '시크릿 키 (SECRET_KEY_...)', 'type' => 'password', 'required' => true],
            ['key' => 'cid',        'label' => 'CID (테스트: TC0ONETIME)',    'type' => 'text',     'required' => true],
        ];
    }

    public function getClientParams(int $orderId, int $amount, string $orderName): array {
        return [
            'gateway'   => 'kakao',
            'orderId'   => $orderId,
            'amount'    => $amount,
            'orderName' => $orderName,
            'mode'      => $this->mode,
        ];
    }

    /**
     * 결제 준비 API 호출 (서버 → 카카오)
     * 반환: { tid, next_redirect_pc_url, ... }
     */
    public function readyToPay(int $orderId, int $amount, string $orderName, string $baseUrl): array {
        $secretKey = $this->config['secret_key'] ?? '';
        $cid       = $this->config['cid'] ?? 'TC0ONETIME';

        $res = $this->httpPost(
            'https://open-api.kakaopay.com/online/v1/payment/ready',
            [
                'cid'              => $cid,
                'partner_order_id' => 'KAKAO-' . $orderId,
                'partner_user_id'  => 'POS_USER',
                'item_name'        => $orderName,
                'quantity'         => 1,
                'total_amount'     => $amount,
                'tax_free_amount'  => 0,
                'approval_url'     => $baseUrl . '/api/payment.php?action=kakao_approve&order_id=' . $orderId,
                'cancel_url'       => $baseUrl . '/index.php',
                'fail_url'         => $baseUrl . '/index.php',
            ],
            ['Authorization: SECRET_KEY ' . $secretKey]
        );

        return $res['body'] ?? [];
    }

    public function confirm(array $payload): array {
        $secretKey = $this->config['secret_key'] ?? '';
        $cid       = $this->config['cid'] ?? 'TC0ONETIME';

        $res = $this->httpPost(
            'https://open-api.kakaopay.com/online/v1/payment/approve',
            ['cid' => $cid, 'tid' => $payload['tid'], 'partner_order_id' => $payload['partner_order_id'], 'partner_user_id' => 'POS_USER', 'pg_token' => $payload['pg_token']],
            ['Authorization: SECRET_KEY ' . $secretKey]
        );

        if (!empty($res['body']['aid'])) {
            return ['success' => true, 'card_last4' => '카카오', 'raw' => $res['body']];
        }
        return ['success' => false, 'message' => $res['body']['msg'] ?? '카카오페이 승인 실패'];
    }
}
