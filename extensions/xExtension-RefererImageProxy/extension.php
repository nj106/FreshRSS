<?php
declare(strict_types=1);

/**
 * Rewrites image URLs from hotlink-protected hosts to the local Referer proxy.
 * Keep HOSTS in sync with the allow-list map in freshrss-imgproxy (nj106/FreshRSS docker-compose.yml).
 */
final class RefererImageProxyExtension extends Minz_Extension {
	private const HOSTS = ['cdnfile.sspai.com', 'img.hellogithub.com', 'tu.djhdhs.us'];
	private const PREFIX = 'https://freshrss.njcq.dpdns.org/imgp/';

	#[\Override]
	public function init(): void {
		$this->registerHook('entry_before_display', [self::class, 'rewriteEntry']);
	}

	public static function rewrite(string $s): string {
		$hosts = implode('|', array_map(static fn(string $h): string => preg_quote($h, '#'), self::HOSTS));
		return preg_replace_callback(
			'#(?:(https?):)?//(' . $hosts . ')(/[^\s"\'<>)]*)#i',
			static fn(array $m): string => self::PREFIX . (($m[1] ?? '') !== '' ? strtolower($m[1]) : 'https') . '/' . strtolower($m[2]) . $m[3],
			$s
		) ?? $s;
	}

	public static function rewriteEntry(FreshRSS_Entry $entry): FreshRSS_Entry {
		$entry->_content(self::rewrite($entry->content(false)));
		$thumb = $entry->attributeArray('thumbnail');
		if (is_array($thumb) && is_string($thumb['url'] ?? null)) {
			$thumb['url'] = self::rewrite($thumb['url']);
			$entry->_attribute('thumbnail', $thumb);
		}
		return $entry;
	}
}
