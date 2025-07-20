<?php

namespace Jk\Vts\Endpoint;

use Jk\Vts\Services\Chat;
use Jk\Vts\Services\Db;
use Jk\Vts\Services\Embed;
use Jk\Vts\Services\ReRanker;

class SearchTranscriptionEmbeds {
    protected string $namespace = 'vts/v1';
    protected string $route = 'search-transcription-embeds';

    const RERANK = true;

    // TODO: chnage these
    protected Db $db;
    protected Embed $embedder;
    protected Chat $chat;
    protected ReRanker $reranker;

    public function __construct() {
        $this->db = Db::initLocal();
        $this->embedder = new Embed();
        $this->chat = new Chat();
        $this->reranker = new ReRanker();
    }

    public function register() {
        register_rest_route(
            $this->namespace,
            $this->route,
            [
                'methods' => 'GET',
                'callback' => [$this, 'get'],
                'permission_callback' => [$this, 'permission'],
                'args' => [
                    'query' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ]
        );
    }

    public function permission() {
        return current_user_can('read');
    }

    public function get(\WP_REST_Request $request) {
        $query = $request->get_param('query');
        if (empty($query)) {
            return rest_ensure_response(new \WP_Error('invalid_query', 'Query is required', array('status' => 400)));
        }

        list($err, $embeddedQuery) = $this->embedder->createEmbed($query);
        if ($err) {
            return rest_ensure_response(new \WP_Error('invalid_query', $err, array('status' => 400)));
        }

        $foundChunks = $this->db->search($embeddedQuery);

        if (self::RERANK) {
            $sortedDocs = $this->reranker->rerank(
                $query,
                collect($foundChunks)->map(function ($chunk) {
                    return $chunk['content'];
                })->toArray()
            );
            $foundChunks = collect($sortedDocs)->map(function ($doc) use ($foundChunks) {
                $foundChunk = array_find($foundChunks, function ($chunk) use ($doc) {
                    return $chunk['content'] == $doc;
                });
                if (!$foundChunk) {
                    error_log("Could not find chunk for doc $doc");
                }
                return $foundChunk;
            })->toArray();
        }

        $aiCuratedChunks = $this->chat->queryChunks(
            $query,
            collect($aiCuratedChunks ?? $foundChunks)->map(function ($chunk) {
                unset($chunk['relative_distance']);
                return $chunk;
            })->toArray()
        );


        $restoredChunks = collect($foundChunks)->filter(function ($chunk) use ($aiCuratedChunks) {
            $chunksWithSameVimeoId = array_values(array_filter($aiCuratedChunks, function ($aiChunk) use ($chunk) {
                $aiChunk = (array) $aiChunk;
                return $aiChunk['vimeoId'] == $chunk['vimeoId'];
            }));
            return array_find($chunksWithSameVimeoId, function ($aichunk) use ($chunk) {
                $aiChunk = (array)$aichunk;
                return $aiChunk['start_time'] == $chunk['start_time'];
            });
        })->toArray();

        $results = $this->formatResults(array_values($restoredChunks));
        // $results = $this->formatResults($foundChunks);

        return rest_ensure_response($results);
    }

    private function formatResults(array $results): array {
        return collect($results)
            ->map(function ($result) {
                $result['score'] = floor(((2 - $result["relative_distance"]) / 2) * 100);
                unset($result['relative_distance']);
                $result['iframeSrcUrl'] = "https://player.vimeo.com/video/" . $result['vimeoId'];
                $result['thumbnail'] = $this->getVimeoThumbnail($result['vimeoId']);
                return $result;
            })
            ->toArray();
    }

    private function getVimeoThumbnail(string $vimeoId): ?string {
        if (!class_exists('\JP\VimeoUtils')) {
            return null;
        }
        return \JP\VimeoUtils::getThumb($vimeoId);
    }
}
