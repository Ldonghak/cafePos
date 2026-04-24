<?php
/**
 * 결제 게이트웨이 기본 클래스 (전략 패턴)
 * 새 업체 추가 시 이 클래스를 상속하여 구현
 */
abstract class BaseGateway {
    protected array $config;
    protected string $mode; // 'test' | 'live'

    public function __construct(array $config, string $mode = 'test') {
        $this->config = $config;
        $this->mode   = $mode;
    }

    /** 결제 초기화 데이터 반환 (프론트엔드에서 사용할 파라미터) */
    abstract public function getClientParams(int $orderId, int $amount, string $orderName): array;

    /** 서버사이드 결제 승인 처리 */
    abstract public function confirm(array $payload): array;

    /** 게이트웨이 코드 */
    abstract public static function code(): string;

    /** 관리자 화면에서 설정할 필드 목록 */
    abstract public static function configFields(): array;

    /** 결제 UI 방식 */
    abstract public static function uiType(): string; // 'js_sdk' | 'redirect' | 'local_agent' | 'keyboard_wedge'

    protected function httpPost(string $url, array $data, array $headers = []): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $code, 'body' => json_decode($body, true) ?? []];
    }
}
