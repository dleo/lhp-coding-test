<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\EventImageResolver;
use Illuminate\Console\Command;

/**
 * Generates the fixed local pool of placeholder SVG images into
 * public/images/events/pool/.  Idempotent — safe to re-run; existing files
 * are overwritten.  One file per pool slot, zero-padded filenames (00.svg …).
 *
 * Each SVG is coloured by slot index and embeds a visible slot number so that
 * files are easily distinguishable in the browser.
 */
final class MakePlaceholderImages extends Command
{
    protected $signature = 'events:make-placeholders';

    protected $description = 'Generate the placeholder SVG image pool into public/images/events/pool/';

    /**
     * Palette — one colour per slot, cycling when POOL_SIZE > palette length.
     *
     * @var list<array{bg: string, text: string}>
     */
    private const PALETTE = [
        ['bg' => '#4F46E5', 'text' => '#FFFFFF'], // indigo
        ['bg' => '#0EA5E9', 'text' => '#FFFFFF'], // sky
        ['bg' => '#10B981', 'text' => '#FFFFFF'], // emerald
        ['bg' => '#F59E0B', 'text' => '#1F2937'], // amber
        ['bg' => '#EF4444', 'text' => '#FFFFFF'], // red
        ['bg' => '#8B5CF6', 'text' => '#FFFFFF'], // violet
        ['bg' => '#EC4899', 'text' => '#FFFFFF'], // pink
        ['bg' => '#14B8A6', 'text' => '#FFFFFF'], // teal
        ['bg' => '#F97316', 'text' => '#FFFFFF'], // orange
        ['bg' => '#6366F1', 'text' => '#FFFFFF'], // purple-blue
    ];

    public function handle(): int
    {
        $poolDir = public_path('images/events/pool');

        if (! is_dir($poolDir) && ! mkdir($poolDir, 0755, true) && ! is_dir($poolDir)) {
            $this->error("Failed to create directory: {$poolDir}");

            return self::FAILURE;
        }

        $size = EventImageResolver::POOL_SIZE;
        $paletteSize = count(self::PALETTE);

        for ($i = 0; $i < $size; $i++) {
            $colour = self::PALETTE[$i % $paletteSize];
            $label = str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $svg = $this->buildSvg($label, $colour['bg'], $colour['text']);
            $path = $poolDir."/{$label}.svg";
            file_put_contents($path, $svg);
        }

        $this->info("Generated {$size} placeholder images in {$poolDir}");

        return self::SUCCESS;
    }

    /**
     * Build a minimal, self-contained SVG placeholder.
     *
     * @param  string  $label  Zero-padded slot index, e.g. "07"
     * @param  string  $bg  Background fill colour, e.g. "#4F46E5"
     * @param  string  $textColour  Label text colour
     */
    private function buildSvg(string $label, string $bg, string $textColour): string
    {
        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 450" width="800" height="450">
              <rect width="800" height="450" fill="{$bg}"/>
              <text
                x="400" y="200"
                font-family="system-ui, sans-serif"
                font-size="120"
                font-weight="bold"
                text-anchor="middle"
                dominant-baseline="middle"
                fill="{$textColour}"
                opacity="0.9"
              >{$label}</text>
              <text
                x="400" y="330"
                font-family="system-ui, sans-serif"
                font-size="36"
                text-anchor="middle"
                dominant-baseline="middle"
                fill="{$textColour}"
                opacity="0.7"
              >Event Placeholder</text>
            </svg>
            SVG;
    }
}
