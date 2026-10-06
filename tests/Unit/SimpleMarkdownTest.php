<?php

namespace Tests\Unit;

use App\Content\EducationalContentPublisher;
use App\Support\SimpleMarkdown;
use PHPUnit\Framework\TestCase;

class SimpleMarkdownTest extends TestCase
{
    public function test_renders_headings_lists_tables_and_safe_links(): void
    {
        $html = SimpleMarkdown::toHtml(<<<'MD'
## Evidence

A **peptide** is not a price.

- [Labs](/testing-labs)
- Skip [bad](javascript:alert(1))

| | BPC-157 |
| --- | --- |
| Identity | Synthetic |
MD);

        $this->assertStringContainsString('<h2>Evidence</h2>', $html);
        $this->assertStringContainsString('<strong>peptide</strong>', $html);
        $this->assertStringContainsString('<a href="/testing-labs">Labs</a>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<th></th>', $html);
        $this->assertStringContainsString('<th>BPC-157</th>', $html);
        $this->assertStringContainsString('<td>Synthetic</td>', $html);
    }

    public function test_prepare_strips_frontmatter_meta_notes_and_competitor_lines(): void
    {
        $clean = EducationalContentPublisher::prepare(<<<'MD'
---
suggested_slug: /blog/example
---

# Visible

Body.

## Sources

1. Primary. https://www.fda.gov/example
2. Competitor page: https://formblends.com/research/guides/peptide-legality-regulatory-guide

## Note for Meta Optimizer

Do not publish this note.
MD);

        $this->assertStringNotContainsString('suggested_slug', $clean);
        $this->assertStringNotContainsString('Note for Meta Optimizer', $clean);
        $this->assertStringNotContainsString('formblends.com', $clean);
        $this->assertStringContainsString('fda.gov', $clean);
        $this->assertStringContainsString('# Visible', $clean);
    }

    public function test_prepare_strips_cms_comments_and_workspace_paths(): void
    {
        $clean = EducationalContentPublisher::prepare(<<<'MD'
<!-- CMS paste: body only. Upload featured PNG separately. Do not paste YAML or the box file path. -->

# Visible title

Body stays.

Featured image present: /workspace/drafts/images/example-featured.png
MD);

        $this->assertStringNotContainsString('CMS paste', $clean);
        $this->assertStringNotContainsString('/workspace/', $clean);
        $this->assertStringContainsString('# Visible title', $clean);
        $this->assertStringContainsString('Body stays.', $clean);
    }
}
