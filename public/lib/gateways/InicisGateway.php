<?php
require_once __DIR__ . '/../BaseGateway.php';

/**
 * KG이니시스 연동
 * 문서: https://manual.inicis.com
 * 방식: JavaScript SDK (INIStdPay) → 서버 승인
 */
class InicisGateway extends BaseGateway {

    public static function code(): string { return 'inicis'; }
    public static function uiType(): string { return 'js_sdk'; }

    public static function configFields(): array {
        return [
            ['key' => 'mid',       'label' => '가맹점 ID (MID)',          'type' => 'text',     'required' => true],
            ['key' => 'sign_key',  'label' => '사인키 (signKey)',          'type' => 'password', 'required' => true],
            ['key' => 'iv',        'label' => 'IV (초기화 벡터)',           'type' => 'text',     'required' => true],
        ];
    }

    public function getClientParams(int $orderId, int $amount, string $orderName): array {
        $mid       = $this->config['mid'] ?? '';
        $signKey   = $this->config['sign_key'] ?? '';
        $oid       = 'INI-' . $orderId . '-' . time();
        $timestamp = (string)(time() * 1000);
        $signature = hash('sha256', 'oid=' . $oid . '&price=' . $amount . '&timestamp=' . $timestamp);
        $verification = hash('sha256', 'mid=' . $mid . '&oid=' . $oid . '&price=' . $amount . '&signKey=' . $signKey . '&timestamp=' . $timestamp);

        return [
            'gateway'      => 'inicis',
            'mid'          => $mid,
            'oid'          => $oid,
            'price'        => $amount,
            'goodname'     => $orderName,
            'timestamp'    => $timestamp,
            'signature'    => $signature,
            'verification' => $verification,
            'mode'         => $this->mode,
            'sdkUrl'       => $this->mode === 'live'
                ? 'https://stdpay.inicis.com/stdjs/INIStdPay.js'
                : 'https://stgstdpay.inicis.com/stdjs/INIStdPay.js',
        ];
    }

    public function confirm(array $payload): array {
        // KG이니시스는 콜백으로 승인결과가 전달됨 (POST 방식)
        $resultCode = $payload['resultCode'] ?? '';
        if ($resultCode === '0000') {
            return ['success' => true, 'card_last4' => substr($payload['cardNum'] ?? '****', -4), 'raw' => $payload];
        }
        return ['success' => false, 'message' => $payload['resultMsg'] ?? '결제 실패'];
    }
}
