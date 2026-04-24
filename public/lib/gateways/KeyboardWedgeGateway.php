<?php
require_once __DIR__ . '/../BaseGateway.php';

/**
 * Keyboard Wedge 방식 (USB 카드 리더기)
 * API 키 불필요 — 리더기가 키보드처럼 카드 데이터를 입력해줌
 * 브라우저의 숨겨진 input이 캡처하여 처리
 */
class KeyboardWedgeGateway extends BaseGateway {

    public static function code(): string { return 'keyboard_wedge'; }
    public static function uiType(): string { return 'keyboard_wedge'; }

    public static function configFields(): array {
        return [
            ['key' => 'capture_timeout_ms', 'label' => '캡처 타임아웃 (ms, 기본 400)', 'type' => 'number', 'required' => false],
        ];
    }

    public function getClientParams(int $orderId, int $amount, string $orderName): array {
        return [
            'gateway'    => 'keyboard_wedge',
            'orderId'    => $orderId,
            'amount'     => $amount,
            'orderName'  => $orderName,
            'timeoutMs'  => (int)($this->config['capture_timeout_ms'] ?? 400),
        ];
    }

    public function confirm(array $payload): array {
        // 카드 데이터에서 마지막 4자리 추출
        $cardRaw = $payload['card_data'] ?? '';
        $digits  = preg_replace('/\D/', '', $cardRaw);
        $last4   = strlen($digits) >= 4 ? substr($digits, -4) : '????';

        return ['success' => true, 'card_last4' => $last4, 'raw' => ['card_data' => '***']];
    }
}
