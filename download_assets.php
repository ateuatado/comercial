<?php

$assets = [
    // Bootstrap
    'public/assets/css/bootstrap.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'public/assets/js/bootstrap.bundle.min.js' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',

    // Bootstrap Icons
    'public/assets/css/bootstrap-icons.min.css' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
    'public/assets/css/fonts/bootstrap-icons.woff2' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff2',
    'public/assets/css/fonts/bootstrap-icons.woff' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/fonts/bootstrap-icons.woff',

    // Chart.js
    'public/assets/js/chart.umd.min.js' => 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js',

    // Leaflet
    'public/assets/css/leaflet.css' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'public/assets/js/leaflet.js' => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'public/assets/css/images/marker-icon.png' => 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    'public/assets/css/images/marker-icon-2x.png' => 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    'public/assets/css/images/marker-shadow.png' => 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',

    // TinyMCE Core
    'public/assets/js/tinymce/tinymce.min.js' => 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js',
    'public/assets/js/tinymce/themes/silver/theme.min.js' => 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/themes/silver/theme.min.js',
    'public/assets/js/tinymce/icons/default/icons.min.js' => 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/icons/default/icons.min.js',
    'public/assets/js/tinymce/models/dom/model.min.js' => 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/models/dom/model.min.js',
    'public/assets/js/tinymce/skins/ui/oxide/skin.min.css' => 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/skins/ui/oxide/skin.min.css',
    'public/assets/js/tinymce/skins/ui/oxide/content.min.css' => 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/skins/ui/oxide/content.min.css',
    'public/assets/js/tinymce/skins/content/default/content.min.css' => 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/skins/content/default/content.min.css',

    // Inter Fonts
    'public/assets/css/fonts/inter-400.woff2' => 'https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-400-normal.woff2',
    'public/assets/css/fonts/inter-500.woff2' => 'https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-500-normal.woff2',
    'public/assets/css/fonts/inter-600.woff2' => 'https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-600-normal.woff2',
    'public/assets/css/fonts/inter-700.woff2' => 'https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-700-normal.woff2',
    'public/assets/css/fonts/inter-800.woff2' => 'https://cdn.jsdelivr.net/fontsource/fonts/inter@latest/latin-800-normal.woff2',
];

echo "Iniciando download dos assets...\n";

foreach ($assets as $dest => $url) {
    $dir = dirname($dest);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    
    echo "Baixando: $url => $dest ... ";
    $content = @file_get_contents($url);
    if ($content !== false) {
        file_put_contents($dest, $content);
        echo "OK\n";
    } else {
        echo "FALHOU\n";
    }
}

// Criar o arquivo inter.css local
$interCss = <<<CSS
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url('fonts/inter-400.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 500;
  font-display: swap;
  src: url('fonts/inter-500.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 600;
  font-display: swap;
  src: url('fonts/inter-600.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url('fonts/inter-700.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style: normal;
  font-weight: 800;
  font-display: swap;
  src: url('fonts/inter-800.woff2') format('woff2');
}
CSS;

file_put_contents('public/assets/css/inter.css', $interCss);
echo "Arquivo public/assets/css/inter.css criado com sucesso!\n";
echo "Downloads concluídos.\n";
