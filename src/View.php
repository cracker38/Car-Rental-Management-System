<?php
declare(strict_types=1);

namespace Crms;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): void
    {
        $data['flashes'] = Http::flashes();
        $data['user'] = Auth::user();
        $data['title'] = $data['title'] ?? 'CRMS';
        $layout = $layout ?? self::layoutFor($template, $data);
        if ($layout === 'app' && ($data['user']['role_name'] ?? '') === 'System Administrator'
            && !array_key_exists('unreadNotificationCount', $data)) {
            try {
                $data['unreadNotificationCount'] = (new Services\NotificationService())->unreadCount(Auth::id(), Auth::customerId());
            } catch (\Throwable) {
                $data['unreadNotificationCount'] = 0;
            }
        }
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . '/views/' . $template . '.php';
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found.';
            return;
        }
        ob_start();
        require $viewFile;
        $content = ob_get_clean();
        $layoutFile = dirname(__DIR__) . '/views/layouts/' . $layout . '.php';
        require $layoutFile;
    }

    private static function layoutFor(string $template, array $data): string
    {
        if (!empty($data['fullBleed'])) {
            return 'site';
        }
        $marketing = [
            'home/index', 'home/search', 'home/fleet', 'home/about', 'home/services',
            'home/blog', 'home/contact', 'auth/login', 'auth/register',
            'errors/404', 'errors/403', 'payments/receipt',
        ];
        if (in_array($template, $marketing, true) || !Auth::user()) {
            return 'site';
        }
        return 'app';
    }
}
