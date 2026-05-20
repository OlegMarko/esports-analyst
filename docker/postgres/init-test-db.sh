#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE esports_analyst_test;
EOSQL

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "esports_analyst_test" <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS vector;
EOSQL
