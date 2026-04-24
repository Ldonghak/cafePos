<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
requireLogin('admin');

$me = currentUser();
$pdo = getDB();
$menus  = $pdo->query("SELECT * FROM menus ORDER BY category, id")->fetchAll();
$users  = $pdo->query("SELECT id,username,name,role,is_active,created_at FROM users ORDER BY id")->fetchAll();

$sd = $_GET['sd'] ?? '';
$ed = $_GET['ed'] ?? '';
$whereClause = "";
$params = [];
if ($sd && $ed) {
    $whereClause = "WHERE o.created_at >= ? AND o.created_at <= ?";
    $params[] = $sd . ' 00:00:00';
    $params[] = $ed . ' 23:59:59';
}

$stmt = $pdo->prepare("
    SELECT o.*, 
           GROUP_CONCAT(oi.menu_name ORDER BY oi.id SEPARATOR ', ') AS items_summary,
           (SELECT COALESCE(username, ip_address) FROM order_logs WHERE order_id = o.id ORDER BY id DESC LIMIT 1) as last_modifier
    FROM orders o 
    LEFT JOIN order_items oi ON oi.order_id=o.id 
    $whereClause 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 500
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

$searchRevenue = 0; $searchOrders = 0;
foreach ($orders as $o) { if ($o['status']==='완료'){$searchRevenue+=$o['total_price'];$searchOrders++;} }

$todayStr = date('Y-m-d');
$todayStats = $pdo->query("SELECT COUNT(*) as cnt, SUM(total_price) as rev FROM orders WHERE status='완료' AND created_at >= '$todayStr 00:00:00'")->fetch();
$todayRevenue = $todayStats['rev'] ?? 0;
$todayOrdersCount = $todayStats['cnt'] ?? 0;

// 매출 통계 (최근 6개월)
$monthlySales = [];
for ($i = 0; $i < 6; $i++) {
    $k = date('Y-m', strtotime("-$i month"));
    $monthlySales[$k] = ['m_label' => date('Y년 n월', strtotime("-$i month")), 'rev' => 0, 'cnt' => 0, 'cnt_cancel' => 0];
}
$s_date = date('Y-m-01 00:00:00', strtotime("-5 month"));
$sales_data = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as m, 
                                  SUM(CASE WHEN status='완료' THEN 1 ELSE 0 END) as cnt, 
                                  SUM(CASE WHEN status='완료' THEN total_price ELSE 0 END) as rev,
                                  SUM(CASE WHEN status='취소' THEN 1 ELSE 0 END) as cnt_cancel
                           FROM orders WHERE created_at >= '$s_date' GROUP BY m")->fetchAll();
foreach ($sales_data as $row) {
    if (isset($monthlySales[$row['m']])) {
        $monthlySales[$row['m']]['rev'] = $row['rev'];
        $monthlySales[$row['m']]['cnt'] = $row['cnt'];
        $monthlySales[$row['m']]['cnt_cancel'] = $row['cnt_cancel'];
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>관리자 | CafePOS</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;700;900&display=swap');
:root{--bg:#0f0f13;--sur:#1a1a22;--sur2:#242430;--ac:#6c63ff;--ac2:#ff6584;--green:#00d084;--amber:#ffb347;--red:#ff4d6d;--text:#f0f0f8;--muted:#8888aa;--bdr:rgba(255,255,255,.07);--r:14px}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Noto Sans KR',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
header{display:flex;align-items:center;justify-content:space-between;padding:14px 28px;background:var(--sur);border-bottom:1px solid var(--bdr);position:sticky;top:0;z-index:10}
.logo{font-size:1.15rem;font-weight:900}.logo span{color:var(--ac)}
.hright{display:flex;align-items:center;gap:10px}
.hi{font-size:.8rem;color:var(--muted)}
.hbtn{padding:7px 16px;background:var(--sur2);border:1px solid var(--bdr);border-radius:9px;color:var(--muted);font-size:.8rem;cursor:pointer;text-decoration:none;transition:.2s;font-family:inherit}
.hbtn:hover{border-color:var(--ac);color:var(--ac)}
.hbtn.red:hover{border-color:var(--red);color:var(--red)}

/* tabs */
.tabs{display:flex;gap:4px;padding:16px 28px 0;border-bottom:1px solid var(--bdr);background:var(--sur)}
.tab{padding:10px 20px;border-radius:10px 10px 0 0;cursor:pointer;font-size:.88rem;font-weight:600;color:var(--muted);border:1px solid transparent;border-bottom:none;transition:.2s}
.tab.active{background:var(--bg);border-color:var(--bdr);color:var(--text)}
.tab-panel{display:none}.tab-panel.active{display:block}

.wrap{max-width:1100px;margin:0 auto;padding:28px 22px}
.grid2{display:grid;grid-template-columns:320px 1fr;gap:22px}
.full{grid-column:1/-1}
@media(max-width:850px){.grid2{grid-template-columns:1fr}}

/* stats */
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
.sbox{background:var(--sur);border:1px solid var(--bdr);border-radius:16px;padding:18px 20px}
.slbl{font-size:.73rem;color:var(--muted);margin-bottom:6px;font-weight:600}
.sval{font-size:1.5rem;font-weight:900}
.sval.g{color:var(--green)}.sval.a{color:var(--ac)}

/* card */
.card{background:var(--sur);border:1px solid var(--bdr);border-radius:18px;padding:22px}
.ctitle{font-size:.95rem;font-weight:800;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.badge{background:var(--ac);color:#fff;font-size:.7rem;padding:2px 9px;border-radius:20px}

/* form */
.fg{margin-bottom:13px}
label{display:block;font-size:.72rem;font-weight:700;color:var(--muted);margin-bottom:6px;letter-spacing:.4px;text-transform:uppercase}
input[type=text],input[type=number],input[type=password],select{width:100%;padding:11px 13px;background:var(--sur2);border:1px solid var(--bdr);border-radius:10px;color:var(--text);font-size:.88rem;font-family:inherit;outline:none;transition:.2s}
input:focus,select:focus{border-color:var(--ac)}
select option{background:var(--sur2)}
.btn{width:100%;padding:12px;background:linear-gradient(135deg,var(--ac),#8b80ff);border:none;border-radius:11px;color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;font-family:inherit;transition:.2s}
.btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(108,99,255,.35)}

/* table */
.tw{overflow-x:auto}
table{width:100%;border-collapse:collapse;font-size:.84rem}
th{text-align:left;padding:9px 11px;color:var(--muted);font-size:.72rem;border-bottom:1px solid var(--bdr);text-transform:uppercase;font-weight:700}
td{padding:11px 11px;border-bottom:1px solid var(--bdr);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(255,255,255,.02)}
.empty td{text-align:center;color:var(--muted);padding:24px}

.tag{display:inline-block;padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:700}
.tc{background:rgba(108,99,255,.15);color:var(--ac);border:1px solid rgba(108,99,255,.3)}
.ts{background:rgba(255,101,132,.15);color:var(--ac2);border:1px solid rgba(255,101,132,.3)}
.tt{background:rgba(0,208,132,.15);color:var(--green);border:1px solid rgba(0,208,132,.3)}
.te{background:rgba(255,179,71,.15);color:var(--amber);border:1px solid rgba(255,179,71,.3)}

.role-sa{background:rgba(255,179,71,.15);color:var(--amber);border:1px solid rgba(255,179,71,.3)}
.role-ad{background:rgba(108,99,255,.15);color:var(--ac);border:1px solid rgba(108,99,255,.3)}
.role-us{background:rgba(255,255,255,.06);color:var(--muted);border:1px solid var(--bdr)}

.abtn{padding:5px 11px;border-radius:7px;cursor:pointer;font-size:.76rem;font-weight:600;font-family:inherit;border:1px solid;transition:.2s;margin-right:3px}
.abtn-del{background:rgba(255,77,109,.1);border-color:rgba(255,77,109,.3);color:var(--red)}
.abtn-del:hover{background:rgba(255,77,109,.25)}
.abtn-tog{background:rgba(255,179,71,.1);border-color:rgba(255,179,71,.3);color:var(--amber)}
.abtn-tog:hover{background:rgba(255,179,71,.25)}
.abtn-pw{background:rgba(108,99,255,.1);border-color:rgba(108,99,255,.3);color:var(--ac)}
.abtn-pw:hover{background:rgba(108,99,255,.25)}

.st-완료{background:rgba(0,208,132,.15);color:var(--green);border:1px solid rgba(0,208,132,.3)}
.st-대기{background:rgba(255,179,71,.15);color:var(--amber);border:1px solid rgba(255,179,71,.3)}
.st-실패, .st-취소{background:rgba(255,77,109,.15);color:var(--red);border:1px solid rgba(255,77,109,.3)}
.stag{display:inline-block;padding:3px 9px;border-radius:20px;font-size:.72rem;font-weight:700}

/* modal */
.mo{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:100;align-items:center;justify-content:center;backdrop-filter:blur(5px)}
.mo.show{display:flex}
.mbox{background:var(--sur);border:1px solid var(--bdr);border-radius:20px;padding:32px;width:90%;max-width:380px}
.mbox h3{font-size:1rem;font-weight:800;margin-bottom:20px}
.mactions{display:flex;gap:10px;margin-top:18px}
.mcancel{flex:1;padding:11px;background:var(--sur2);border:1px solid var(--bdr);border-radius:10px;color:var(--muted);cursor:pointer;font-family:inherit;font-size:.88rem;transition:.2s}
.mcancel:hover{border-color:var(--red);color:var(--red)}
.msubmit{flex:2;padding:11px;background:linear-gradient(135deg,var(--ac),#8b80ff);border:none;border-radius:10px;color:#fff;font-weight:700;cursor:pointer;font-family:inherit;font-size:.88rem}
</style>
</head>
<body>
<header>
  <div class="logo">☕ <span>Cafe</span>POS <small style="font-size:.7rem;color:var(--muted);margin-left:4px;">관리자</small></div>
  <div class="hright">
    <span class="hi">👤 <?=htmlspecialchars($me['name'])?> (<?=htmlspecialchars($me['role'])?>)</span>
    <a href="/index.php" class="hbtn">← POS</a>
    <a href="/logout.php" class="hbtn red">로그아웃</a>
  </div>
</header>

<!-- TABS -->
<div class="tabs">
  <div class="tab active" onclick="switchTab('menu')">🍽 메뉴관리</div>
  <div class="tab" onclick="switchTab('order')">📦 주문내역</div>
  <div class="tab" onclick="switchTab('sales')">📊 매출&정산</div>
  <?php if(isAdmin()): ?>
  <div class="tab" onclick="switchTab('user')">👥 사용자관리</div>
  <div class="tab" onclick="switchTab('pay')">💳 결제설정</div>
  <?php endif; ?>
</div>

<!-- ═══ 메뉴관리 탭 ═══ -->
<div id="tab-menu" class="tab-panel active">
<div class="wrap">
  <div class="stats">
    <div class="sbox"><div class="slbl">오늘 총 매출</div><div class="sval g"><?=number_format($todayRevenue)?>원</div></div>
    <div class="sbox"><div class="slbl">오늘 완료 주문</div><div class="sval a"><?=$todayOrdersCount?>건</div></div>
    <div class="sbox"><div class="slbl">등록 메뉴</div><div class="sval"><?=count($menus)?>개</div></div>
  </div>
  <div class="grid2">
    <div class="card">
      <div class="ctitle">🍽 메뉴 추가</div>
      <form id="addMenuForm">
        <div class="fg"><label>메뉴 이름</label><input type="text" id="mName" placeholder="예: 바닐라라떼" required></div>
        <div class="fg"><label>가격 (원)</label><input type="number" id="mPrice" placeholder="예: 4500" min="100" step="100" required></div>
        <div class="fg"><label>카테고리</label>
          <select id="mCat"><option value="커피">☕ 커피</option><option value="스무디">🍓 스무디</option><option value="차">🍵 차</option><option value="기타">🥤 기타</option></select>
        </div>
        <div class="fg"><label>메뉴 설명 <small style="color:var(--muted);font-weight:400">(POS 화면에 표시)</small></label>
          <textarea id="mDesc" placeholder="예: 승언도 스페셔로 추출한 승었한 블랜드 (옵션: 다시 스틜민�...)"
            style="width:100%;padding:10px 13px;background:var(--sur2);border:1px solid var(--bdr);border-radius:10px;color:var(--text);font-size:.85rem;font-family:inherit;resize:vertical;min-height:68px;outline:none;transition:.2s" onfocus="this.style.borderColor='var(--ac)'" onblur="this.style.borderColor=''"></textarea>
        </div>
        <button class="btn" type="submit">+ 메뉴 등록</button>
      </form>
    </div>
    <div class="card">
      <div class="ctitle">📋 메뉴 목록 <span class="badge"><?=count($menus)?>개</span></div>
      <div class="tw"><table>
        <thead><tr><th>메뉴명</th><th>가격</th><th>분류</th><th>관리</th></tr></thead>
        <tbody id="menuTbody">
        <?php $tmap=['커피'=>'tc','스무디'=>'ts','차'=>'tt','기타'=>'te'];
        $current_category = '';
        foreach($menus as $m): $tc=$tmap[$m['category']]??'te'; 
          if ($current_category !== $m['category']):
            $current_category = $m['category'];
        ?>
        <tr style="background:var(--sur2)">
          <td colspan="4" style="font-weight:800;color:var(--text);padding:8px 11px;font-size:0.9rem;border-bottom:1px solid var(--bdr)">
            <span class="tag <?=$tc?>"><?=htmlspecialchars($current_category)?></span> 카테고리
          </td>
        </tr>
        <?php endif; ?>
        <tr id="mr-<?=$m['id']?>">
          <td style="font-weight:600;<?=$m['is_available']==0?'opacity:.4;':''?>">
            <?=htmlspecialchars($m['name'])?>
            <?php if(!empty($m['description'])): ?>
            <div style="font-size:.72rem;color:var(--muted);font-weight:400;margin-top:2px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=htmlspecialchars($m['description'])?></div>
            <?php endif; ?>
          </td>
          <td style="color:var(--ac);font-weight:700;white-space:nowrap"><?=number_format($m['price'])?>원</td>
          <td style="white-space:nowrap"><span class="tag <?=$tc?>" style="white-space:nowrap;display:inline-block"><?=htmlspecialchars($m['category'])?></span></td>
          <td style="white-space:nowrap">
            <button class="abtn abtn-pw" style="white-space:nowrap" onclick="openEditMenu(<?=$m['id']?>,<?=htmlspecialchars(json_encode(['name'=>$m['name'],'price'=>$m['price'],'category'=>$m['category'],'description'=>$m['description']??'']),ENT_QUOTES)?> )">수정</button>
            <button class="abtn abtn-tog" style="white-space:nowrap" onclick="toggleMenu(<?=$m['id']?>,this)"><?=$m['is_available']?'숨김':'표시'?></button>
            <button class="abtn abtn-del" style="white-space:nowrap" onclick="deleteMenu(<?=$m['id']?>)">삭제</button>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
</div>

<!-- ═══ 주문내역 탭 ═══ -->
<div id="tab-order" class="tab-panel">
<div class="wrap">
  <div class="card full">
    <div class="ctitle">📦 최근 주문 내역 <span class="badge"><?=count($orders)?>건 조회됨</span> <span style="margin-left:auto;font-size:0.85rem;color:var(--green)">검색 매출: <?=number_format($searchRevenue)?>원</span></div>
    
    <form method="GET" action="admin.php" style="margin-bottom:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <input type="hidden" name="tab" value="order">
      <input type="date" name="sd" value="<?=htmlspecialchars($sd)?>" style="width:auto;padding:7px;border-radius:8px;background:var(--sur2);color:var(--text);border:1px solid var(--bdr);color-scheme:dark;">
      <span style="color:var(--muted)">~</span>
      <input type="date" name="ed" value="<?=htmlspecialchars($ed)?>" style="width:auto;padding:7px;border-radius:8px;background:var(--sur2);color:var(--text);border:1px solid var(--bdr);color-scheme:dark;">
      <button type="submit" class="abtn abtn-pw" style="padding:8px 16px;background:var(--ac);color:#fff;border:none">검색</button>
      
      <div style="margin-left:auto;display:flex;gap:4px">
        <button type="button" class="abtn role-us" onclick="setDateRange('today')">오늘</button>
        <button type="button" class="abtn role-us" onclick="setDateRange('yesterday')">어제</button>
        <button type="button" class="abtn role-us" onclick="setDateRange('week')">일주일</button>
        <button type="button" class="abtn role-us" onclick="setDateRange('thisMonth')">이번달</button>
        <button type="button" class="abtn role-us" onclick="setDateRange('lastMonth')">저번달</button>
      </div>
    </form>

    <div class="tw"><table>
      <thead><tr><th>#</th><th>주문 항목</th><th>금액</th><th>카드</th><th>상태</th><th>일시</th></tr></thead>
      <tbody>
      <?php if(empty($orders)): ?><tr class="empty"><td colspan="6">주문 내역이 없습니다</td></tr>
      <?php else: foreach($orders as $o): ?>
      <tr onclick="openOrderDetail(<?=$o['id']?>)" style="cursor:pointer" title="클릭하여 상세 보기">
        <td style="color:var(--muted);font-size:.8rem">#<?=$o['id']?></td>
        <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=htmlspecialchars($o['items_summary']??'-')?></td>
        <td style="color:var(--green);font-weight:700"><?=number_format($o['total_price'])?>원</td>
        <td style="color:var(--muted);font-size:.8rem"><?=$o['card_last4']?'****-'.htmlspecialchars($o['card_last4']):'-'?></td>
        <td><span class="stag st-<?=$o['status']?>"><?=$o['status']?></span></td>
        <td style="color:var(--muted);font-size:.78rem;white-space:nowrap">
          <?=date('m/d H:i',strtotime($o['created_at']))?>
          <?php if($o['last_modifier']): ?>
            <br><a href="#" onclick="openOrderLogs(<?=$o['id']?>, event); return false;" style="color:var(--ac);text-decoration:underline;font-size:0.75rem;font-weight:600" title="변경 로그 보기">ID: <?=htmlspecialchars($o['last_modifier'])?></a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table></div>
  </div>
</div>
</div>

<!-- ═══ 매출&정산 탭 ═══ -->
<div id="tab-sales" class="tab-panel">
<div class="wrap">
  <div class="card full">
    <div class="ctitle">📊 월별 매출 및 정산 내역 <span class="badge">최근 6개월</span></div>
    <div class="tw"><table>
      <thead><tr><th>연월</th><th>주문 건수</th><th>총 매출(원)</th><th>정산 상태</th></tr></thead>
      <tbody>
      <?php foreach($monthlySales as $ms): ?>
      <tr>
        <td style="font-weight:700;color:var(--text);font-size:1.05rem;padding:16px 11px"><?=$ms['m_label']?></td>
        <td style="color:var(--muted)">
          총 <?=number_format($ms['cnt']+$ms['cnt_cancel'])?>건 
          <span style="font-size:0.75rem;margin-left:4px">(<span style="color:var(--green)">완료 <?=number_format($ms['cnt'])?></span> / <span style="color:var(--red)">취소 <?=number_format($ms['cnt_cancel'])?></span>)</span>
        </td>
        <td style="color:var(--green);font-weight:900;font-size:1.2rem"><?=number_format($ms['rev'])?>원</td>
        <td><span class="tag <?=$ms['rev']>0?'tc':'role-us'?>"><?=$ms['rev']>0?'정산완료':'내역없음'?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
</div>
</div>

<!-- ═══ 사용자관리 탭 ═══ -->
<?php if(isAdmin()): ?>
<div id="tab-user" class="tab-panel">
<div class="wrap">
  <div class="grid2">
    <div class="card">
      <div class="ctitle">👤 사용자 추가</div>
      <form id="addUserForm">
        <div class="fg"><label>아이디</label><input type="text" id="uId" placeholder="로그인 아이디" required></div>
        <div class="fg"><label>이름</label><input type="text" id="uName" placeholder="표시 이름" required></div>
        <div class="fg"><label>비밀번호</label><input type="password" id="uPw" placeholder="4자 이상" required></div>
        <div class="fg"><label>역할</label>
          <select id="uRole">
            <option value="user">일반 사용자</option>
            <option value="admin">관리자</option>
            <?php if(isSuperAdmin()): ?><option value="super_admin">슈퍼관리자</option><?php endif; ?>
          </select>
        </div>
        <button class="btn" type="submit">+ 사용자 등록</button>
      </form>
    </div>
    <div class="card">
      <div class="ctitle">👥 사용자 목록 <span class="badge"><?=count($users)?>명</span></div>
      <div class="tw"><table>
        <thead><tr><th>아이디</th><th>이름</th><th>역할</th><th>상태</th><th>관리</th></tr></thead>
        <tbody id="userTbody">
        <?php $rmap=['super_admin'=>['슈퍼관리자','role-sa'],'admin'=>['관리자','role-ad'],'user'=>['사용자','role-us']];
        foreach($users as $u): [$rlbl,$rcls]=$rmap[$u['role']]??['?','role-us']; ?>
        <tr id="ur-<?=$u['id']?>">
          <td style="font-weight:700"><?=htmlspecialchars($u['username'])?></td>
          <td><?=htmlspecialchars($u['name'])?></td>
          <td><span class="tag <?=$rcls?>"><?=$rlbl?></span></td>
          <td><span style="font-size:.78rem;color:<?=$u['is_active']?'var(--green)':'var(--muted)'?>"><?=$u['is_active']?'활성':'비활성'?></span></td>
          <td>
            <?php if($u['id']!==$me['id']): ?>
            <button class="abtn abtn-pw" onclick="openPwModal(<?=$u['id']?>,<?=htmlspecialchars(json_encode($u['username']))?>)">PW</button>
            <button class="abtn abtn-tog" onclick="toggleUser(<?=$u['id']?>,this)"><?=$u['is_active']?'비활성':'활성'?></button>
            <?php if(isSuperAdmin()||$u['role']==='user'): ?>
            <button class="abtn abtn-del" onclick="deleteUser(<?=$u['id']?>)">삭제</button>
            <?php endif; ?>
            <?php else: ?><span style="font-size:.75rem;color:var(--muted)">본인</span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
</div>
<?php endif; ?>

<!-- ═══ 결제설정 탭 ═══ -->
<?php if(isAdmin()): ?>
<div id="tab-pay" class="tab-panel">
<div class="wrap">
  <div class="card full" style="grid-column:1/-1">
    <div class="ctitle">💳 결제 게이트웨이 설정</div>
    <p style="font-size:.83rem;color:var(--muted);margin-bottom:20px;">활성화된 게이트웨이만 POS 결제에 사용됩니다. API 키를 저장하면 즉시 적용됩니다.</p>
    <div id="gwList" style="display:flex;flex-direction:column;gap:16px;">로딩 중...</div>
  </div>
</div>
</div>
<?php endif; ?>

<!-- 게이트웨이 설정 모달 -->
<div class="mo" id="gwModal">
  <div class="mbox" style="max-width:480px">
    <h3 id="gwModalTitle">⚙ 게이트웨이 설정</h3>
    <div id="gwModalFields"></div>
    <div style="margin-top:12px">
      <label>운영 모드</label>
      <select id="gwModalMode" style="width:100%;padding:10px;background:var(--sur2);border:1px solid var(--bdr);border-radius:9px;color:var(--text);font-family:inherit">
        <option value="test">🧪 테스트 모드</option>
        <option value="live">🚀 라이브 모드</option>
      </select>
    </div>
    <div class="mactions">
      <button class="mcancel" onclick="document.getElementById('gwModal').classList.remove('show')">취소</button>
      <button class="msubmit" onclick="saveGwConfig()">저장</button>
    </div>
  </div>
</div>

<!-- 메뉴 수정 모달 -->
<div class="mo" id="editMenuModal">
  <div class="mbox" style="max-width:460px">
    <h3>✏ 메뉴 수정</h3>
    <div class="fg"><label>메뉴 이름</label><input type="text" id="em_name" required></div>
    <div class="fg"><label>가격 (원)</label><input type="number" id="em_price" min="100" step="100" required></div>
    <div class="fg"><label>카테고리</label>
      <select id="em_cat">
        <option value="커피">☕ 커피</option>
        <option value="스무디">🍓 스무디</option>
        <option value="차">🍵 차</option>
        <option value="기타">🥤 기타</option>
      </select>
    </div>
    <div class="fg"><label>메뉴 설명 <small style="color:var(--muted);font-weight:400">(POS 화면에 표시)</small></label>
      <textarea id="em_desc" placeholder="간단한 메뉴 설명을 입력하세요."
        style="width:100%;padding:10px 13px;background:var(--sur2);border:1px solid var(--bdr);border-radius:10px;color:var(--text);font-size:.85rem;font-family:inherit;resize:vertical;min-height:72px;outline:none;transition:.2s"
        onfocus="this.style.borderColor='var(--ac)'" onblur="this.style.borderColor=''"></textarea>
    </div>
    <div class="mactions">
      <button class="mcancel" onclick="document.getElementById('editMenuModal').classList.remove('show')">취소</button>
      <button class="msubmit" onclick="submitEditMenu()">저장</button>
    </div>
  </div>
</div>

<!-- 비밀번호 변경 모달 -->
<div class="mo" id="pwModal">
  <div class="mbox">
    <h3>🔑 비밀번호 변경 — <span id="pwTarget"></span></h3>
    <div class="fg"><label>새 비밀번호</label><input type="password" id="newPw" placeholder="4자 이상" autocomplete="new-password"></div>
    <div class="mactions">
      <button class="mcancel" onclick="document.getElementById('pwModal').classList.remove('show')">취소</button>
      <button class="msubmit" onclick="submitPwChange()">변경</button>
    </div>
  </div>
</div>

<!-- ═══ 주문 상세 모달 ═══ -->
<div id="orderModal" class="mo">
  <div class="mbox" style="max-width:500px">
    <h3>🧾 주문 상세 내역 <span id="od_id" style="color:var(--muted);font-weight:400"></span></h3>
    <div style="margin-bottom:14px;display:flex;justify-content:space-between">
      <span style="font-size:.8rem;color:var(--muted)" id="od_time"></span>
      <span class="stag" id="od_status"></span>
    </div>
    <div class="tw">
      <table style="margin-bottom:0">
        <thead><tr><th>메뉴명</th><th style="width:60px;text-align:center">수량</th><th>금액</th></tr></thead>
        <tbody id="od_items"></tbody>
      </table>
    </div>
    <div style="text-align:right;font-size:1.1rem;font-weight:900;color:var(--green);margin:16px 0;padding-top:16px;border-top:1px solid var(--bdr)">
      총 결제금액: <span id="od_total"></span>원
    </div>
    <div class="mactions">
      <button class="mcancel" onclick="document.getElementById('orderModal').classList.remove('show')">닫기</button>
      <button class="msubmit" onclick="saveOrderChanges()" style="background:var(--ac);flex:1.5">수정 저장</button>
      <button class="msubmit" onclick="cancelOrder()" style="background:var(--red);flex:1">주문 취소</button>
    </div>
  </div>
</div>

<!-- ═══ 주문 로그 모달 ═══ -->
<div id="logModal" class="mo">
  <div class="mbox" style="max-width:500px">
    <h3>📝 주문 처리 로그 <span id="log_od_id" style="color:var(--muted);font-weight:400"></span></h3>
    <div class="tw" style="max-height:300px;overflow-y:auto;border:1px solid var(--bdr);border-radius:10px;margin-bottom:14px">
      <table style="margin-bottom:0">
        <thead><tr><th>일시</th><th>행동</th><th>상세</th><th>작업자(ID)</th></tr></thead>
        <tbody id="log_items"></tbody>
      </table>
    </div>
    <div class="mactions">
      <button class="mcancel" onclick="document.getElementById('logModal').classList.remove('show')" style="flex:1">닫기</button>
    </div>
  </div>
</div>

<script>
// ─── 탭 유지하며 새로고침 ───
function reloadPage() {
  const url = new URL(window.location.href);
  const activeTab = document.querySelector('.tab-panel.active');
  if (activeTab) {
    const tabName = activeTab.id.replace('tab-', '');
    url.searchParams.set('tab', tabName);
  }
  window.location.href = url.toString();
}
// ─── 탭 전환 ───
const TAB_NAMES = ['menu','order','sales','user','pay'];
function switchTab(name){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  let index = TAB_NAMES.indexOf(name);
  if(index !== -1) {
    let tabs = document.querySelectorAll('.tabs .tab');
    if(tabs[index]) tabs[index].classList.add('active');
  }
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.getElementById('tab-'+name)?.classList.add('active');
  if(name==='pay') loadGateways();
}

// ─── 메뉴 추가 ───
document.getElementById('addMenuForm').addEventListener('submit',async e=>{
  e.preventDefault();
  const r=await fetch('/api/menus.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({name:document.getElementById('mName').value.trim(),
      price:parseInt(document.getElementById('mPrice').value),
      category:document.getElementById('mCat').value,
      description:document.getElementById('mDesc').value.trim()})}).then(r=>r.json());
  if(r.success){alert('✅ '+r.message);reloadPage();}else alert('❌ '+r.message);
});

// ─── 메뉴 삭제 ───
async function deleteMenu(id){
  if(!confirm('삭제하시겠습니까?'))return;
  const r=await fetch('/api/menus.php?id='+id,{method:'DELETE'}).then(r=>r.json());
  if(r.success)document.getElementById('mr-'+id)?.remove();else alert('❌ '+r.message);
}

// ─── 메뉴 숨김 ───
async function toggleMenu(id,btn){
  const r=await fetch('/api/menus.php?id='+id+'&action=toggle',{method:'PATCH'}).then(r=>r.json());
  if(r.success){btn.textContent=r.is_available?'숨김':'표시';
    const row=document.getElementById('mr-'+id);
    if(row)row.querySelector('td:first-child').style.opacity=r.is_available?'1':'.4';}
  else alert('❌ '+r.message);
}

// ─── 메뉴 수정 ───
let _editMenuId = null;
function openEditMenu(id, data) {
  _editMenuId = id;
  document.getElementById('em_name').value  = data.name    || '';
  document.getElementById('em_price').value = data.price   || '';
  document.getElementById('em_cat').value   = data.category|| '커피';
  document.getElementById('em_desc').value  = data.description || '';
  document.getElementById('editMenuModal').classList.add('show');
}
async function submitEditMenu() {
  if (!_editMenuId) return;
  const r = await fetch('/api/menus.php?id=' + _editMenuId, {
    method: 'PUT',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({
      name:        document.getElementById('em_name').value.trim(),
      price:       parseInt(document.getElementById('em_price').value),
      category:    document.getElementById('em_cat').value,
      description: document.getElementById('em_desc').value.trim(),
    })
  }).then(r => r.json());
  if (r.success) {
    alert('✅ ' + r.message);
    document.getElementById('editMenuModal').classList.remove('show');
    reloadPage();
  } else {
    alert('❌ ' + r.message);
  }
}

// ─── 사용자 추가 ───
document.getElementById('addUserForm')?.addEventListener('submit',async e=>{
  e.preventDefault();
  const r=await fetch('/api/users.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({username:document.getElementById('uId').value.trim(),
      name:document.getElementById('uName').value.trim(),
      password:document.getElementById('uPw').value,
      role:document.getElementById('uRole').value})}).then(r=>r.json());
  if(r.success){alert('✅ '+r.message);reloadPage();}else alert('❌ '+r.message);
});

// ─── 주문 상세 및 수정/취소 ───
let _currentOrderId = null;
let _currentOrderItems = [];

async function openOrderDetail(id) {
  _currentOrderId = id;
  const res = await fetch('/api/orders.php?id=' + id).then(r=>r.json());
  if(!res.success) { alert('❌ ' + res.message); return; }
  
  const o = res.data;
  document.getElementById('od_id').textContent = '#' + o.id;
  document.getElementById('od_time').textContent = o.created_at;
  document.getElementById('od_status').textContent = o.status;
  document.getElementById('od_status').className = 'stag st-' + o.status;
  document.getElementById('od_total').textContent = parseInt(o.total_price).toLocaleString();
  
  _currentOrderItems = o.items || [];
  renderOrderItems();
  
  document.getElementById('orderModal').classList.add('show');
}

function renderOrderItems() {
  const tbody = document.getElementById('od_items');
  tbody.innerHTML = '';
  _currentOrderItems.forEach((it, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="font-weight:600">${it.menu_name}</td>
      <td style="text-align:center">
        <input type="number" min="0" value="${it.quantity}" 
               onchange="updateItemQty(${idx}, this.value)" 
               style="width:50px;padding:4px;text-align:center;background:var(--sur2);color:#fff;border:1px solid var(--bdr);border-radius:6px;font-family:inherit;">
      </td>
      <td style="color:var(--ac);font-weight:700">${(it.price * it.quantity).toLocaleString()}원</td>
    `;
    tbody.appendChild(tr);
  });
}

function updateItemQty(idx, val) {
  let v = parseInt(val);
  if(isNaN(v) || v < 0) v = 0;
  _currentOrderItems[idx].quantity = v;
  
  let total = 0;
  _currentOrderItems.forEach(it => total += it.price * it.quantity);
  document.getElementById('od_total').textContent = total.toLocaleString();
  
  renderOrderItems();
}

async function saveOrderChanges() {
  if(!_currentOrderId) return;
  const res = await fetch('/api/orders.php', {
    method: 'PUT',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      id: _currentOrderId,
      items: _currentOrderItems
    })
  }).then(r=>r.json());
  if(res.success) { 
    alert('✅ 수정되었습니다.'); 
    reloadPage();
  }
  else { alert('❌ ' + res.message); }
}

async function cancelOrder() {
  if(!_currentOrderId) return;
  if(!confirm('정말로 이 주문을 취소하시겠습니까?')) return;
  const res = await fetch('/api/orders.php', {
    method: 'PUT',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      id: _currentOrderId,
      status: '취소'
    })
  }).then(r=>r.json());
  if(res.success) { 
    alert('✅ 취소 처리되었습니다.'); 
    reloadPage();
  }
  else { alert('❌ ' + res.message); }
}

async function openOrderLogs(id, e) {
  if (e) e.stopPropagation();
  const res = await fetch('/api/orders.php?action=logs&id=' + id).then(r=>r.json());
  if(!res.success) { alert('❌ ' + res.message); return; }
  
  document.getElementById('log_od_id').textContent = '#' + id;
  const tbody = document.getElementById('log_items');
  tbody.innerHTML = '';
  
  if (res.data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="4" class="empty">로그가 없습니다.</td></tr>';
  } else {
    res.data.forEach(log => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td style="font-size:0.75rem">${log.created_at}</td>
        <td><span class="stag" style="background:var(--sur2);border:1px solid var(--bdr)">${log.action}</span></td>
        <td style="font-size:0.8rem">${log.details}</td>
        <td style="font-size:0.8rem;color:var(--muted);font-weight:600">${log.username || log.ip_address}</td>
      `;
      tbody.appendChild(tr);
    });
  }
  
  document.getElementById('logModal').classList.add('show');
}

// ─── 사용자 삭제 ───
async function deleteUser(id){
  if(!confirm('이 사용자를 삭제하시겠습니까?'))return;
  const r=await fetch('/api/users.php?id='+id,{method:'DELETE'}).then(r=>r.json());
  if(r.success)document.getElementById('ur-'+id)?.remove();else alert('❌ '+r.message);
}

// ─── 사용자 활성/비활성 ───
async function toggleUser(id,btn){
  const r=await fetch('/api/users.php?id='+id+'&action=toggle',{method:'PATCH'}).then(r=>r.json());
  if(r.success){btn.textContent=r.is_active?'비활성':'활성';
    const row=document.getElementById('ur-'+id);
    const st=row?.querySelector('td:nth-child(4) span');
    if(st){st.textContent=r.is_active?'활성':'비활성';st.style.color=r.is_active?'var(--green)':'var(--muted)';}
  }else alert('❌ '+r.message);
}

// ─── 비밀번호 변경 모달 ───
let _pwUserId=null;
function openPwModal(id,uname){
  _pwUserId=id;
  document.getElementById('pwTarget').textContent=uname;
  document.getElementById('newPw').value='';
  document.getElementById('pwModal').classList.add('show');
  document.getElementById('newPw').focus();
}
async function submitPwChange(){
  const pw=document.getElementById('newPw').value;
  if(pw.length<4){alert('4자 이상 입력해주세요.');return;}
  const r=await fetch('/api/users.php?id='+_pwUserId+'&action=password',{method:'PATCH',
    headers:{'Content-Type':'application/json'},body:JSON.stringify({password:pw})}).then(r=>r.json());
  if(r.success){alert('✅ '+r.message);document.getElementById('pwModal').classList.remove('show');}
  else alert('❌ '+r.message);
}

// ─── 결제 게이트웨이 목록 로드 ───
let _gwData = [];
let _gwEditing = null;
const GW_ICONS = {keyboard_wedge:'🖱',toss:'💙',nice:'🟢',inicis:'🟡',kcp:'🔵',kakao:'💛'};
const GW_DESC  = {
  keyboard_wedge:'USB 카드 리더기 직접 입력 (API 키 불필요)',
  toss:'토스페이먼츠 JS SDK — 가장 간편한 온라인 결제',
  nice:'NICE페이먼츠 — 대형 VAN사, 오프라인/온라인 모두 지원',
  inicis:'KG이니시스 — 국내 최대 PG사 중 하나',
  kcp:'NHN KCP — 카드사 직접 계약 기반 결제',
  kakao:'카카오페이 — QR/앱 결제',
};

async function loadGateways(){
  const res = await fetch('/api/gateways.php').then(r=>r.json());
  if(!res.success){document.getElementById('gwList').textContent='로드 실패';return;}
  _gwData = res.data;
  renderGateways();
}

function renderGateways(){
  const el = document.getElementById('gwList');
  el.innerHTML = _gwData.map(gw=>`
    <div style="background:var(--sur2);border:1px solid ${gw.is_active?'var(--ac)':'var(--bdr)'};border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
      <div style="font-size:1.8rem">${GW_ICONS[gw.code]||'💳'}</div>
      <div style="flex:1;min-width:160px">
        <div style="font-weight:800;font-size:.95rem">${gw.name}</div>
        <div style="font-size:.78rem;color:var(--muted);margin-top:3px">${GW_DESC[gw.code]||''}</div>
        <div style="font-size:.75rem;margin-top:5px;color:${gw.mode==='live'?'var(--green)':'var(--amber)'}">
          ${gw.mode==='live'?'🚀 라이브':'🧪 테스트'} 모드
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center">
        ${gw.config_fields.length?`<button class="abtn abtn-pw" onclick="openGwModal(${JSON.stringify(gw).split('"').join('&quot;')})">⚙ 설정</button>`:''}
        ${gw.is_active
          ? `<span style="background:rgba(108,99,255,.2);border:1px solid var(--ac);color:var(--ac);padding:5px 14px;border-radius:8px;font-size:.78rem;font-weight:700">✓ 사용중</span>`
          : `<button class="abtn abtn-tog" onclick="activateGw('${gw.code}')">활성화</button>`}
      </div>
    </div>`).join('');
}

function openGwModal(gw){
  if(typeof gw === 'string') gw = JSON.parse(gw.replace(/&quot;/g,'"'));
  _gwEditing = gw;
  document.getElementById('gwModalTitle').textContent = GW_ICONS[gw.code]+' '+gw.name+' 설정';
  document.getElementById('gwModalMode').value = gw.mode || 'test';
  const fields = gw.config_fields||[];
  document.getElementById('gwModalFields').innerHTML = fields.map(f=>`
    <div class="fg">
      <label>${f.label}</label>
      <input type="${f.type||'text'}" id="gwf_${f.key}" value="${(gw.config||{})[f.key]||''}" placeholder="${f.required?'필수':'선택'}" autocomplete="off">
    </div>`).join('');
  document.getElementById('gwModal').classList.add('show');
}

async function saveGwConfig(){
  if(!_gwEditing) return;
  const config={};
  (_gwEditing.config_fields||[]).forEach(f=>{
    const el=document.getElementById('gwf_'+f.key);
    if(el) config[f.key]=el.value;
  });
  const mode=document.getElementById('gwModalMode').value;
  const r=await fetch('/api/gateways.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'save_config',code:_gwEditing.code,config,mode})}).then(r=>r.json());
  if(r.success){alert('✅ '+r.message);document.getElementById('gwModal').classList.remove('show');loadGateways();}
  else alert('❌ '+r.message);
}

async function activateGw(code){
  const gw = _gwData.find(g=>g.code===code);
  if(!confirm(`[${gw?.name||code}] 게이트웨이를 활성화하시겠습니까?\n기존 활성 게이트웨이는 비활성화됩니다.`)) return;
  const r=await fetch('/api/gateways.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({action:'activate',code})}).then(r=>r.json());
  if(r.success){loadGateways();}else alert('❌ '+r.message);
}

// ─── 날짜 검색 ───
function setDateRange(range) {
  const today = new Date();
  let sd = new Date(), ed = new Date();
  
  if (range === 'today') {
    // defaults
  } else if (range === 'yesterday') {
    sd.setDate(today.getDate() - 1);
    ed.setDate(today.getDate() - 1);
  } else if (range === 'week') {
    sd.setDate(today.getDate() - 7);
  } else if (range === 'thisMonth') {
    sd = new Date(today.getFullYear(), today.getMonth(), 1);
  } else if (range === 'lastMonth') {
    sd = new Date(today.getFullYear(), today.getMonth() - 1, 1);
    ed = new Date(today.getFullYear(), today.getMonth(), 0);
  }
  
  // Date formatting for input type="date"
  const offset = sd.getTimezoneOffset() * 60000;
  document.querySelector('input[name="sd"]').value = new Date(sd.getTime() - offset).toISOString().split('T')[0];
  document.querySelector('input[name="ed"]').value = new Date(ed.getTime() - offset).toISOString().split('T')[0];
  document.querySelector('input[name="sd"]').closest('form').submit();
}

// ─── URL 기반 초기 탭 설정 ───
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('tab')) {
    switchTab(urlParams.get('tab'));
  }
});
</script>
</body>
</html>
