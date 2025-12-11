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

        // Pagination parameters
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = (int) $request->query->get('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        // Convert dates to DateTime objects if provided
        $startDateTime = $startDate ? new \DateTime($startDate . ' 00:00:00') : null;
        $endDateTime = $endDate ? new \DateTime($endDate . ' 23:59:59') : null;

        $userIdInt = $userId ? (int) $userId : null;
        $actionValue = $action ?: null;

        // Total count for pagination
        $totalLogs = $activityLogRepository->countWithFilters(
            $userIdInt,
            $actionValue,
            $startDateTime,
            $endDateTime,
        );

        $totalPages = max(1, (int) ceil($totalLogs / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;

        // Fetch filtered logs for current page
        $activityLogs = $activityLogRepository->findWithFilters(
            $userIdInt,
            $actionValue,
            $startDateTime,
            $endDateTime,
            $perPage,
            $offset,
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
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'allowed_per_page' => $allowedPerPage,
                'total' => $totalLogs,
                'total_pages' => $totalPages,
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
