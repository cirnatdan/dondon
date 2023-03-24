<?php

namespace App;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class NitterScraper
{
    public function __construct(private Client $goutteClient)
    {
    }

    public function searchAccounts(string $query, bool $resolve = false): array
    {
        $twitterUsername = explode('@', $query)[1];
        $crawler = $this->goutteClient->request('GET', 'https://nitter.net/search?f=users&q=' . rawurlencode($twitterUsername));

        error_log($twitterUsername);

        if ($resolve) {
            return [$this->lookupAccount($twitterUsername)];
        }

        return array_filter($crawler->filter('.timeline-item')->each(function ($node) use ($twitterUsername, $resolve) {
            error_log(ltrim($node->filter('.username')->text(), '@'));
            if (ltrim($node->filter('.username')->text(), '@') === $twitterUsername) {
                if ($resolve) {
                    return $this->lookupAccount($twitterUsername);
                }

                return [
                    'id' => 3,
                    'acct' => ltrim($node->filter('.username')->text(), '@') . '@twitter.com',
                    'username' => ltrim($node->filter('.username')->text(), '@'),
                    'display_name' => $node->filter('.fullname')->text(),
                    'avatar' => str_replace(['/pic/', 'bigger.jpg'], ['https://', '400x400.jpg'], rawurldecode($node->filter('.avatar')->attr('src'))),
                    'url' => 'https://twitter.com/' . $twitterUsername,
                    'emojis' => [],
                    'fields' => [],
                ];
            }
            return [];
        }));
    }

    public function lookupAccount(string $twitterUsername): array
    {
        $crawler = $this->goutteClient->request('GET', 'https://nitter.net/' . rawurlencode($twitterUsername));

        $node = $crawler->filter('.profile-tabs');

        if (ltrim(strtolower($node->filter('.profile-card-username')->text()), '@') === strtolower($twitterUsername)) {
            return [
                'id' => '@' . $twitterUsername . '@twitter.com',
                'twitter_id' => $node->filter('.profile_banner > a')->getNode(0) !== null ? $this->extractTwitterIDFromBannerURL($node->filter('.profile-banner > a')->attr('href')) : null,
                'acct' => ltrim($node->filter('.profile-card-username')->text(), '@') . '@twitter.com',
                'username' => ltrim($node->filter('.profile-card-username')->text(), '@'),
                'display_name' => $node->filter('.profile-card-fullname')->text(),
                'avatar' => $this->nitterImageURLToTwitterURL(rawurldecode($node->filter('.profile-card-avatar')->attr('href'))),
                'url' => 'https://twitter.com/' . $twitterUsername,
                'header' => $this->nitterImageURLToTwitterURL(rawurldecode($node->filter('.profile-banner > a')->attr('href'))),
                'created_at' => \DateTimeImmutable::createFromFormat('h:i A - d M Y', $node->filter('.profile-joindate > span')->attr('title'))->format(\DateTimeImmutable::ATOM),
                'emojis' => [],
                'fields' => [],
            ];
        }

        return [];
    }

    public function getAccountTweets(string $username, bool $withReplies = false)
    {
        $twitterUsername = explode('@', $username)[1];
        $url = 'https://nitter.it/' . rawurlencode($twitterUsername);
        if ($withReplies) {
            $url .= '/with_replies';
        }
        $crawler = $this->goutteClient->request('GET', $url);

        $account = $this->lookupAccount($twitterUsername);

        return $crawler->filter('.timeline-item ')->each(function (Crawler $node) use ($account) {
            $tweetID = filter_var($node->filter('.tweet-link')->attr('href'), FILTER_SANITIZE_NUMBER_INT);

            //print_r($node->filter('.tweet-body > .attachments')->html());
            return [
                'id' => $tweetID,
                'created_at' => \DateTimeImmutable::createFromFormat('M d, Y · h:i A e', $node->filter('.tweet-date > a')->attr('title'))->format(\DateTimeImmutable::ATOM),
                'in_reply_to_id' => null,
                'in_reply_to_account_id' => null,
                'sensitive' => false,
                'spoiler_text' => '',
                'visibility' => 'public',
                'language' => null,
                'url' => 'https://twitter.com/' . $node->filter('.tweet-link')->attr('href'),
                'replies_count' => filter_var($node->filter('.tweet-body > .tweet-stats > .tweet-stat > .icon-container > .icon-comment')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
                'reblogs_count' => filter_var($node->filter('.tweet-body > .tweet-stats > .tweet-stat > .icon-container > .icon-retweet')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
                'favourites_count' => filter_var($node->filter('.tweet-body > .tweet-stats > .tweet-stat > .icon-container > .icon-heart')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
                'edited_at' => null,
                'favourited' => false,
                'reblogged' => false,
                'muted' => false,
                'bookmarked' => false,
                'content' => $node->filter('.tweet-body > div > .retweet-header')->count() === 0 ? $node->filter('.tweet-body > .tweet-content.media-body')->text() : '',
                'filtered' => [],
                'reblog' => $node->filter('.tweet-body > div > .retweet-header')->count()
                    ?  [
                        'id' => $tweetID,
                        'created_at' => \DateTimeImmutable::createFromFormat('M d, Y · h:i A e', $node->filter('.tweet-date > a')->attr('title'))->format(\DateTimeImmutable::ATOM),
                        'in_reply_to_id' => null,
                        'in_reply_to_account_id' => null,
                        'sensitive' => false,
                        'spoiler_text' => '',
                        'visibility' => 'public',
                        'language' => null,
                        'url' => 'https://twitter.com/' . $node->filter('.tweet-link')->attr('href'),
                        'replies_count' => filter_var($node->filter('.tweet-body > .tweet-stats > .tweet-stat > .icon-container > .icon-comment')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
                        'reblogs_count' => filter_var($node->filter('.tweet-body > .tweet-stats > .tweet-stat > .icon-container > .icon-retweet')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
                        'favourites_count' => filter_var($node->filter('.tweet-body > .tweet-stats > .tweet-stat > .icon-container > .icon-heart')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
                        'edited_at' => null,
                        'favourited' => false,
                        'reblogged' => true,
                        'bookmarked' => false,
                        'content' => $node->filter('.tweet-body > .tweet-content.media-body')->text(),
                        'filtered' => [],
                        'reblog' => null,
                        'account' => [
                            'id' => $node->filter('.tweet-body > div > .tweet-header > .tweet-name-row > .fullname-and-username > a.username')->attr('title') . '@twitter.com',
                            'username' => str_replace('@', '', $node->filter('.tweet-body > div > .tweet-header > .tweet-name-row > .fullname-and-username > a.username')->attr('title')),
                            'acct' => str_replace('@', '', $node->filter('.tweet-body > div > .tweet-header > .tweet-name-row > .fullname-and-username > a.username')->attr('title')) . '@twitter.com',
                            'display_name' => $node->filter('.tweet-body > div > .tweet-header > .tweet-name-row > .fullname-and-username > a.fullname')->text(),
                            'locked' => false,
                            'bot' => false,
                            'discoverable' => false,
                            'group' => false,
                            'created_at' => \DateTimeImmutable::createFromFormat('M d, Y · h:i A e', $node->filter('.tweet-date > a')->attr('title'))->format(\DateTimeImmutable::ATOM), //todo
                            'note' => null,
                            'url' => 'https://twitter.com/' . str_replace('@', '', $node->filter('.tweet-body > div > .tweet-header > .tweet-name-row > .fullname-and-username > a.username')->attr('title')),
                            'avatar' => 'https://nitter.net' . $node->filter('.tweet-body > div > .tweet-header > .tweet-avatar > img.avatar.round')->attr('src'),
                            'avatar_static' => 'https://nitter.net' . $node->filter('.tweet-body > div > .tweet-header > .tweet-avatar > img.avatar.round')->attr('src'),
                            'header' => null,
                            'header_static' => null,
                            'followers_count' => null,
                            'following_count' => null,
                            'statuses_count' => null,
                            'last_status_at' => \DateTimeImmutable::createFromFormat('M d, Y · h:i A e', $node->filter('.tweet-date > a')->attr('title'))->format(\DateTimeImmutable::ATOM), //todo
                            'emojis' => [],
                            'fields' => [],
                        ],
                        'media_attachments' => $node->filter('.tweet-body > .attachments')->count() > 0
                            ? array_merge(
                                $node->filter('.tweet-body > .attachments > .gallery-row > .attachment > a')->each(function(Crawler $node) use($tweetID) {
                                    return [
                                        'id' => $tweetID, // TODO add unique image ids
                                        'type' => 'image',
                                        'url' => 'https://nitter.net' . $node->attr('href'),
                                        'preview_url' => 'https://nitter.net' . $node->filter('img')->attr('src'),
                                        'preview_remote_url' => null,
                                        'text_url' => null,
                                        'meta' => [],
                                        'description' => null,
                                        'blurhash' => 'UHSF-Day-;j[-paybHkC~qofRjj[9Fj[t7WB', // TODO
                                    ];
                                }),
                                $node->filter('.tweet-body > .attachments > .gallery-video > .video-container > video')->each(function (Crawler $node) use ($tweetID) {
                                    return [
                                        'id' => $tweetID, // TODO add unique image ids
                                        'type' => 'video',
                                        'url' => 'https://nitter.net' . $node->attr('data-url'),
                                        'preview_url' => 'https://nitter.net' . $node->attr('poster'),
                                        'remote_url' => 'https://nitter.net' . $node->attr('data-url'),
                                        'preview_remote_url' => null,
                                        'text_url' => null,
                                        'meta' => [],
                                        'description' => null,
                                        'blurHash' => 'UHH_lTDi?HWARN-=9FNG4m~WtSt5-;D$WYxu',
                                    ];
                                })
                            )
                            : [],
                        'mentions' => [],
                        'tags' => [],
                        'emojis' => [],
                        'card' => null, //$node->filter('.tweet-body > .quote'),
                        'poll' => null,
                    ] : null,
                'application' => null,
                'account' => $account,
                'media_attachments' => [],
                'mentions' => [],
                'tags' => [],
                'emojis' => [],
                'card' => null,
                'poll' => null,
           ];
        });
    }

    public function getTweet(string $tweetID): array
    {
        error_log($tweetID);
        $crawler = $this->goutteClient->request('GET', 'https://nitter.it/i/status/' . $tweetID);

        $node = $crawler->filter('.main-tweet > .timeline-item > .tweet-body');

        $twitterUsername = ltrim($node->filter('div > .tweet-header > .tweet-name-row > .fullname-and-username > a.username')->attr('title'), '@');
        return [
            'id' => $tweetID,
            'created_at' => \DateTimeImmutable::createFromFormat('M d, Y · h:i A e', $node->filter('.tweet-date > a')->attr('title'))->format(\DateTimeImmutable::ATOM),
            'in_reply_to_id' => null,
            'in_reply_to_account_id' => null,
            'sensitive' => false,
            'spoiler_text' => '',
            'visibility' => 'public',
            'language' => null,
            'url' => 'https://twitter.com/' . $twitterUsername . '/status/' . $tweetID,
            'replies_count' => filter_var($node->filter('.tweet-stats > .tweet-stat > .icon-container > .icon-comment')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
            'reblogs_count' => filter_var($node->filter('.tweet-stats > .tweet-stat > .icon-container > .icon-retweet')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
            'favourites_count' => filter_var($node->filter('.tweet-stats > .tweet-stat > .icon-container > .icon-heart')->getNode(0)->parentNode->textContent, FILTER_SANITIZE_NUMBER_INT),
            'edited_at' => null,
            'favourited' => false,
            'reblogged' => false,
            'muted' => false,
            'bookmarked' => false,
            'content' => $node->filter('.tweet-content.media-body')->text(),
            'filtered' => [],
            'reblog' => null,
            'application' => null,
            'account' => $this->lookupAccount($twitterUsername),
            'media_attachments' => $node->filter('.attachments')->count() > 0
                ? array_merge(
                    $node->filter('.attachments > .gallery-row > .attachment > a')->each(function(Crawler $node) use($tweetID) {
                        return [
                            'id' => $tweetID, // TODO add unique image ids
                            'type' => 'image',
                            'url' => 'https://nitter.net' . $node->attr('href'),
                            'preview_url' => 'https://nitter.net' . $node->filter('img')->attr('src'),
                            'preview_remote_url' => null,
                            'text_url' => null,
                            'meta' => [],
                            'description' => null,
                            'blurhash' => 'UHSF-Day-;j[-paybHkC~qofRjj[9Fj[t7WB', // TODO
                        ];
                    }),
                    $node->filter('.attachments > .gallery-video > .video-container > video')->each(function (Crawler $node) use ($tweetID) {
                        return [
                            'id' => $tweetID, // TODO add unique image ids
                            'type' => 'video',
                            'url' => 'https://nitter.net' . $node->attr('data-url'),
                            'preview_url' => 'https://nitter.net' . $node->attr('poster'),
                            'remote_url' => 'https://nitter.net' . $node->attr('data-url'),
                            'preview_remote_url' => null,
                            'text_url' => null,
                            'meta' => [],
                            'description' => null,
                            'blurHash' => 'UHH_lTDi?HWARN-=9FNG4m~WtSt5-;D$WYxu',
                        ];
                    })
                )
                : [],
            'mentions' => [],
            'tags' => [],
            'emojis' => [],
            'card' => null,
            'poll' => null,
        ];

    }

    public function getTweetContext(): array
    {
        return [
            'ancestors' => [],
            'descendants' => [],
        ]; // TODO
    }

    private function nitterImageURLToTwitterURL(string $imageURL): string
    {
        $imageURL = str_replace(['/pic/'], [''], $imageURL);

        if (!str_contains($imageURL, 'https://')) {
            return 'https://' . $imageURL;
        }
        return $imageURL;
    }

    private function extractTwitterIDFromBannerURL(string $attr)
    {
        $matches = [];
        preg_match('/profile_banners%2F([0-9]+)%2F/', $attr, $matches);
        return $matches[1];
    }
}