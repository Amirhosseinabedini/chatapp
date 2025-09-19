<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\GroupMember;
use App\Entity\GroupMessage;
use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\GroupMemberRepository;
use App\Repository\GroupMessageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[Route('/groups', name: 'app_groups_')]
#[IsGranted('ROLE_USER')]
class GroupController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GroupRepository $groupRepository,
        private GroupMemberRepository $groupMemberRepository,
        private GroupMessageRepository $groupMessageRepository,
        private UserRepository $userRepository,
        private HubInterface $hub,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        // Force fresh user retrieval to avoid caching issues
        $currentUser = $this->getUser();
        if ($currentUser) {
            // Refresh user from database to ensure we have latest data
            $currentUser = $this->userRepository->find($currentUser->getId());
        }
        
        $userGroups = $this->groupRepository->getUserGroups($currentUser);
        $publicGroups = $this->groupRepository->getPublicGroups(10);

        $response = $this->render('groups/index.html.twig', [
            'user_groups' => $userGroups,
            'public_groups' => $publicGroups,
            'current_user' => $currentUser,
        ]);
        
        // Add aggressive cache-busting headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('ETag', md5(serialize([$currentUser->getId(), time()])));
        
        return $response;
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Group name is required'], 400);
        }

        $group = new Group();
        $group->setName($data['name']);
        $group->setDescription($data['description'] ?? null);
        $group->setOwner($currentUser);
        $group->setIsPublic($data['is_public'] ?? false);

        $this->entityManager->persist($group);

        // Add owner as member
        $ownerMember = new GroupMember();
        $ownerMember->setGroup($group);
        $ownerMember->setUser($currentUser);
        $ownerMember->setRole(GroupMember::ROLE_OWNER);
        $this->entityManager->persist($ownerMember);

        $this->entityManager->flush();

        // Generate invite code
        $group->generateInviteCode();
        $this->entityManager->flush();

        // Create system message
        $this->createSystemMessage($group, 'group_created', [
            'user_name' => $currentUser->getDisplayNameOrEmail(),
            'group_name' => $group->getName(),
        ]);

        $this->logger->info('Group created', [
            'group_id' => $group->getId(),
            'group_name' => $group->getName(),
            'owner_id' => $currentUser->getId(),
        ]);

        return new JsonResponse([
            'success' => true,
            'group_id' => $group->getId(),
            'invite_code' => $group->getInviteCode(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        // Force fresh user retrieval to avoid caching issues
        $currentUser = $this->getUser();
        if ($currentUser) {
            // Refresh user from database to ensure we have latest data
            $currentUser = $this->userRepository->find($currentUser->getId());
        }
        
        $group = $this->groupRepository->find($id);

        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $isPublicGroup = $group->isPublic();
        
        if (!$member && !$isAdmin && !$isPublicGroup) {
            throw $this->createNotFoundException('You are not a member of this group');
        }

        $messages = $this->groupMessageRepository->getGroupMessages($group, 50);
        $pinnedMessages = $this->groupMessageRepository->getPinnedMessages($group);
        $members = $this->groupMemberRepository->getGroupMembers($group);

        $userRole = $member ? $member->getRole() : ($isAdmin ? 'admin' : ($isPublicGroup ? 'guest' : null));
        
        $response = $this->render('groups/show.html.twig', [
            'group' => $group,
            'messages' => array_reverse($messages),
            'pinned_messages' => $pinnedMessages,
            'members' => $members,
            'current_user' => $currentUser,
            'user_role' => $userRole,
            'is_admin' => $isAdmin,
        ]);
        
        // Add aggressive cache-busting headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->headers->set('ETag', md5(serialize([$currentUser->getId(), $group->getId(), time()])));
        
        return $response;
    }

    #[Route('/join/{inviteCode}', name: 'join_by_invite')]
    public function joinByInvite(string $inviteCode): JsonResponse
    {
        $currentUser = $this->getUser();
        $group = $this->groupRepository->findByInviteCode($inviteCode);

        if (!$group) {
            return new JsonResponse(['error' => 'Invalid or expired invite code'], 400);
        }

        // Check if user is already a member
        $existingMember = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        if ($existingMember) {
            return new JsonResponse(['error' => 'You are already a member of this group'], 400);
        }

        // Add user as member
        $member = new GroupMember();
        $member->setGroup($group);
        $member->setUser($currentUser);
        $member->setRole(GroupMember::ROLE_MEMBER);
        $this->entityManager->persist($member);

        // Create system message
        $this->createSystemMessage($group, 'user_joined', [
            'user_name' => $currentUser->getDisplayNameOrEmail(),
        ]);

        $this->entityManager->flush();

        $this->logger->info('User joined group via invite', [
            'group_id' => $group->getId(),
            'user_id' => $currentUser->getId(),
            'invite_code' => $inviteCode,
        ]);

        return new JsonResponse([
            'success' => true,
            'group_id' => $group->getId(),
            'group_name' => $group->getName(),
        ]);
    }

    #[Route('/{id}/invite', name: 'generate_invite', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function generateInvite(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        $group = $this->groupRepository->find($id);

        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        if ((!$member || !$member->canManageMembers()) && !$isAdmin) {
            return new JsonResponse(['error' => 'You do not have permission to generate invites'], 403);
        }

        $inviteCode = $group->generateInviteCode();
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'invite_code' => $inviteCode,
            'expires_at' => $group->getInviteExpiresAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/{id}/members', name: 'manage_members', requirements: ['id' => '\d+'])]
    public function manageMembers(int $id): Response
    {
        $currentUser = $this->getUser();
        $group = $this->groupRepository->find($id);

        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        if ((!$member || !$member->canManageMembers()) && !$isAdmin) {
            throw $this->createNotFoundException('You do not have permission to manage members');
        }

        $members = $this->groupMemberRepository->getGroupMembers($group);

        return $this->render('groups/members.html.twig', [
            'group' => $group,
            'members' => $members,
            'current_user' => $currentUser,
            'user_role' => $member ? $member->getRole() : null,
        ]);
    }

    #[Route('/{id}/members/{userId}/role', name: 'update_member_role', requirements: ['id' => '\d+', 'userId' => '\d+'], methods: ['POST'])]
    public function updateMemberRole(int $id, int $userId, Request $request): JsonResponse
    {
        $currentUser = $this->getUser();
        $group = $this->groupRepository->find($id);
        $targetUser = $this->userRepository->find($userId);

        if (!$group || !$targetUser) {
            return new JsonResponse(['error' => 'Group or user not found'], 404);
        }

        $currentMember = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        if ((!$currentMember || !$currentMember->canManageMembers()) && !$isAdmin) {
            return new JsonResponse(['error' => 'You do not have permission to manage members'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $newRole = $data['role'] ?? null;

        if (!in_array($newRole, [GroupMember::ROLE_MEMBER, GroupMember::ROLE_MODERATOR])) {
            return new JsonResponse(['error' => 'Invalid role'], 400);
        }

        $targetMember = $this->groupMemberRepository->findByGroupAndUser($group, $targetUser);
        if (!$targetMember) {
            return new JsonResponse(['error' => 'User is not a member of this group'], 400);
        }

        $oldRole = $targetMember->getRole();
        $targetMember->setRole($newRole);
        $this->entityManager->flush();

        // Create system message
        $this->createSystemMessage($group, 'role_changed', [
            'user_name' => $targetUser->getDisplayNameOrEmail(),
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'changed_by' => $currentUser->getDisplayNameOrEmail(),
        ]);

        $this->logger->info('Member role updated', [
            'group_id' => $group->getId(),
            'user_id' => $targetUser->getId(),
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'updated_by' => $currentUser->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/leave', name: 'leave', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function leave(int $id): JsonResponse
    {
        $currentUser = $this->getUser();
        $group = $this->groupRepository->find($id);

        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        if (!$member) {
            return new JsonResponse(['error' => 'You are not a member of this group'], 400);
        }

        // Owner cannot leave, must transfer ownership first
        if ($member->isOwner()) {
            return new JsonResponse(['error' => 'Group owner must transfer ownership before leaving'], 400);
        }

        $this->groupMemberRepository->removeUserFromGroup($group, $currentUser);

        // Create system message
        $this->createSystemMessage($group, 'user_left', [
            'user_name' => $currentUser->getDisplayNameOrEmail(),
        ]);

        $this->logger->info('User left group', [
            'group_id' => $group->getId(),
            'user_id' => $currentUser->getId(),
        ]);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/members.json', name: 'get_members', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getMembers(int $id): JsonResponse
    {
        // Force fresh user retrieval to avoid caching issues
        $currentUser = $this->getUser();
        if ($currentUser) {
            // Refresh user from database to ensure we have latest data
            $currentUser = $this->userRepository->find($currentUser->getId());
        }
        
        $group = $this->groupRepository->find($id);

        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $member = $this->groupMemberRepository->findByGroupAndUser($group, $currentUser);
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        if (!$member && !$isAdmin) {
            return new JsonResponse(['error' => 'You are not a member of this group'], 403);
        }

        $members = $this->groupMemberRepository->getGroupMembers($group);

        $memberData = [];
        foreach ($members as $member) {
            $memberData[] = [
                'user' => [
                    'id' => $member->getUser()->getId(),
                    'display_name' => $member->getUser()->getDisplayName(),
                    'display_name_or_email' => $member->getUser()->getDisplayNameOrEmail(),
                    'avatar' => $member->getUser()->getAvatar(),
                    'is_online' => $member->getUser()->isOnline(),
                    'last_seen_at' => $member->getUser()->getLastSeenAt()?->format('Y-m-d H:i:s'),
                ],
                'role' => $member->getRole(),
            ];
        }

        $response = new JsonResponse(['members' => $memberData]);
        
        // Add cache-busting headers
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }

    private function createSystemMessage(Group $group, string $type, array $data): void
    {
        $message = new GroupMessage();
        $message->setGroup($group);
        $message->setSender($group->getOwner()); // System messages sent by group owner
        $message->setContent($this->formatSystemMessage($type, $data));
        $message->setMessageType('system');
        $message->setIsSystemMessage(true);

        $this->entityManager->persist($message);
    }

    private function formatSystemMessage(string $type, array $data): string
    {
        switch ($type) {
            case 'group_created':
                return "Group '{$data['group_name']}' was created by {$data['user_name']}";
            case 'user_joined':
                return "{$data['user_name']} joined the group";
            case 'user_left':
                return "{$data['user_name']} left the group";
            case 'role_changed':
                return "{$data['changed_by']} changed {$data['user_name']}'s role from {$data['old_role']} to {$data['new_role']}";
            default:
                return 'System message';
        }
    }
}
