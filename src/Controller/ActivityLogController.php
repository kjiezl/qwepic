<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('dashboard/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
final class ActivityLogController extends AbstractController
{
    #[Route(name: 'app_activity_log_index', methods: ['GET'])]
    public function index(
        Request $request,
        ActivityLogRepository $activityLogRepository,
        UserRepository $userRepository
    ): Response {
        // Get filter parameters from request
        $userId = $request->query->get('user_id');
        $action = $request->query->get('action');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // Convert dates to DateTime objects if provided
        $startDateTime = $startDate ? new \DateTime($startDate . ' 00:00:00') : null;
        $endDateTime = $endDate ? new \DateTime($endDate . ' 23:59:59') : null;

        // Fetch filtered logs
        $activityLogs = $activityLogRepository->findWithFilters(
            $userId ? (int) $userId : null,
            $action ?: null,
            $startDateTime,
            $endDateTime
        );

        // Get all users for filter dropdown
        $users = $userRepository->findAll();

        // Get distinct actions for filter dropdown
        $actions = $activityLogRepository->findDistinctActions();

        return $this->render('activity_log/index.html.twig', [
            'activity_logs' => $activityLogs,
            'users' => $users,
            'actions' => $actions,
            'filters' => [
                'user_id' => $userId,
                'action' => $action,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_activity_log_show', methods: ['GET'])]
    public function show(ActivityLog $activityLog): Response
    {
        return $this->render('activity_log/show.html.twig', [
            'activity_log' => $activityLog,
        ]);
    }
}
