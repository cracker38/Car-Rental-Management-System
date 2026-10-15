<?php
declare(strict_types=1);

namespace Crms;

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'site'): void
    {
        $data['flashes'] = Http::flashes();
        $data['user'] = Auth::user();
        $data['title'] = $data['title'] ?? 'CRMS';
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
}
