<?php
// table_tent.php - Dynamic Table Tent Generator for Rock Ready
date_default_timezone_set('America/Los_Angeles');

// Theme mode toggle via GET parameter: ?mode=light or ?mode=dark (defaults to light)
$mode = isset($_GET['mode']) && strtolower($_GET['mode']) === 'dark' ? 'dark' : 'light';

$today_str = date('Y-m-d H:i:s');
$events = [];

// 1. Fetch upcoming events via The Events Calendar REST API
$api_url = 'https://rockready.band/wp-json/tribe/events/v1/events?per_page=20&start_date=' . urlencode($today_str);

$context = stream_context_create([
    'http' => [
        'method'     => 'GET',
        'user_agent' => 'Mozilla/5.0 (compatible; RockReadyTent/1.0)',
        'timeout'    => 6,
    ],
    'ssl' => [
        'verify_peer'      => true,
        'verify_peer_name' => true,
    ]
]);

$json_data = @file_get_contents($api_url, false, $context);

if ($json_data) {
    $decoded = json_decode($json_data, true);
    if (!empty($decoded['events'])) {
        foreach ($decoded['events'] as $ev) {
            $venue_title = !empty($ev['venue']['venue']) ? $ev['venue']['venue'] : '';
            $city        = !empty($ev['venue']['city']) ? $ev['venue']['city'] : '';
            $venue_str   = trim($venue_title . ($city ? " • {$city}" : ''));

            $start_dt = new DateTime($ev['start_date']);
            $end_dt   = !empty($ev['end_date']) ? new DateTime($ev['end_date']) : null;

            $time_str = $start_dt->format('g:i a');
            if ($end_dt) {
                $time_str .= ' – ' . $end_dt->format('g:i a');
            }

            $events[] = [
                'title'      => html_entity_decode($ev['title'], ENT_QUOTES, 'UTF-8'),
                'start_dt'   => $start_dt,
                'date_key'   => $start_dt->format('Y-m-d'),
                'day_num'    => (int)$start_dt->format('j'),
                'month_key'  => $start_dt->format('Y-m'),
                'date_label' => $start_dt->format('D, M j'),
                'time_label' => $time_str,
                'short_time' => $start_dt->format('g') . ($end_dt ? '-' . $end_dt->format('ga') : 'a'),
                'venue'      => $venue_str,
                'short_loc'  => $venue_title ?: 'Show',
            ];
        }
    }
}

// Fallback: Parse HTML if API response is unavailable
if (empty($events)) {
    $html = @file_get_contents('https://rockready.band/events/list/', false, $context);
    if ($html) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $articles = $xpath->query("//article[contains(@class, 'tribe-events-calendar-list__event')]");

        foreach ($articles as $art) {
            $tNode = $xpath->query(".//h3[contains(@class, 'tribe-events-calendar-list__event-title')]//a", $art)->item(0);
            $dNode = $xpath->query(".//time[contains(@class, 'tribe-events-calendar-list__event-datetime')]", $art)->item(0);
            $vNode = $xpath->query(".//span[contains(@class, 'tribe-events-calendar-list__event-venue-title')]", $art)->item(0);

            if ($tNode && $dNode) {
                $dt_attr = $dNode->getAttribute('datetime');
                $start_dt = new DateTime($dt_attr ?: 'now');
                $vText = $vNode ? trim($vNode->textContent) : '';

                $events[] = [
                    'title'      => trim($tNode->textContent),
                    'start_dt'   => $start_dt,
                    'date_key'   => $start_dt->format('Y-m-d'),
                    'day_num'    => (int)$start_dt->format('j'),
                    'month_key'  => $start_dt->format('Y-m'),
                    'date_label' => $start_dt->format('D, M j'),
                    'time_label' => trim($dNode->textContent),
                    'short_time' => $start_dt->format('g-ia'),
                    'venue'      => $vText,
                    'short_loc'  => $vText ?: 'Show',
                ];
            }
        }
    }
}

// Slice out the first 3 shows
$upcoming_three = array_slice($events, 0, 3);

// Calendar Setup for Current Month
$year          = (int)date('Y');
$month         = (int)date('n');
$month_title   = date('F Y');
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_dow     = (int)date('w', strtotime("{$year}-{$month}-01")); // 0 = Sunday

// Map events by day number for calendar cells
$cur_month_key = date('Y-m');
$cal_events = [];
foreach ($events as $ev) {
    if ($ev['month_key'] === $cur_month_key) {
        $cal_events[$ev['day_num']][] = $ev;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Rock Ready - Table Tent (<?= ucfirst($mode) ?>)</title>
  <style>
    @page {
      size: letter landscape;
      margin: 0;
    }
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    /* THEME VARIABLES */
    body.theme-dark {
      --bg-page: #111;
      --bg-panel: radial-gradient(circle at center, #1e1e1e 0%, #0d0d0d 100%);
      --text-main: #eee;
      --text-sub: #aaa;
      --heading-color: #ff5500;
      --border-accent: #e53935;
      --border-line: #333;
      --fold-line: #444;
      --card-bg: rgba(255, 255, 255, 0.05);
      --card-border: #ff9800;
      --card-title: #fff;
      --card-time: #ffcc00;
      --cal-th: #bbb;
      --cal-border: #282828;
      --cal-cell-bg: rgba(255, 255, 255, 0.02);
      --cal-gig-bg: rgba(229, 57, 53, 0.28);
      --cal-gig-border: #ff5500;
      --cal-gig-tag: #ffcc00;
      --qr-border: #fff;
    }

    body.theme-light {
      --bg-page: #fff;
      --bg-panel: #ffffff;
      --text-main: #1a1a1a;
      --text-sub: #555;
      --heading-color: #c62828;
      --border-accent: #d32f2f;
      --border-line: #ccc;
      --fold-line: #bbb;
      --card-bg: #f9f9f9;
      --card-border: #c62828;
      --card-title: #111;
      --card-time: #d84315;
      --cal-th: #444;
      --cal-border: #ddd;
      --cal-cell-bg: #fafafa;
      --cal-gig-bg: #ffebee;
      --cal-gig-border: #d32f2f;
      --cal-gig-tag: #b71c1c;
      --qr-border: #000;
    }

    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg-page);
      color: var(--text-main);
    }

    .sheet {
      width: 11in;
      height: 8.5in;
      display: flex;
      flex-direction: column;
      position: relative;
      background: var(--bg-page);
      overflow: hidden;
    }

    .fold-line {
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      border-top: 1px dashed var(--fold-line);
      z-index: 100;
    }

    .panel {
      width: 100%;
      height: 4.25in;
      padding: 0.22in 0.4in;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      background: var(--bg-panel);
    }

    .panel-top {
      transform: rotate(180deg);
    }

    /* Side 1: 3-column layout (Logo - Shows - Cartoon) */
    .showcase-content {
      display: flex;
      gap: 1rem;
      align-items: center;
      justify-content: space-between;
      height: 2.75in;
    }

    .side-img {
      height: 2.55in;
      width: 2.5in;
      object-fit: contain;
    }

    .gig-list-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      min-width: 0;
    }

    .panel-title {
      font-size: 1.18rem;
      font-weight: 900;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: var(--heading-color);
      border-bottom: 2px solid var(--border-accent);
      padding-bottom: 3px;
      margin: 0 0 6px 0;
    }

    .gig-item {
      margin-bottom: 5px;
      padding: 4px 7px;
      background: var(--card-bg);
      border-left: 3px solid var(--card-border);
      border-radius: 0 4px 4px 0;
    }

    .gig-title {
      font-size: 0.88rem;
      font-weight: bold;
      color: var(--card-title);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .gig-meta {
      font-size: 0.74rem;
      color: var(--card-time);
      font-weight: 600;
    }

    .gig-location {
      font-size: 0.70rem;
      color: var(--text-sub);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Side 2: Calendar */
    .calendar-container {
      height: 2.75in;
      display: flex;
      flex-direction: column;
    }

    .cal-header-bar {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      border-bottom: 2px solid var(--border-accent);
      padding-bottom: 3px;
      margin-bottom: 4px;
    }

    .calendar-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    .calendar-table th {
      color: var(--cal-th);
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      padding: 1px 0;
      text-align: center;
    }

    .calendar-table td {
      height: 0.38in;
      vertical-align: top;
      text-align: right;
      padding: 2px 4px;
      font-size: 0.72rem;
      border: 1px solid var(--cal-border);
      background: var(--cal-cell-bg);
      color: var(--text-main);
    }

    .calendar-table td.empty {
      border: none;
      background: transparent;
    }

    .calendar-table td.gig-day {
      background: var(--cal-gig-bg);
      border: 1.5px solid var(--cal-gig-border);
      font-weight: bold;
      color: var(--text-main);
    }

    .gig-tag {
      display: block;
      font-size: 0.58rem;
      line-height: 1.1;
      text-align: left;
      color: var(--cal-gig-tag);
      font-weight: bold;
      margin-top: 1px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Shared Footer */
    .footer-bar {
      height: 1.05in;
      border-top: 1px solid var(--border-line);
      padding-top: 5px;
      display: flex;
      justify-content: space-around;
      align-items: center;
    }

    .qr-group {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .qr-img {
      width: 0.85in;
      height: 0.85in;
      background: #fff;
      padding: 2px;
      border-radius: 4px;
      border: 1px solid var(--border-line);
    }

    .qr-label {
      font-weight: 800;
      font-size: 0.95rem;
      color: var(--heading-color);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .qr-subtext {
      font-size: 0.75rem;
      color: var(--text-sub);
    }
  </style>
</head>
<body class="theme-<?= htmlspecialchars($mode) ?>">

  <div class="sheet">
    <div class="fold-line"></div>

    <!-- PANEL TOP: SHOW CALENDAR (Rotated for tent fold) -->
    <section class="panel panel-top">
      <div class="calendar-container">
        <div class="cal-header-bar">
          <h2 class="panel-title" style="margin-bottom:0; border:none;"><?= htmlspecialchars($month_title) ?> Show Calendar</h2>
          <span style="font-size: 0.75rem; color: var(--card-time); font-weight: 600;">rockready.band/events</span>
        </div>

        <table class="calendar-table">
          <thead>
            <tr>
              <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
            </tr>
          </thead>
          <tbody>
            <tr>
            <?php
            $cell_count = 0;

            for ($i = 0; $i < $first_dow; $i++) {
                echo '<td class="empty"></td>';
                $cell_count++;
            }

            for ($d = 1; $d <= $days_in_month; $d++) {
                $has_gig = isset($cal_events[$d]);
                $td_class = $has_gig ? 'gig-day' : '';

                echo "<td class='{$td_class}'>{$d}";
                if ($has_gig) {
                    foreach ($cal_events[$d] as $show) {
                        $loc = htmlspecialchars($show['short_loc']);
                        echo "<span class='gig-tag'>{$loc}</span>";
                    }
                }
                echo "</td>";
                $cell_count++;

                if ($cell_count % 7 === 0 && $d !== $days_in_month) {
                    echo "</tr><tr>";
                }
            }

            while ($cell_count % 7 !== 0) {
                echo '<td class="empty"></td>';
                $cell_count++;
            }
            ?>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Bar -->
      <footer class="footer-bar">
        <div class="qr-group">
          <img src="venmo_qr.png" alt="Venmo QR" class="qr-img">
          <div class="qr-text">
            <div class="qr-label">Venmo the Band</div>
            <div class="qr-subtext">Tips</div>
          </div>
        </div>
        <div class="qr-group">
          <img src="qrcode_rockready.band.png" alt="Rock Ready QR" class="qr-img">
          <div class="qr-text">
            <div class="qr-label">Rock Ready Band</div>
            <div class="qr-subtext">rockready.band</div>
          </div>
        </div>
      </footer>
    </section>

    <!-- PANEL BOTTOM: LOGO + UPCOMING SHOWS + CARTOON -->
    <section class="panel">
      <div class="showcase-content">
        <img src="RockReady.jpg" alt="Rock Ready Band" class="side-img">

        <div class="gig-list-container">
          <h2 class="panel-title">Upcoming Shows</h2>

          <?php if (!empty($upcoming_three)): ?>
            <?php foreach ($upcoming_three as $gig): ?>
              <div class="gig-item">
                <div class="gig-title"><?= htmlspecialchars($gig['title']) ?></div>
                <div class="gig-meta"><?= htmlspecialchars($gig['date_label']) ?> &bull; <?= htmlspecialchars($gig['time_label']) ?></div>
                <?php if (!empty($gig['venue'])): ?>
                  <div class="gig-location"><?= htmlspecialchars($gig['venue']) ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="gig-item">
              <div class="gig-title">Check Back Soon!</div>
              <div class="gig-meta">New dates loading at rockready.band</div>
            </div>
          <?php endif; ?>
        </div>

        <img src="RockReady_Cartoon.jpg" alt="Rock Ready Band Cartoon" class="side-img">
      </div>

      <!-- Footer Bar -->
      <footer class="footer-bar">
        <div class="qr-group">
          <img src="venmo_qr.png" alt="Venmo QR" class="qr-img">
          <div class="qr-text">
            <div class="qr-label">Venmo the Band</div>
            <div class="qr-subtext">Tips</div>
          </div>
        </div>
        <div class="qr-group">
          <img src="qrcode_rockready.band.png" alt="Rock Ready QR" class="qr-img">
          <div class="qr-text">
            <div class="qr-label">Rock Ready Band</div>
            <div class="qr-subtext">rockready.band</div>
          </div>
        </div>
      </footer>
    </section>

  </div>

</body>
</html>
