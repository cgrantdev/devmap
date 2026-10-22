<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Command;

class BlogsRecent extends Command
{
    protected $signature = 'blogs:recent {--days=14}';
    protected $description = 'List recently created/updated blogs with author + source signals.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $blogs = Blog::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->get();

        $this->line("=== Blogs created in last {$days}d ({$blogs->count()}) ===");
        foreach ($blogs as $b) {
            $this->line(sprintf(
                "  [%d] %s  created=%s  author_id=%s  user=%s",
                $b->id,
                mb_substr($b->title ?: $b->slug, 0, 70),
                $b->created_at?->toDateString(),
                $b->user_id ?: '-',
                optional($b->user ?? \App\Models\User::find($b->user_id))->email ?: '-'
            ));
        }
        return self::SUCCESS;
    }
}
