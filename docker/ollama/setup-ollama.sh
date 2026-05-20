#!/bin/bash
set -e

# ── Ollama ────────────────────────────────────────────────────────────────────
OLLAMA_CONTAINER="esports_analyst_ollama"

if ! docker compose ps --status running | grep -q "$OLLAMA_CONTAINER"; then
  echo "Starting Ollama container..."
  docker compose up -d ollama
  echo "Waiting for Ollama to be ready..."
  until docker compose exec ollama ollama list > /dev/null 2>&1; do
    sleep 1
  done
fi

echo "Pulling Ollama models for CS2 analyst..."
docker compose exec ollama ollama pull nomic-embed-text
docker compose exec ollama ollama pull llama3.2
echo "Models ready."
