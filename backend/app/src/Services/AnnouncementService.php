<?php
declare(strict_types=1);

namespace Zika\Services;

use Zika\Core\Db;

/** Thông báo hiện cho người học (dải trên cùng / hộp nổi) — 04 §3, 07 §1.2. */
final class AnnouncementService
{
    /**
     * Thông báo đang hiện, đúng đối tượng, người này chưa đóng. Hộp nổi chỉ hiện 1 lần (chưa xem).
     * @param array<string,mixed> $user
     * @return list<array<string,mixed>>
     */
    public static function activeFor(array $user): array
    {
        $rows = Db::all(
            "SELECT a.id, a.kind, a.title, a.body, a.cta_text, a.cta_route, r.seen_at, r.dismissed_at
             FROM announcements a
             LEFT JOIN announcement_reads r ON r.announcement_id = a.id AND r.user_id = ?
             WHERE a.status = 'published' AND a.kind IN ('banner','modal') AND a.is_auto_welcome = 0
               AND (a.starts_at IS NULL OR a.starts_at <= NOW()) AND (a.ends_at IS NULL OR a.ends_at > NOW())
               AND (a.audience = 'all'
                    OR (a.audience = 'level' AND a.audience_level = ?)
                    OR (a.audience = 'new7d' AND ? >= NOW() - INTERVAL 7 DAY)
                    OR (a.audience = 'user' AND a.target_user_id = ?))
             ORDER BY a.starts_at DESC, a.id DESC",
            [(int) $user['id'], $user['current_level_id'] === null ? null : (int) $user['current_level_id'], (string) $user['created_at'], (int) $user['id']],
        );
        $out = [];
        foreach ($rows as $a) {
            if ($a['dismissed_at'] !== null || ($a['kind'] === 'modal' && $a['seen_at'] !== null)) {
                continue;
            }
            $out[] = [
                'id' => (int) $a['id'],
                'kind' => (string) $a['kind'],
                'title' => (string) $a['title'],
                'body' => (string) $a['body'],
                'ctaText' => $a['cta_text'],
                'ctaRoute' => $a['cta_route'],
            ];
        }
        return $out;
    }

    /** Ghi đã xem / đã đóng / đã bấm (mỗi người 1 dòng). Lần đầu xem/bấm mới tăng bộ đếm. */
    public static function mark(int $announcementId, int $userId, string $what): void
    {
        $exists = Db::one('SELECT seen_at, clicked_at FROM announcement_reads WHERE announcement_id = ? AND user_id = ?', [$announcementId, $userId]);
        if ($exists === null) {
            Db::exec('INSERT INTO announcement_reads (announcement_id, user_id, seen_at) VALUES (?, ?, NOW())', [$announcementId, $userId]);
            Db::exec('UPDATE announcements SET views = views + 1 WHERE id = ?', [$announcementId]);
        }
        if ($what === 'dismiss') {
            Db::exec('UPDATE announcement_reads SET dismissed_at = NOW() WHERE announcement_id = ? AND user_id = ?', [$announcementId, $userId]);
        } elseif ($what === 'click' && ($exists['clicked_at'] ?? null) === null) {
            Db::exec('UPDATE announcement_reads SET clicked_at = NOW() WHERE announcement_id = ? AND user_id = ?', [$announcementId, $userId]);
            Db::exec('UPDATE announcements SET clicks = clicks + 1 WHERE id = ?', [$announcementId]);
        }
    }
}
