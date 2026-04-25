<?php

/**
 * TaskController — Handles all Task Management module HTTP requests.
 *
 * Routes are prefixed with /tasks.
 * Delegates business logic to TaskService.
 *
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8
 */
class TaskController
{
    private TaskService $taskService;

    public function __construct()
    {
        $this->taskService = new TaskService();
    }

    public function index(): void
    {
        redirect(base_url('tasks/list'));
    }

    public function list(): void
    {
        PermissionHelper::requirePermission('task.view');

        $branchId = PermissionHelper::getBranchFilter();

        $filters = [
            'status'      => sanitize($_GET['status']      ?? ''),
            'priority'    => sanitize($_GET['priority']    ?? ''),
            'assigned_to' => (int)($_GET['assigned_to']  ?? 0) ?: null,
            'branch_id'   => $branchId,
            'search'     => sanitize($_GET['search']     ?? ''),
            'page'      => max(1, (int)($_GET['page'] ?? 1)),
            'limit'     => 25,
        ];

        $result     = $this->taskService->getTasks($filters);
        $employees = $this->taskService->getAllEmployees($branchId);

        $pageTitle      = 'Task List';
        $currentPage    = 'tasks';
        $currentSubPage = 'tasks-list';
        $viewFile       = BASE_PATH . '/views/tasks/list.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function create(): void
    {
        PermissionHelper::requirePermission('task.create');

        $branchId  = PermissionHelper::getBranchFilter();
        $employees = $this->taskService->getAllEmployees($branchId);

        $pageTitle      = 'Create Task';
        $currentPage    = 'tasks';
        $currentSubPage = 'task-create';
        $viewFile       = BASE_PATH . '/views/tasks/form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function store(): void
    {
        PermissionHelper::requirePermission('task.create');

        try {
            $branchId = PermissionHelper::getBranchFilter()
                ?? (int)($_POST['branch_id'] ?? 0) ?: null;

            $data = [
                'title'        => sanitize($_POST['title'] ?? ''),
                'description'  => sanitize($_POST['description'] ?? ''),
                'priority'    => sanitize($_POST['priority'] ?? 'medium'),
                'due_date'   => sanitize($_POST['due_date'] ?? ''),
                'assigned_to' => (int)($_POST['assigned_to'] ?? 0) ?: null,
                'branch_id'  => $branchId,
                'created_by'  => $_SESSION['user_id'] ?? null,
            ];

            if (empty($data['title'])) {
                $_SESSION['error'] = 'Task title is required.';
                redirect(base_url('tasks/create'));
                return;
            }

            $taskId = $this->taskService->createTask($data);

            if (!empty($data['assigned_to'])) {
                $this->taskService->assignTask($taskId, (int)$data['assigned_to'], '');
            }

            $_SESSION['success'] = 'Task #' . $taskId . ' created successfully.';
            redirect(base_url('tasks/view/' . $taskId));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('tasks/create'));
        }
    }

    public function view(int $id): void
    {
        PermissionHelper::requirePermission('task.view');

        $task = $this->taskService->getTask($id);
        if (!$task) {
            $_SESSION['error'] = 'Task not found.';
            redirect(base_url('tasks/list'));
            return;
        }

        $branchId  = PermissionHelper::getBranchFilter();
        $employees = $this->taskService->getAllEmployees($branchId);

        $pageTitle      = 'Task #' . $id . ': ' . htmlspecialchars($task['title']);
        $currentPage    = 'tasks';
        $currentSubPage = 'tasks-list';
        $viewFile       = BASE_PATH . '/views/tasks/view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function edit(int $id): void
    {
        PermissionHelper::requirePermission('task.edit');

        $task = $this->taskService->getTask($id);
        if (!$task) {
            $_SESSION['error'] = 'Task not found.';
            redirect(base_url('tasks/list'));
            return;
        }

        $branchId  = PermissionHelper::getBranchFilter();
        $employees = $this->taskService->getAllEmployees($branchId);

        $pageTitle      = 'Edit Task #' . $id;
        $currentPage    = 'tasks';
        $currentSubPage = 'tasks-list';
        $viewFile       = BASE_PATH . '/views/tasks/form.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function update(int $id): void
    {
        PermissionHelper::requirePermission('task.edit');

        try {
            $data = [
                'title'       => sanitize($_POST['title'] ?? ''),
                'description' => sanitize($_POST['description'] ?? ''),
                'priority'   => sanitize($_POST['priority'] ?? 'medium'),
                'status'    => sanitize($_POST['status'] ?? 'pending'),
                'due_date'  => sanitize($_POST['due_date'] ?? '') ?: null,
            ];

            if (empty($data['title'])) {
                $_SESSION['error'] = 'Task title is required.';
                redirect(base_url('tasks/edit/' . $id));
                return;
            }

            $this->taskService->updateTask($id, $data);
            $_SESSION['success'] = 'Task updated successfully.';
            redirect(base_url('tasks/view/' . $id));
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
            redirect(base_url('tasks/edit/' . $id));
        }
    }

    public function assign(int $id): void
    {
        PermissionHelper::requirePermission('task.assign');

        $employeeId = (int)($_POST['employee_id'] ?? 0);
        $notes     = sanitize($_POST['notes'] ?? '');

        if (!$employeeId) {
            $_SESSION['error'] = 'Please select an employee to assign.';
            redirect(base_url('tasks/view/' . $id));
            return;
        }

        try {
            $this->taskService->assignTask($id, $employeeId, $notes);
            $_SESSION['success'] = 'Task assigned successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('tasks/view/' . $id));
    }

    public function status(int $id): void
    {
        PermissionHelper::requirePermission('task.edit');

        $newStatus = sanitize($_POST['status'] ?? '');
        $validStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];

        if (!in_array($newStatus, $validStatuses)) {
            $_SESSION['error'] = 'Invalid status.';
            redirect(base_url('tasks/view/' . $id));
            return;
        }

        try {
            $this->taskService->updateStatus($id, $newStatus);
            $_SESSION['success'] = 'Task status updated to ' . $newStatus . '.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('tasks/view/' . $id));
    }

    public function delete(int $id): void
    {
        PermissionHelper::requirePermission('task.delete');

        try {
            $this->taskService->deleteTask($id);
            $_SESSION['success'] = 'Task deleted successfully.';
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('tasks/list'));
    }

    public function calendar(): void
    {
        PermissionHelper::requirePermission('task.view');

        $branchId = PermissionHelper::getBranchFilter();
        $date    = sanitize($_GET['date'] ?? date('Y-m-d'));

        $tasks = $this->taskService->getTasksByDate($date, $branchId);

        $pageTitle      = 'Task Calendar';
        $currentPage    = 'tasks';
        $currentSubPage = 'task-calendar';
        $viewFile       = BASE_PATH . '/views/tasks/calendar.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    public function bulkAssign(): void
    {
        PermissionHelper::requirePermission('task.assign');

        $taskIds   = $_POST['task_ids']   ?? [];
        $employeeId = (int)($_POST['employee_id'] ?? 0);

        if (empty($taskIds) || !$employeeId) {
            $_SESSION['error'] = 'Please select tasks and an employee.';
            redirect(base_url('tasks/list'));
            return;
        }

        try {
            $count = $this->taskService->bulkAssign($taskIds, $employeeId);
            $_SESSION['success'] = "{$count} task(s) assigned successfully.";
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        redirect(base_url('tasks/list'));
    }

    public function reports(): void
    {
        PermissionHelper::requirePermission('task.reports');

        $branchId = PermissionHelper::getBranchFilter();
        $report  = $this->taskService->getCompletionReport($branchId);

        $pageTitle      = 'Task Reports';
        $currentPage    = 'tasks';
        $currentSubPage = 'task-reports';
        $viewFile       = BASE_PATH . '/views/tasks/reports.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }
}