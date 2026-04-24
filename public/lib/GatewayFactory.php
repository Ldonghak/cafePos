<?php
require_once __DIR__ . '/BaseGateway.php';
require_once __DIR__ . '/gateways/TossGateway.php';
require_once __DIR__ . '/gateways/NiceGateway.php';
require_once __DIR__ . '/gateways/InicisGateway.php';
require_once __DIR__ . '/gateways/KcpGateway.php';
require_once __DIR__ . '/gateways/KakaoGateway.php';
require_once __DIR__ . '/gateways/KeyboardWedgeGateway.php';

class GatewayFactory {

    /** 지원 게이트웨이 목록 (새 업체 추가 시 여기에만 등록) */
    private static array $registry = [
        'toss'            => TossGateway::class,
        'nice'            => NiceGateway::class,
        'inicis'          => InicisGateway::class,
        'kcp'             => KcpGateway::class,
        'kakao'           => KakaoGateway::class,
        'keyboard_wedge'  => KeyboardWedgeGateway::class,
    ];

    /** DB에서 현재 활성 게이트웨이를 읽어 인스턴스 반환 */
    public static function active(PDO $pdo): ?BaseGateway {
        $row = $pdo->query("SELECT * FROM payment_gateways WHERE is_active = 1 LIMIT 1")->fetch();
        if (!$row) return null;
        return self::make($row['code'], json_decode($row['config'] ?? '{}', true) ?? [], $row['mode']);
    }

    /** 코드로 인스턴스 생성 */
    public static function make(string $code, array $config = [], string $mode = 'test'): ?BaseGateway {
        $class = self::$registry[$code] ?? null;
        if (!$class) return null;
        return new $class($config, $mode);
    }

    /** 등록된 모든 게이트웨이 메타 정보 반환 */
    public static function all(): array {
        $result = [];
        foreach (self::$registry as $code => $class) {
            $result[$code] = [
                'code'         => $code,
                'uiType'       => $class::uiType(),
                'configFields' => $class::configFields(),
            ];
        }
        return $result;
    }
}
