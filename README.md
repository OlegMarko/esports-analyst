# Esports Analyst

A real-time CS2 Faceit match analysis platform built with Laravel 13. Tracks players, ingests matches via Faceit webhooks, generates AI-powered analysis with structured insights, and predicts live match outcomes using ELO-based modelling.

## Screenshots

| Dashboard | Player Profile |
|-----------|---------------|
| Match cards with AI headlines, MVP, KDA, ADR | Per-player stats, AI performance brief, match history |

| Match Analysis Modal | Live Matches |
|---------------------|-------------|
| Ratings, key moments, similar matches | Real-time predictions with ELO, odds, skill-level badges |

## Features

- **Real-time webhook ingestion** — Faceit webhooks trigger immediate live match detection and post-match processing
- **AI match analysis** — Structured output per match: headline, 3-4 sentence summary, key moments, MVP, economy/mechanics/quality ratings (1–10)
- **Player profiles** — Per-player stats (KDA, ADR, HS%, win rate), AI performance brief with KDA trend and map breakdown
- **Live match predictions** — ELO-based win probabilities with decimal odds, 3-tier data resolution (tracked ELO → match history → Faceit skill level)
- **Hybrid retrieval + reranking** — Vector + keyword search (RRF fusion) with optional Cohere reranking to surface similar historical matches as AI context
- **Leaderboard** — Auto-updated rankings across tracked players
- **Horizon queue management** — Dedicated supervisors for webhooks, summaries, and embeddings

## Tech Stack

- **Framework**: Laravel 13 + Livewire 4 + Flux UI
- **Database**: PostgreSQL + pgvector (semantic embeddings)
- **Queue**: Laravel Horizon (Redis)
- **AI**: Laravel AI package — Anthropic Claude (default) or Ollama (local)
- **Embeddings**: Anthropic or Ollama (`nomic-embed-text`)
- **Reranking**: Cohere (optional, rate-limited gracefully)
- **Data source**: Faceit Data API v4 + webhooks

## Requirements

- PHP 8.2+
- PostgreSQL with `pgvector` extension
- Redis
- Node.js (for asset compilation)
- Faceit Developer account (API key + webhook subscription)
- One of: Anthropic API key **or** Ollama running locally

## Installation

```bash
git clone <repo>
cd esports-analyst
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Configure your `.env` (see below), then:

```bash
php artisan migrate
php artisan db:seed   # optional demo data
```

## Environment Variables

```env
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=esports_analyst
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis (queue + cache)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# Faceit
FACEIT_API_KEY=your_faceit_api_key
FACEIT_APP_ID=your_webhook_app_id        # from Faceit developer portal
FACEIT_HUB_IDS=uuid1,uuid2              # optional: poll specific hubs

# AI provider — pick one
AI_DRIVER=anthropic
AI_EMBEDDING_DRIVER=anthropic
AI_AGENT_PROVIDER=anthropic
ANTHROPIC_API_KEY=sk-ant-...

# — OR use Ollama locally —
# AI_DRIVER=ollama
# AI_EMBEDDING_DRIVER=ollama
# AI_AGENT_PROVIDER=ollama
# OLLAMA_BASE_URL=http://localhost:11434
# AI_AGENT_MODEL=llama3.2
# OLLAMA_EMBEDDING_MODEL=nomic-embed-text
# OLLAMA_EMBEDDING_DIMS=768

# Reranking (optional — Cohere trial key works, 10 req/min limit handled automatically)
COHERE_API_KEY=your_cohere_key

# Broadcasting (optional — for real-time UI updates when summaries complete)
BROADCAST_CONNECTION=reverb   # or pusher, log
```

## Running Locally

**1. Start infrastructure (PostgreSQL + Redis + Ollama)**

```bash
docker compose up -d
```

Docker Compose provides:
- `esports_analyst_db` — PostgreSQL 17 with pgvector on port `5433`
- `esports_analyst_redis` — Redis 7 on port `6379`
- `esports_analyst_ollama` — Ollama on port `11434` (uses GPU if available)

**2. Pull Ollama models** (only needed once, or when switching to Ollama driver)

```bash
bash ./docker/ollama/setup-ollama.sh
```

Pulls `llama3.2` (analysis) and `nomic-embed-text` (embeddings) into the Ollama container.

**3. Run migrations**

```bash
php artisan migrate
```

**4. Start application processes** (three terminals)

```bash
# Terminal 1 — web server
php artisan serve

# Terminal 2 — queue workers (Horizon)
php artisan horizon

# Terminal 3 — scheduler (match polling, live match cleanup)
php artisan schedule:work
```

## Faceit Webhook Setup

1. Go to [developers.faceit.com](https://developers.faceit.com) → your app → Webhooks
2. Add a subscription with URL: `https://your-domain.com/webhooks/faceit`
3. Subscribe to events: `match_object_created`, `match_status_ready`, `match_status_finished`, `match_status_cancelled`
4. Copy the **App ID** into `FACEIT_APP_ID` in your `.env`

For local development, use a tunnel (ngrok, Expose, etc.) and point the webhook URL there.

## How It Works

```
Faceit webhook → POST /webhooks/faceit
    └── ProcessFaceitWebhookJob
            ├── match_status_ready   → upsert LiveMatch (ELO prediction + roster)
            ├── match_status_finished → delete LiveMatch + dispatch PollFaceitMatchesJob (3 min delay)
            └── match_status_cancelled → delete LiveMatch

PollFaceitMatchesJob (every 10 min or webhook-triggered)
    └── Fetch recent matches from Faceit API
        └── EmbedMatchJob → generate pgvector embeddings
            └── GenerateSummaryJob
                    ├── HybridMatchRetriever (vector + BM25 → RRF)
                    ├── Cohere reranking (optional, rate-limited to 5/min)
                    ├── ContextCompressor (summarise similar matches)
                    ├── MatchAnalystAgent (structured AI output)
                    └── broadcast MatchSummaryReady → Livewire update
```

## Queue Supervisors

| Supervisor | Queue | Workers | Purpose |
|---|---|---|---|
| supervisor-webhooks | webhooks | 3 | Webhook processing, match polling, leaderboard updates |
| supervisor-summaries | summaries | 2 | AI analysis generation |
| supervisor-embeddings | embeddings | 1 | pgvector embedding generation |

## Key Artisan Commands

```bash
# Manually trigger AI analysis for a specific match
php artisan tinker
> App\Jobs\GenerateSummaryJob::dispatch($matchId)->onQueue('summaries');

# Re-run all AI analysis
> App\Models\GameMatch::query()->update(['ai_summary' => null, 'summary_at' => null]);
> App\Models\GameMatch::pluck('id')->each(fn($id) => App\Jobs\GenerateSummaryJob::dispatch($id)->onQueue('summaries'));

# Check queue state
php artisan horizon:status
php artisan queue:retry all
```

## License

MIT
