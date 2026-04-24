<?php
require_once __DIR__ . '/../BaseGateway.php';

/**
 * NHN KCP 연동
 * 문서: https://kcp.co.kr/developer
 * 방식: JavaScript SDK (주문창) → 서버 승인 (배치 키)
 */
class KcpGateway extends BaseGateway {

    public static function code(): string { return 'kcp'; }
    public static function uiType(): string { return 'js_sdk'; }

    public static function configFields(): array {
        return [
            ['key' => 'site_cd',    'label' => '사이트 코드 (site_cd)',    'type' => 'text',     'required' => true],
            ['key' => 'site_key',   'label' => '사이트 키 (site_key)',      'type' => 'password', 'required' => true],
            ['key' => 'site_name',  'label' => '사이트 명',                 'type' => 'text',     'required' => false],
        ];
    }

    public function getClientParams(int $orderId, int $amount, string $orderName): array {
        $siteCd  = $this->config['site_cd'] ?? '';
        $ordrIdxx = 'KCP' . str_pad($orderId, 8, '0', STR_PAD_LEFT) . substr(time(), -6);

        return [
            'gateway'    => 'kcp',
            'site_cd'    => $siteCd,
            'ordr_idxx'  => $ordrIdxx,
            'good_name'  => $orderName,
            'good_mny'   => $amount,
            'mode'       => $this->mode,
            'sdkUrl'     => $this->mode === 'live'
                ? 'https://pay.kcp.co.kr/plugin/payplus_plugin.jsp'
                : 'https://testpay.kcp.co.kr/plugin/payplus_plugin.jsp',
        ];
    }

    public function confirm(array $payload): array {
        // KCP 승인은 서버사이드 res_cd 확인
        $resCd = $payload['res_cd'] ?? '';
        if ($resCd === '0000') {
            return ['success' => true, 'card_last4' => substr($payload['card_no'] ?? '****', -4), 'raw' => $payload];
        }
        return ['success' => false, 'message' => $payload['res_msg'] ?? '결제 실패'];
    }
}
