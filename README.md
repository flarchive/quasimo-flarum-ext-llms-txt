# quasimo/flarum-ext-llms-txt

A [Flarum](https://flarum.org) extension that automatically generates [`llms.txt`](https://llmstxt.org) and `llms-full.txt` files for your forum, making your community content discoverable and readable by AI language models.

## Features

- Serves `yourforum.com/llms.txt` — a concise index (title, categories, discussion links with excerpts)
- Serves `yourforum.com/llms-full.txt` — full post content for every discussion
- Admin panel to toggle each file on/off independently
- Configurable sort order (latest activity or most replies)
- Configurable max discussions and max posts per discussion
- Optional custom introduction paragraph
- Automatic category listing when [flarum/tags](https://github.com/flarum/tags) is installed
- `Cache-Control: public, max-age=3600` header for CDN-friendly caching

## Installation

```bash
composer require quasimo/flarum-ext-llms-txt
php flarum extension:enable quasimo-llms-txt
php flarum cache:clear
```

## Configuration

Go to **Admin → Extensions → LLMs.txt Generator** and configure:

| Setting | Default | Description |
|---|---|---|
| Enable `llms.txt` | ✅ | Toggle the brief index file |
| Enable `llms-full.txt` | ✅ | Toggle the full content file |
| Sort order | Latest | Latest activity or Most replies |
| Max discussions | 100 | Cap on discussions included |
| Max posts per discussion | 50 | Full file only |
| Custom intro | — | Optional paragraph below forum title |

## Output format

### llms.txt (brief)

```
# My Forum

> The best place to discuss things.

## Categories
- [Support](https://example.com/t/support): Get help here
- [General](https://example.com/t/general)

## Recent Discussions
- [How to install X](https://example.com/d/42-how-to-install-x): Step by step guide…
- [Release 2.0](https://example.com/d/38-release-2-0): We just shipped…

## Links
- [Forum Home](https://example.com)
- [All Categories](https://example.com/tags)
- [Full Content](https://example.com/llms-full.txt)
```

### llms-full.txt (full content)

```
# My Forum

> The best place to discuss things.

---

## How to install X

URL: https://example.com/d/42-how-to-install-x
Author: alice
Date: 2024-06-01T10:00:00+00:00
Replies: 5

Here is a step-by-step guide to installing X…

### Reply by bob (2024-06-01)

Thanks, this worked perfectly!
```

## Links

- [Flarum Marketplace](https://discuss.flarum.org/d/XXXXX)
- [Packagist](https://packagist.org/packages/quasimo/flarum-ext-llms-txt)
- [llmstxt.org spec](https://llmstxt.org)

## License

MIT © [Quasimo](https://github.com/Quasimo)
