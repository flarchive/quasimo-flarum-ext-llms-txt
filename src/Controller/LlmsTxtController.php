<?php

declare(strict_types=1);

namespace Quasimo\LlmsTxt\Controller;

use Flarum\Discussion\Discussion;
use Flarum\Http\UrlGenerator;
use Flarum\Post\CommentPost;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LlmsTxtController implements RequestHandlerInterface
{
    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $isFull = str_ends_with(rtrim($path, '/'), 'llms-full.txt');

        $enabledKey = $isFull ? 'llms_txt.full_enabled' : 'llms_txt.enabled';

        if (!$this->settings->get($enabledKey, '1')) {
            return $this->textResponse('Not Found', 404);
        }

        $content = $isFull ? $this->generateFull() : $this->generateBrief();

        return $this->textResponse($content, 200);
    }

    // -------------------------------------------------------------------------
    // Brief version — llms.txt
    // -------------------------------------------------------------------------

    protected function generateBrief(): string
    {
        $forumTitle   = $this->settings->get('forum_title', 'Flarum Forum');
        $forumDesc    = $this->settings->get('forum_description', '');
        $customIntro  = $this->settings->get('llms_txt.custom_intro', '');
        $baseUrl      = $this->baseUrl();
        $maxDiscussions = max(1, (int) $this->settings->get('llms_txt.max_discussions', 100));
        $sortBy       = $this->settings->get('llms_txt.sort', 'latest');

        $lines = [];

        // --- Header ---
        $lines[] = "# {$forumTitle}";
        $lines[] = '';

        if ($forumDesc) {
            $lines[] = '> ' . $forumDesc;
            $lines[] = '';
        }

        if ($customIntro) {
            $lines[] = $customIntro;
            $lines[] = '';
        }

        // --- Tags / Categories (optional extension) ---
        if (class_exists(\Flarum\Tags\Tag::class)) {
            $tags = \Flarum\Tags\Tag::query()
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get();

            if ($tags->isNotEmpty()) {
                $lines[] = '## Categories';
                $lines[] = '';
                foreach ($tags as $tag) {
                    $tagUrl  = $baseUrl . '/t/' . $tag->slug;
                    $tagDesc = $tag->description ? ': ' . $this->singleLine($tag->description) : '';
                    $lines[] = "- [{$tag->name}]({$tagUrl}){$tagDesc}";
                }
                $lines[] = '';
            }
        }

        // --- Recent discussions ---
        $discussions = $this->queryDiscussions($sortBy, $maxDiscussions);

        if ($discussions->isNotEmpty()) {
            $sectionTitle = $sortBy === 'top' ? 'Top Discussions' : 'Recent Discussions';
            $lines[] = "## {$sectionTitle}";
            $lines[] = '';

            foreach ($discussions as $discussion) {
                $discussionUrl = $this->discussionUrl($baseUrl, $discussion);
                // Use first post excerpt as description when available
                $excerpt = $this->getExcerpt($discussion, 120);
                $suffix  = $excerpt ? ': ' . $excerpt : '';
                $lines[] = "- [{$discussion->title}]({$discussionUrl}){$suffix}";
            }

            $lines[] = '';
        }

        // --- Footer links ---
        $lines[] = '## Links';
        $lines[] = '';
        $lines[] = "- [Forum Home]({$baseUrl})";

        if (class_exists(\Flarum\Tags\Tag::class)) {
            $lines[] = "- [All Categories]({$baseUrl}/tags)";
        }

        if ($this->settings->get('llms_txt.full_enabled', '1')) {
            $lines[] = "- [Full Content]({$baseUrl}/llms-full.txt)";
        }

        return implode("\n", $lines) . "\n";
    }

    // -------------------------------------------------------------------------
    // Full version — llms-full.txt
    // -------------------------------------------------------------------------

    protected function generateFull(): string
    {
        $forumTitle   = $this->settings->get('forum_title', 'Flarum Forum');
        $forumDesc    = $this->settings->get('forum_description', '');
        $customIntro  = $this->settings->get('llms_txt.custom_intro', '');
        $baseUrl      = $this->baseUrl();
        $maxDiscussions = max(1, (int) $this->settings->get('llms_txt.max_discussions', 100));
        $maxPosts     = max(1, (int) $this->settings->get('llms_txt.max_posts_per_discussion', 50));
        $sortBy       = $this->settings->get('llms_txt.sort', 'latest');

        $lines = [];

        // --- Header ---
        $lines[] = "# {$forumTitle}";
        $lines[] = '';

        if ($forumDesc) {
            $lines[] = '> ' . $forumDesc;
            $lines[] = '';
        }

        if ($customIntro) {
            $lines[] = $customIntro;
            $lines[] = '';
        }

        // --- Discussions with full content ---
        $discussions = $this->queryDiscussions($sortBy, $maxDiscussions);

        foreach ($discussions as $discussion) {
            $discussionUrl = $this->discussionUrl($baseUrl, $discussion);

            $lines[] = '---';
            $lines[] = '';
            $lines[] = "## {$discussion->title}";
            $lines[] = '';
            $lines[] = "URL: {$discussionUrl}";

            if ($discussion->user) {
                $lines[] = "Author: {$discussion->user->username}";
            }

            $lines[] = 'Date: ' . $discussion->created_at->toIso8601String();
            $lines[] = "Replies: {$discussion->comment_count}";
            $lines[] = '';

            $posts = CommentPost::query()
                ->where('discussion_id', $discussion->id)
                ->whereNull('hidden_at')
                ->with('user')
                ->orderBy('number', 'asc')
                ->limit($maxPosts)
                ->get();

            foreach ($posts as $index => $post) {
                $plainText = $this->toPlainText((string) ($post->getRawOriginal('content') ?? ''));

                if ($index === 0) {
                    // Opening post — no extra heading
                    $lines[] = $plainText;
                } else {
                    $author = $post->user ? $post->user->username : 'Anonymous';
                    $date   = $post->created_at->toDateString();
                    $lines[] = '';
                    $lines[] = "### Reply by {$author} ({$date})";
                    $lines[] = '';
                    $lines[] = $plainText;
                }
            }

            $lines[] = '';
        }

        return implode("\n", $lines) . "\n";
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function queryDiscussions(string $sortBy, int $limit)
    {
        $query = Discussion::query()
            ->where('is_private', false)
            ->whereNull('hidden_at')
            ->with(['user'])
            ->limit($limit);

        if ($sortBy === 'top') {
            $query->orderBy('comment_count', 'desc');
        } else {
            $query->orderBy('last_posted_at', 'desc');
        }

        return $query->get();
    }

    protected function getExcerpt(Discussion $discussion, int $maxLength): string
    {
        $firstPost = CommentPost::query()
            ->where('discussion_id', $discussion->id)
            ->whereNull('hidden_at')
            ->orderBy('number', 'asc')
            ->first();

        if (!$firstPost) {
            return '';
        }

        $text = $this->toPlainText((string) ($firstPost->getRawOriginal('content') ?? ''));
        $text = $this->singleLine($text);

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength) . '…';
        }

        return $text;
    }

    /**
     * Convert raw Flarum post content (may be XML/HTML from the formatter) to
     * clean plain text suitable for an llms.txt file.
     */
    protected function toPlainText(string $raw): string
    {
        if (empty($raw)) {
            return '';
        }

        // Flarum's formatter stores content as XML. Try to strip tags cleanly.
        $text = preg_replace('/<br\s*\/?>/i', "\n", $raw) ?? $raw;
        $text = preg_replace('/<\/p>/i', "\n\n", $text) ?? $text;
        $text = preg_replace('/<\/li>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<li[^>]*>/i', '- ', $text) ?? $text;
        $text = preg_replace('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is', "### $1\n", $text) ?? $text;
        $text = preg_replace('/<blockquote[^>]*>(.*?)<\/blockquote>/is', "> $1", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Collapse excessive blank lines
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function singleLine(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    protected function baseUrl(): string
    {
        return rtrim($this->url->to('forum')->base(), '/');
    }

    protected function discussionUrl(string $baseUrl, Discussion $discussion): string
    {
        $slug = $discussion->slug ?: '';
        $id   = $discussion->id;

        return $slug
            ? "{$baseUrl}/d/{$id}-{$slug}"
            : "{$baseUrl}/d/{$id}";
    }

    protected function textResponse(string $body, int $status = 200): ResponseInterface
    {
        $stream = new Stream('php://memory', 'r+');
        $stream->write($body);
        $stream->rewind();

        return new Response($stream, $status, [
            'Content-Type'  => 'text/plain; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
