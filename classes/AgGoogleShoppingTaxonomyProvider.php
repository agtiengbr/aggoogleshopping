<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

final class AgGoogleShoppingTaxonomyProvider
{
    /** @var array<string, array<int, string>> */
    private static $cacheByLocale = [];

    public function resolveLocale(string $isoCode): string
    {
        $iso = Tools::strtolower(trim($isoCode));

        if ($iso === 'pt' || strpos($iso, 'pt-') === 0 || strpos($iso, 'pt_') === 0) {
            return 'pt-BR';
        }

        return 'en-US';
    }

    public function getFilePath(string $locale): string
    {
        $moduleDir = _PS_MODULE_DIR_ . 'aggoogleshopping/data/';
        $file = $moduleDir . 'taxonomy-with-ids.' . $locale . '.txt';

        if (!is_readable($file)) {
            $file = $moduleDir . 'taxonomy-with-ids.en-US.txt';
        }

        return $file;
    }

    /**
     * @return array<int, string>
     */
    private function load(string $locale): array
    {
        if (isset(self::$cacheByLocale[$locale])) {
            return self::$cacheByLocale[$locale];
        }

        $map = [];
        $file = $this->getFilePath($locale);

        if (!is_readable($file)) {
            self::$cacheByLocale[$locale] = $map;

            return $map;
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            self::$cacheByLocale[$locale] = $map;

            return $map;
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (!preg_match('/^(\d+)\s+-\s+(.+)$/u', $line, $matches)) {
                continue;
            }

            $map[(int) $matches[1]] = $matches[2];
        }

        fclose($handle);

        self::$cacheByLocale[$locale] = $map;

        return $map;
    }

    public function getPathById(int $id, string $isoCode): ?string
    {
        if ($id <= 0) {
            return null;
        }

        $map = $this->load($this->resolveLocale($isoCode));

        return $map[$id] ?? null;
    }

    public function getLabelById(int $id, string $isoCode): string
    {
        $path = $this->getPathById($id, $isoCode);
        if ($path === null) {
            return '';
        }

        return $id . ' - ' . $path;
    }

    public function isValidId(int $id, string $isoCode): bool
    {
        return $this->getPathById($id, $isoCode) !== null;
    }

    /**
     * @return array<int, array{id: string, path: string, label: string}>
     */
    public function search(string $query, string $isoCode, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '' || $limit <= 0) {
            return [];
        }

        $map = $this->load($this->resolveLocale($isoCode));
        $normalizedQuery = $this->normalizeForSearch($query);
        $matches = [];

        foreach ($map as $id => $path) {
            $idString = (string) $id;
            $label = $idString . ' - ' . $path;
            $score = $this->scoreMatch($normalizedQuery, $query, $idString, $path, $label);

            if ($score <= 0) {
                continue;
            }

            $matches[] = [
                'id' => $idString,
                'path' => $path,
                'label' => $label,
                'score' => $score,
            ];
        }

        usort($matches, static function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                return strcmp($left['path'], $right['path']);
            }

            return $right['score'] <=> $left['score'];
        });

        $results = [];
        foreach (array_slice($matches, 0, $limit) as $match) {
            unset($match['score']);
            $results[] = $match;
        }

        return $results;
    }

    private function normalizeForSearch(string $value): string
    {
        $value = Tools::strtolower(trim($value));

        if (method_exists('Tools', 'replaceAccentedChars')) {
            return Tools::strtolower(Tools::replaceAccentedChars($value));
        }

        if (class_exists('Transliterator', false)) {
            $transliterator = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');
            if ($transliterator !== null) {
                return $transliterator->transliterate($value);
            }
        }

        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return is_string($converted) && $converted !== '' ? $converted : $value;
    }

    private function scoreMatch(
        string $normalizedQuery,
        string $rawQuery,
        string $idString,
        string $path,
        string $label
    ): int {
        if ($normalizedQuery === '') {
            return 0;
        }

        if (strpos($idString, $rawQuery) === 0) {
            return 120;
        }

        $normalizedPath = $this->normalizeForSearch($path);
        $normalizedLabel = $this->normalizeForSearch($label);
        $bestScore = 0;

        if ($normalizedPath === $normalizedQuery) {
            $bestScore = max($bestScore, 110);
        }

        foreach (explode(' > ', $path) as $segment) {
            $normalizedSegment = $this->normalizeForSearch($segment);

            if ($normalizedSegment === $normalizedQuery) {
                $bestScore = max($bestScore, 100);
            }

            if (strpos($normalizedSegment, $normalizedQuery) === 0) {
                $bestScore = max($bestScore, 90);
            }

            if (preg_match('/\b' . preg_quote($normalizedQuery, '/') . '/u', $normalizedSegment)) {
                $bestScore = max($bestScore, 70);
            }
        }

        if (strpos($normalizedPath, $normalizedQuery) !== false) {
            $bestScore = max($bestScore, 50 - (int) (strlen($path) / 20));
        }

        if ($bestScore > 0 && strpos($normalizedLabel, $normalizedQuery) === false) {
            $bestScore = max(1, $bestScore - 10);
        }

        return $bestScore;
    }
}
