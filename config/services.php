<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'sendlayer' => [
        'api_key' => env('SENDLAYER_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'bot_api' => [
        // Bearer token consumed by BotApiAuth middleware — set in .env only.
        'token' => env('BOT_API_TOKEN'),
    ],

    'anthropic' => [
        // Used by RunImplementerJob to talk to the Anthropic Messages API.
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_IMPLEMENTER_MODEL', 'claude-opus-5'),
        // Hard per-run spend cap — abort loop if we cross this.
        'max_cost_usd' => (float) env('ANTHROPIC_MAX_COST_USD', 5.0),
        // Hard iteration cap — safety net against infinite tool loops.
        'max_iterations' => (int) env('ANTHROPIC_MAX_ITERATIONS', 40),
    ],

    'github' => [
        // Fine-grained PAT with Contents + Pull Requests read/write on this repo.
        'token' => env('GITHUB_TOKEN'),
        'owner' => env('GITHUB_OWNER', 'cgrantdev'),
        'repo' => env('GITHUB_REPO', 'devmap'),
        // Branch PRs land against. Kept configurable in case we ever
        // want a staging trunk between agent and main.
        'base_branch' => env('GITHUB_BASE_BRANCH', 'main'),
    ],

    'discord' => [
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'application_id' => env('DISCORD_APPLICATION_ID'),
        'public_key' => env('DISCORD_PUBLIC_KEY'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        // Channel that receives the weekly growth digest. Restrict its
        // visibility in the Discord UI — the bot posts here regardless.
        'growth_channel_id' => env('DISCORD_GROWTH_CHANNEL_ID', '1541364154093404171'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
