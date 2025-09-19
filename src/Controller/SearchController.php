<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function search(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->render('search/index.html.twig', [
                'query' => '',
                'results' => [],
                'message' => 'Please enter a search term'
            ]);
        }

        // Mock search results - in a real app, you'd search the database
        $results = [
            'users' => [
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'avatar' => '/images/default-avatar.svg',
                    'type' => 'user'
                ],
                [
                    'id' => 2,
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'avatar' => '/images/default-avatar.svg',
                    'type' => 'user'
                ]
            ],
            'groups' => [
                [
                    'id' => 1,
                    'name' => 'Project Team',
                    'description' => 'Team for the main project',
                    'avatar' => '/images/default-avatar.svg',
                    'type' => 'group'
                ]
            ],
            'messages' => [
                [
                    'id' => 1,
                    'content' => 'This is a sample message containing the search term',
                    'sender' => 'John Doe',
                    'timestamp' => new \DateTime(),
                    'type' => 'message'
                ]
            ]
        ];

        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
            'totalResults' => count($results['users']) + count($results['groups']) + count($results['messages'])
        ]);
    }

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function apiSearch(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $query = $request->query->get('q', '');
        $type = $request->query->get('type', 'all'); // all, users, groups, messages

        if (empty($query)) {
            return $this->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        // Mock API search results
        $results = [
            'users' => [],
            'groups' => [],
            'messages' => []
        ];

        if ($type === 'all' || $type === 'users') {
            $results['users'] = [
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'avatar' => '/images/default-avatar.svg'
                ]
            ];
        }

        if ($type === 'all' || $type === 'groups') {
            $results['groups'] = [
                [
                    'id' => 1,
                    'name' => 'Project Team',
                    'description' => 'Team for the main project',
                    'avatar' => '/images/default-avatar.svg'
                ]
            ];
        }

        return $this->json([
            'success' => true,
            'query' => $query,
            'results' => $results,
            'totalResults' => count($results['users']) + count($results['groups']) + count($results['messages'])
        ]);
    }
}
