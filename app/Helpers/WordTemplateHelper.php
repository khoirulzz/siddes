<?php

namespace App\Helpers;

use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

class WordTemplateHelper
{
    /**
     * Fill a Word template with data and return the path to the generated file.
     *
     * @param string $templatePath
     * @param array $data
     * @param array<string, string> $literalReplacements
     * @return string Path to generated file
     * @throws \Exception
     */
    public static function fillTemplate(string $templatePath, array $data, array $literalReplacements = []): string
    {
        if (! file_exists($templatePath)) {
            throw new \Exception("Template file not found: {$templatePath}");
        }

        try {
            $preparedTemplatePath = self::prepareTemplate($templatePath, array_keys($data));
            $processor = new TemplateProcessor($preparedTemplatePath);
            $processor->setMacroChars('{', '}');

            foreach ($data as $key => $value) {
                $value = (string) ($value ?? '');
                $processor->setValue($key, $value);
            }

            $outputDir = storage_path('app/tmp-letters');
            if (! is_dir($outputDir)) {
                @mkdir($outputDir, 0755, true);
            }

            $outputPath = $outputDir . '/tmp_surat_' . uniqid() . '.docx';
            $processor->saveAs($outputPath);

            $allLiteralReplacements = array_merge(
                self::buildBraceTokenReplacements($data),
                $literalReplacements,
            );
            if ($allLiteralReplacements !== []) {
                self::replaceLiteralTokens($outputPath, $allLiteralReplacements);
            }

            if (! file_exists($outputPath)) {
                throw new \Exception("Failed to generate output file at: {$outputPath}");
            }

            @unlink($preparedTemplatePath);

            return $outputPath;
        } catch (\Exception $e) {
            throw new \Exception("Error filling template: " . $e->getMessage());
        }
    }

    /**
     * @param array<int, string> $keys
     */
    private static function prepareTemplate(string $templatePath, array $keys): string
    {
        $outputDir = storage_path('app/tmp-letters');
        if (! is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        $preparedTemplatePath = $outputDir . '/prepared_' . uniqid() . '.docx';
        copy($templatePath, $preparedTemplatePath);

        $replacements = [];
        foreach ($keys as $key) {
            $replacements['{' . $key . '}'] = '{' . $key . '}';
        }
        self::replaceLiteralTokens($preparedTemplatePath, $replacements);

        return $preparedTemplatePath;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private static function buildBraceTokenReplacements(array $data): array
    {
        $replacements = [];
        foreach ($data as $key => $value) {
            $token = '{' . (string) $key . '}';
            $replacements[$token] = (string) ($value ?? '');
        }

        return $replacements;
    }

    /**
     * Replace tokens directly in all relevant XML files inside docx.
     *
     * @param array<string, string> $replacements
     */
    private static function replaceLiteralTokens(string $docxPath, array $replacements): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! str_starts_with($name, 'word/') || ! str_ends_with($name, '.xml')) {
                continue;
            }

            $xml = $zip->getFromIndex($i);
            if (! is_string($xml) || $xml === '') {
                continue;
            }

            foreach ($replacements as $token => $value) {
                $escaped = htmlspecialchars((string) $value, ENT_XML1);
                $xml = str_replace($token, $escaped, $xml);
                $xml = self::replaceSplitToken($xml, $token, $escaped);
            }

            $zip->addFromString($name, $xml);
        }

        $zip->close();
    }

    private static function replaceSplitToken(string $xml, string $token, string $replacement): string
    {
        $chars = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($chars) <= 1) {
            return str_replace($token, $replacement, $xml);
        }

        $glue = '(?:<\/w:t>|<w:t[^>]*>|<[^>]+>)*';
        $parts = array_map(
            static fn (string $char): string => preg_quote($char, '/'),
            $chars
        );
        $pattern = '/' . implode($glue, $parts) . '/u';

        return (string) preg_replace($pattern, $replacement, $xml);
    }
}
