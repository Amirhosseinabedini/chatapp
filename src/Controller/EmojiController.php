<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/emoji', name: 'app_emoji_')]
#[IsGranted('ROLE_USER')]
class EmojiController extends AbstractController
{
    #[Route('/search', name: 'search')]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min((int) $request->query->get('limit', 20), 50);

        if (strlen($query) < 1) {
            return new JsonResponse(['emojis' => []]);
        }

        $emojis = $this->searchEmojis($query, $limit);

        return new JsonResponse(['emojis' => $emojis]);
    }

    #[Route('/popular', name: 'popular')]
    public function popular(): JsonResponse
    {
        $popularEmojis = [
            '😀', '😂', '❤️', '👍', '👎', '😍', '😢', '😮', '😡', '😱',
            '🎉', '🔥', '💯', '✨', '👏', '🙌', '💪', '🤔', '😎', '🥳'
        ];

        return new JsonResponse(['emojis' => $popularEmojis]);
    }

    #[Route('/categories', name: 'categories')]
    public function categories(): JsonResponse
    {
        $categories = [
            'smileys' => [
                'name' => 'Smileys & People',
                'emojis' => ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳']
            ],
            'animals' => [
                'name' => 'Animals & Nature',
                'emojis' => ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯', '🦁', '🐮', '🐷', '🐸', '🐵', '🙈', '🙉', '🙊', '🐒', '🦍', '🦧', '🐕', '🐩', '🦮', '🐕‍🦺', '🐈', '🐈‍⬛', '🦄', '🐎', '🦓', '🦌']
            ],
            'food' => [
                'name' => 'Food & Drink',
                'emojis' => ['🍎', '🍊', '🍋', '🍌', '🍉', '🍇', '🍓', '🫐', '🍈', '🍒', '🍑', '🥭', '🍍', '🥥', '🥝', '🍅', '🍆', '🥑', '🥦', '🥬', '🥒', '🌶', '🫒', '🌽', '🥕', '🫑', '🥔', '🍠', '🥐', '🥖', '🍞']
            ],
            'activities' => [
                'name' => 'Activities',
                'emojis' => ['⚽', '🏀', '🏈', '⚾', '🥎', '🎾', '🏐', '🏉', '🎱', '🪀', '🏓', '🏸', '🏒', '🏑', '🥍', '🏏', '🪃', '🥅', '⛳', '🪁', '🏹', '🎣', '🤿', '🥊', '🥋', '🎽', '🛹', '🛷', '⛸', '🥌', '🎿']
            ],
            'objects' => [
                'name' => 'Objects',
                'emojis' => ['⌚', '📱', '📲', '💻', '⌨️', '🖥', '🖨', '🖱', '🖲', '🕹', '🗜', '💽', '💾', '💿', '📀', '📼', '📷', '📸', '📹', '🎥', '📽', '🎞', '📞', '☎️', '📟', '📠', '📺', '📻', '🎙', '🎚', '🎛']
            ],
            'symbols' => [
                'name' => 'Symbols',
                'emojis' => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️', '✝️', '☪️', '🕉', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐', '⛎']
            ]
        ];

        return new JsonResponse(['categories' => $categories]);
    }

    #[Route('/gif/search', name: 'gif_search')]
    public function gifSearch(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min((int) $request->query->get('limit', 20), 50);

        if (strlen($query) < 2) {
            return new JsonResponse(['gifs' => []]);
        }

        // In a real implementation, you would call Tenor API here
        // For now, we'll return mock data
        $gifs = $this->searchGifs($query, $limit);

        return new JsonResponse(['gifs' => $gifs]);
    }

    #[Route('/gif/trending', name: 'gif_trending')]
    public function gifTrending(): JsonResponse
    {
        // Mock trending GIFs
        $trendingGifs = [
            [
                'id' => '1',
                'url' => 'https://media.giphy.com/media/3o7aCRloybJlXpxqRy/giphy.gif',
                'preview' => 'https://media.giphy.com/media/3o7aCRloybJlXpxqRy/giphy-preview.gif',
                'title' => 'Happy Dance'
            ],
            [
                'id' => '2',
                'url' => 'https://media.giphy.com/media/26BRrSvJUoWwJ2q5i/giphy.gif',
                'preview' => 'https://media.giphy.com/media/26BRrSvJUoWwJ2q5i/giphy-preview.gif',
                'title' => 'Thumbs Up'
            ],
            [
                'id' => '3',
                'url' => 'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy.gif',
                'preview' => 'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy-preview.gif',
                'title' => 'Celebration'
            ]
        ];

        return new JsonResponse(['gifs' => $trendingGifs]);
    }

    private function searchEmojis(string $query, int $limit): array
    {
        // Comprehensive emoji database
        $emojiDatabase = [
            'happy' => ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇'],
            'sad' => ['😢', '😭', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖'],
            'love' => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💕', '💖', '💗', '💘', '💝'],
            'angry' => ['😡', '😠', '🤬', '😤', '💢', '🔥'],
            'surprised' => ['😮', '😯', '😲', '😱', '🤯', '😳'],
            'thinking' => ['🤔', '🤨', '🧐', '🤓', '😏'],
            'cool' => ['😎', '🤩', '🥳', '😏', '😌'],
            'thumbs' => ['👍', '👎', '👌', '✌️', '🤞', '🤟', '🤘', '🤙'],
            'hands' => ['👏', '🙌', '👋', '🤚', '🖐', '✋', '🖖', '👌', '🤏', '✌️'],
            'celebration' => ['🎉', '🎊', '🎈', '🎁', '🏆', '🥇', '🥈', '🥉', '🏅', '🎖'],
            'fire' => ['🔥', '💯', '✨', '⭐', '🌟', '💫', '⚡', '🌈'],
            'food' => ['🍕', '🍔', '🍟', '🌭', '🥪', '🌮', '🌯', '🥙', '🥗', '🍝'],
            'drink' => ['☕', '🍵', '🥤', '🍶', '🍺', '🍻', '🥂', '🍷', '🥃', '🍸'],
            'heart' => ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔'],
            'star' => ['⭐', '🌟', '💫', '✨', '⚡'],
            'party' => ['🎉', '🎊', '🥳', '🎈', '🎁'],
            'weather' => ['☀️', '🌤', '⛅', '🌥', '☁️', '🌦', '🌧', '⛈', '🌩', '🌨'],
            'animals' => ['🐶', '🐱', '🐭', '🐹', '🐰', '🦊', '🐻', '🐼', '🐨', '🐯'],
            'nature' => ['🌱', '🌿', '🍀', '🌾', '🌵', '🌲', '🌳', '🌴', '🌰', '🍄'],
            'travel' => ['✈️', '🚀', '🚁', '🚂', '🚃', '🚄', '🚅', '🚆', '🚇', '🚈']
        ];

        $results = [];
        $queryLower = strtolower($query);

        foreach ($emojiDatabase as $category => $emojis) {
            if (strpos($category, $queryLower) !== false) {
                $results = array_merge($results, $emojis);
            }
        }

        // Also search individual emoji names
        foreach ($emojiDatabase as $emojis) {
            foreach ($emojis as $emoji) {
                if (in_array($emoji, $results)) continue;
                
                // Simple keyword matching for emoji descriptions
                $emojiKeywords = $this->getEmojiKeywords($emoji);
                foreach ($emojiKeywords as $keyword) {
                    if (strpos($keyword, $queryLower) !== false) {
                        $results[] = $emoji;
                        break;
                    }
                }
            }
        }

        return array_slice(array_unique($results), 0, $limit);
    }

    private function getEmojiKeywords(string $emoji): array
    {
        $keywords = [
            '😀' => ['grinning', 'face', 'happy', 'smile'],
            '😂' => ['laughing', 'tears', 'joy', 'funny'],
            '❤️' => ['heart', 'red', 'love', 'like'],
            '👍' => ['thumbs', 'up', 'good', 'yes', 'approve'],
            '👎' => ['thumbs', 'down', 'bad', 'no', 'disapprove'],
            '😍' => ['heart', 'eyes', 'love', 'adore'],
            '😢' => ['crying', 'sad', 'tears'],
            '😮' => ['surprised', 'shocked', 'wow'],
            '😡' => ['angry', 'mad', 'furious'],
            '😱' => ['scream', 'shocked', 'fear'],
            '🎉' => ['party', 'celebration', 'confetti'],
            '🔥' => ['fire', 'hot', 'lit', 'amazing'],
            '💯' => ['hundred', 'perfect', '100'],
            '✨' => ['sparkles', 'magic', 'shiny'],
            '👏' => ['clap', 'applause', 'good'],
            '🙌' => ['raise', 'hands', 'celebration'],
            '💪' => ['muscle', 'strong', 'power'],
            '🤔' => ['thinking', 'hmm', 'considering'],
            '😎' => ['cool', 'sunglasses', 'awesome'],
            '🥳' => ['party', 'celebration', 'birthday']
        ];

        return $keywords[$emoji] ?? [];
    }

    private function searchGifs(string $query, int $limit): array
    {
        // Mock GIF search results
        // In a real implementation, you would call Tenor API
        $mockGifs = [
            [
                'id' => '1',
                'url' => 'https://media.giphy.com/media/3o7aCRloybJlXpxqRy/giphy.gif',
                'preview' => 'https://media.giphy.com/media/3o7aCRloybJlXpxqRy/giphy-preview.gif',
                'title' => 'Happy Dance',
                'tags' => ['happy', 'dance', 'celebration']
            ],
            [
                'id' => '2',
                'url' => 'https://media.giphy.com/media/26BRrSvJUoWwJ2q5i/giphy.gif',
                'preview' => 'https://media.giphy.com/media/26BRrSvJUoWwJ2q5i/giphy-preview.gif',
                'title' => 'Thumbs Up',
                'tags' => ['thumbs', 'up', 'good', 'approve']
            ],
            [
                'id' => '3',
                'url' => 'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy.gif',
                'preview' => 'https://media.giphy.com/media/l0MYt5jPR6QX5pnqM/giphy-preview.gif',
                'title' => 'Celebration',
                'tags' => ['celebration', 'party', 'happy']
            ]
        ];

        // Filter by query
        $filteredGifs = array_filter($mockGifs, function($gif) use ($query) {
            $queryLower = strtolower($query);
            return strpos(strtolower($gif['title']), $queryLower) !== false ||
                   in_array($queryLower, array_map('strtolower', $gif['tags']));
        });

        return array_slice($filteredGifs, 0, $limit);
    }
}

