<?php

namespace App\Libraries;

use PharData;
use RuntimeException;
use Throwable;

/**
 * Lecteur .xlsx minimaliste (sans dépendance externe).
 * Utilise PharData pour désarchiver et des expressions régulières pour parser,
 * car les extensions zip/xml ne sont pas disponibles sur cet environnement.
 */
class XlsxReader
{
    private array $sharedStrings = [];

    /**
     * @return array[] Liste de feuilles : [['name' => ..., 'rows' => [colonneLettre => valeur, ...]], ...]
     */
    public function read(string $path): array
    {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xlsx') {
            $tmp = sys_get_temp_dir() . '/xlsx_import_' . uniqid() . '.xlsx';
            if (! @copy($path, $tmp)) {
                throw new RuntimeException('Impossible de préparer le fichier pour lecture.');
            }
            try {
                return $this->doRead($tmp);
            } finally {
                @unlink($tmp);
            }
        }

        return $this->doRead($path);
    }

    /**
     * @return array[]
     */
    private function doRead(string $path): array
    {
        $zip = new PharData($path);

        $workbookXml = $zip['xl/workbook.xml']->getContent();

        if (isset($zip['xl/sharedStrings.xml'])) {
            $this->loadSharedStrings($zip['xl/sharedStrings.xml']->getContent());
        }

        $relTargets = [];
        if (isset($zip['xl/_rels/workbook.xml.rels'])) {
            $relsXml = $zip['xl/_rels/workbook.xml.rels']->getContent();
            if (preg_match_all('/<Relationship\b[^>]*>/s', $relsXml, $tags)) {
                foreach ($tags[0] as $tag) {
                    preg_match('/\bId="([^"]+)"/', $tag, $idM);
                    preg_match('/\bTarget="([^"]+)"/', $tag, $tgM);
                    if ($idM && $tgM) {
                        $relTargets[$idM[1]] = $tgM[1];
                    }
                }
            }
        }

        $sheets = [];
        if (preg_match_all('/<sheet\b[^>]*>/s', $workbookXml, $sheetTags)) {
            foreach ($sheetTags[0] as $tag) {
                preg_match('/\bname="([^"]*)"/', $tag, $nM);
                preg_match('/\br:id="([^"]+)"/', $tag, $rM);
                if (! $nM || ! $rM || ! isset($relTargets[$rM[1]])) {
                    continue;
                }

                $target = ltrim($relTargets[$rM[1]], '/');
                if (! str_starts_with($target, 'xl/')) {
                    $target = 'xl/' . $target;
                }
                if (! isset($zip[$target])) {
                    continue;
                }

                try {
                    $rows = $this->parseSheet($zip[$target]->getContent());
                } catch (Throwable $e) {
                    $rows = [];
                }

                $sheets[] = [
                    'name' => self::decodeEntities($nM[1]),
                    'rows' => $rows,
                ];
            }
        }

        return $sheets;
    }

    private function loadSharedStrings(string $xml): void
    {
        $this->sharedStrings = [];
        if (preg_match_all('/<si(?:\s[^>]*)?>(.*?)<\/si>/s', $xml, $sis)) {
            foreach ($sis[1] as $si) {
                $text = '';
                if (preg_match_all('/<t(?:\s[^>]*)?>(.*?)<\/t>/s', $si, $ts)) {
                    $text = implode('', $ts[1]);
                }
                $this->sharedStrings[] = self::decodeEntities($text);
            }
        }
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseSheet(string $xml): array
    {
        $rows = [];
        if (! preg_match_all('/<row\b[^>]*>(.*?)<\/row>/s', $xml, $rowMatches)) {
            return $rows;
        }

        foreach ($rowMatches[1] as $rowXml) {
            $cells = [];
            if (preg_match_all('/<c\s([^>]*?)>(.*?)<\/c>/s', $rowXml, $cellMatches, PREG_SET_ORDER)) {
                foreach ($cellMatches as $cm) {
                    $attrs = $cm[1];
                    $inner = $cm[2];

                    if (! preg_match('/\br="([A-Z]+)\d+"/', $attrs, $refM)) {
                        continue;
                    }

                    $value = '';
                    if (str_contains($attrs, 't="inlineStr"')) {
                        if (preg_match_all('/<t(?:\s[^>]*)?>(.*?)<\/t>/s', $inner, $ts)) {
                            $value = implode('', $ts[1]);
                        }
                    } elseif (preg_match('/<v(?:\s[^>]*)?>(.*?)<\/v>/s', $inner, $vM)) {
                        $value = $vM[1];
                    }

                    if ($value !== '' && str_contains($attrs, 't="s"')) {
                        $value = $this->sharedStrings[(int) $value] ?? '';
                    }

                    $value = self::decodeEntities(trim($value));
                    if ($value !== '') {
                        $cells[$refM[1]] = $value;
                    }
                }
            }
            if ($cells !== []) {
                $rows[] = $cells;
            }
        }

        return $rows;
    }

    private static function decodeEntities(string $s): string
    {
        $s = str_replace(['&lt;', '&gt;', '&quot;', '&apos;'], ['<', '>', '"', "'"], $s);
        $s = preg_replace_callback('/&#x([0-9a-fA-F]+);/', static fn ($m) => mb_chr((int) hexdec($m[1]), 'UTF-8'), $s) ?? $s;
        $s = preg_replace_callback('/&#(\d+);/', static fn ($m) => mb_chr((int) $m[1], 'UTF-8'), $s) ?? $s;
        return str_replace('&amp;', '&', $s);
    }
}
