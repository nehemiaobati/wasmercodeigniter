<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as $url): ?>
    <url>
    <loc><?= esc($url['loc'], 'html') ?></loc>
    <lastmod><?= esc($url['lastmod'], 'html') ?></lastmod>
    <changefreq><?= esc($url['changefreq'], 'html') ?></changefreq>
    <priority><?= esc($url['priority'], 'html') ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
